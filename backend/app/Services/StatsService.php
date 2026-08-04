<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Business;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Everything the dashboard counts.
 *
 * Two rules hold throughout:
 *
 *  - No driver-specific SQL. The previous version mixed MySQL's LEFT() with
 *    PostgreSQL's EXTRACT(... )::int in one method, so it could not run
 *    anywhere. Grouping that SQL does differently per driver is done in PHP;
 *    what stays in SQL is portable aggregation.
 *
 *  - Revenue reads bookings.price, never services.price. The service may have
 *    been repriced or deleted since; what the customer agreed to is what the
 *    books should show.
 */
class StatsService
{
    private const REVENUE_STATUSES = [Booking::STATUS_CONFIRMED, Booking::STATUS_COMPLETED];

    public function __construct(
        private readonly AvailabilityService $availability,
    ) {}

    /** Headline figures for the top of the dashboard. */
    public function overview(Business $business): array
    {
        $tz    = $this->availability->timezone($business);
        $now   = Carbon::now($tz);
        $month = $this->monthBounds($now);
        $prev  = $this->monthBounds($now->copy()->subMonthNoOverflow());

        $thisMonth = $this->monthSummary($business, $month);
        $lastMonth = $this->monthSummary($business, $prev);

        $limit = $business->user?->monthlyBookingLimit();

        return [
            'today_bookings'   => $this->scope($business)
                ->whereDate('date', $now->toDateString())
                ->whereIn('status', Booking::ACTIVE_STATUSES)
                ->count(),

            'pending_bookings' => $this->scope($business)
                ->where('status', Booking::STATUS_PENDING)
                ->count(),

            'monthly_bookings'   => $thisMonth['total'],
            'monthly_trend'      => $this->trend($thisMonth['total'], $lastMonth['total']),
            'revenue_this_month' => $thisMonth['revenue'],
            'revenue_trend'      => $this->trend($thisMonth['revenue'], $lastMonth['revenue']),

            'completion_rate'   => $this->rate($thisMonth['completed'], $thisMonth['total']),
            'cancellation_rate' => $this->rate($thisMonth['cancelled'], $thisMonth['total']),
            'no_show_rate'      => $this->rate($thisMonth['no_show'], $thisMonth['total']),

            'total_clients'     => $this->distinctCustomers($this->scope($business)),
            'monthly_clients'   => $this->distinctCustomers(
                $this->scope($business)->whereBetween('date', $month)
            ),
            'returning_clients' => $this->returningCustomers($business),

            // Usage is metered on created_at (see BookingService::guardQuota),
            // so the number shown here has to be counted the same way.
            'plan'       => $business->user?->effectivePlan(),
            'plan_limit' => $limit,
            'plan_used'  => $this->quotaUsed($business, $now),

            'currency'     => $business->currency,
            'has_services' => $business->services()->exists(),
        ];
    }

    /** Booking counts per day for the last week and per month for the last half-year. */
    public function charts(Business $business): array
    {
        $tz  = $this->availability->timezone($business);
        $now = Carbon::now($tz);

        return [
            'daily'   => $this->dailySeries($business, $now, days: 7),
            'monthly' => $this->monthlySeries($business, $now, months: 6),
        ];
    }

    /** The Pro-only breakdowns. */
    public function analytics(Business $business): array
    {
        $tz    = $this->availability->timezone($business);
        $now   = Carbon::now($tz);
        $month = $this->monthBounds($now);

        return [
            'top_services'        => $this->topServices($business, $month),
            'top_revenue'         => $this->topRevenue($business),
            'peak_hours'          => $this->peakHours($business),
            'peak_days'           => $this->peakDays($business),
            'status_distribution' => $this->statusDistribution($business),
            'recent_activity'     => $this->recentActivity($business),
        ];
    }

    // ─── Building blocks ─────────────────────────────────────────────────

    private function scope(Business $business): Builder
    {
        return Booking::query()->forBusiness($business->id);
    }

    /** @return array{0: string, 1: string} */
    private function monthBounds(Carbon $moment): array
    {
        return [
            $moment->copy()->startOfMonth()->toDateString(),
            $moment->copy()->endOfMonth()->toDateString(),
        ];
    }

    /**
     * One pass over a month, bucketed in PHP.
     *
     * Five separate COUNT queries per month was five round trips to say the
     * same thing; the row count here is a single salon's month.
     *
     * @param  array{0: string, 1: string}  $bounds
     */
    private function monthSummary(Business $business, array $bounds): array
    {
        $rows = $this->scope($business)
            ->whereBetween('date', $bounds)
            ->get(['status', 'price']);

        return [
            'total'     => $rows->count(),
            'completed' => $rows->where('status', Booking::STATUS_COMPLETED)->count(),
            'cancelled' => $rows->where('status', Booking::STATUS_CANCELLED)->count(),
            'no_show'   => $rows->where('status', Booking::STATUS_NO_SHOW)->count(),
            'revenue'   => (int) $rows->whereIn('status', self::REVENUE_STATUSES)->sum('price'),
        ];
    }

    private function quotaUsed(Business $business, Carbon $now): int
    {
        return $this->scope($business)
            ->where('status', '!=', Booking::STATUS_CANCELLED)
            ->whereBetween('created_at', [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ])
            ->count();
    }

    /**
     * Customers are identified by phone: it is the one field every booking has
     * and the one people reuse across visits.
     */
    private function distinctCustomers(Builder $query): int
    {
        return $query->distinct()->count('customer_phone');
    }

    private function returningCustomers(Business $business): int
    {
        return $this->scope($business)
            ->select('customer_phone')
            ->groupBy('customer_phone')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
    }

    /** Percentage change, guarding the "nothing to compare against" case. */
    private function trend(float $current, float $previous): float
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100, 1);
        }

        return $current > 0 ? 100.0 : 0.0;
    }

    private function rate(int $part, int $whole): int
    {
        return $whole > 0 ? (int) round(($part / $whole) * 100) : 0;
    }

    // ─── Series ──────────────────────────────────────────────────────────

    private function dailySeries(Business $business, Carbon $now, int $days): array
    {
        $start = $now->copy()->subDays($days - 1)->startOfDay();

        $counts = $this->scope($business)
            ->where('date', '>=', $start->toDateString())
            ->get(['date'])
            ->countBy(fn (Booking $b) => $b->date->toDateString());

        $labels = [];
        $values = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day      = $now->copy()->subDays($i);
            $labels[] = $day->locale('fr')->isoFormat('ddd D');
            $values[] = $counts[$day->toDateString()] ?? 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * One query for the whole range, bucketed by Y-m in PHP; the previous
     * version fired a COUNT per month inside a loop.
     */
    private function monthlySeries(Business $business, Carbon $now, int $months): array
    {
        $start = $now->copy()->subMonthsNoOverflow($months - 1)->startOfMonth();

        $counts = $this->scope($business)
            ->where('date', '>=', $start->toDateString())
            ->get(['date'])
            ->countBy(fn (Booking $b) => $b->date->format('Y-m'));

        $labels = [];
        $values = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month    = $now->copy()->subMonthsNoOverflow($i);
            $labels[] = $month->locale('fr')->isoFormat('MMM YY');
            $values[] = $counts[$month->format('Y-m')] ?? 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    // ─── Analytics ───────────────────────────────────────────────────────

    /** @param array{0: string, 1: string} $bounds */
    private function topServices(Business $business, array $bounds): Collection
    {
        return $this->scope($business)
            ->whereBetween('date', $bounds)
            ->whereIn('status', Booking::ACTIVE_STATUSES)
            ->with('service:id,name,color')
            ->get(['service_id', 'price'])
            ->groupBy('service_id')
            ->map(fn (Collection $group) => [
                'name'  => $group->first()->service?->name ?? 'Service supprimé',
                'color' => $group->first()->service?->color ?? '#9ca3af',
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values();
    }

    private function topRevenue(Business $business): Collection
    {
        return $this->scope($business)
            ->whereIn('status', self::REVENUE_STATUSES)
            ->with('service:id,name,color')
            ->get(['service_id', 'price'])
            ->groupBy('service_id')
            ->map(fn (Collection $group) => [
                'name'    => $group->first()->service?->name ?? 'Service supprimé',
                'color'   => $group->first()->service?->color ?? '#9ca3af',
                'revenue' => (int) $group->sum('price'),
                'count'   => $group->count(),
            ])
            ->sortByDesc('revenue')
            ->take(5)
            ->values();
    }

    /** @return array<string, int> "08" => 4 */
    private function peakHours(Business $business): array
    {
        return $this->scope($business)
            ->whereIn('status', Booking::ACTIVE_STATUSES)
            ->get(['time_slot'])
            ->countBy(fn (Booking $b) => substr((string) $b->time_slot, 0, 2))
            ->sortKeys()
            ->all();
    }

    /** Monday first, Sunday last: how a merchant reads a week. */
    private function peakDays(Business $business): array
    {
        $counts = $this->scope($business)
            ->whereIn('status', Booking::ACTIVE_STATUSES)
            ->get(['date'])
            ->countBy(fn (Booking $b) => $b->date->dayOfWeekIso);

        $names = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim'];

        return collect($names)
            ->map(fn (string $label, int $iso) => ['day' => $label, 'count' => $counts[$iso] ?? 0])
            ->values()
            ->all();
    }

    /** @return array<string, int> */
    private function statusDistribution(Business $business): array
    {
        $counts = $this->scope($business)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        // Always return every status so the chart legend does not jump around
        // as buckets appear and disappear.
        return collect(Booking::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => (int) ($counts[$status] ?? 0)])
            ->all();
    }

    private function recentActivity(Business $business): Collection
    {
        return $this->scope($business)
            ->with('service:id,name,color')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(fn (Booking $b) => [
                'id'            => $b->id,
                'reference'     => $b->reference,
                'customer_name' => $b->customer_name,
                'service_name'  => $b->service?->name,
                'service_color' => $b->service?->color ?? '#9ca3af',
                'status'        => $b->status,
                'status_label'  => $b->status_label,
                'date'          => $b->date->toDateString(),
                'time_slot'     => $b->time_slot,
                'updated_at'    => $b->updated_at?->diffForHumans(),
            ]);
    }
}
