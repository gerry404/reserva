<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The single source of truth for "can this be booked?".
 *
 * Every rule about opening hours, lead time and collisions lives here — the
 * controllers only ask questions. A slot is offered only when all four hold:
 *
 *   1. the business is open that weekday;
 *   2. the *whole* service fits before closing time (a 3h service cannot start
 *      at 17:00 in a shop that shuts at 18:00);
 *   3. the start is at least `booking_notice` minutes away, measured on the
 *      business's own clock;
 *   4. the interval [start, start + duration) overlaps no live booking.
 *
 * All arithmetic happens in the business timezone. A salon's 10:00 is 10:00
 * where the salon stands, whatever the server or the customer's phone thinks.
 */
class AvailabilityService
{
    /** Never scan more than this far ahead, whatever the caller asks for. */
    public const MAX_HORIZON_DAYS = 120;

    private const DAYS_FR = [
        Carbon::SUNDAY    => 'dimanche',
        Carbon::MONDAY    => 'lundi',
        Carbon::TUESDAY   => 'mardi',
        Carbon::WEDNESDAY => 'mercredi',
        Carbon::THURSDAY  => 'jeudi',
        Carbon::FRIDAY    => 'vendredi',
        Carbon::SATURDAY  => 'samedi',
    ];

    /**
     * Bookable start times for one service on one day, as "HH:MM" strings.
     *
     * @return list<string>
     */
    public function slotsFor(Business $business, Service $service, string $date): array
    {
        $day = $this->businessDate($business, $date);

        $hours = $this->openingHours($business, $day);
        if ($hours === null) {
            return [];
        }

        [$opensAt, $closesAt] = $hours;

        $earliestStart = $this->earliestStart($business);
        $taken         = $this->bookedIntervals($business, $day);
        $duration      = max(1, (int) $service->duration);
        $step          = max(5, (int) $business->slot_duration);

        $slots  = [];
        $cursor = $opensAt->copy();

        while ($cursor->lessThan($closesAt)) {
            $start = $cursor->copy();
            $end   = $start->copy()->addMinutes($duration);
            $cursor->addMinutes($step);

            // Rule 2 — the service must finish before the shutters come down.
            if ($end->greaterThan($closesAt)) {
                continue;
            }

            // Rule 3 — respect the merchant's lead time.
            if ($start->lessThan($earliestStart)) {
                continue;
            }

            // Rule 4 — no collision with anything already on the books.
            if ($this->collides($start, $end, $taken)) {
                continue;
            }

            $slots[] = $start->format('H:i');
        }

        return $slots;
    }

    /**
     * Whether one precise start is bookable. Same rules as slotsFor(), asked
     * about a single candidate — used to re-check at submission time.
     */
    public function isBookable(Business $business, Service $service, string $date, string $time): bool
    {
        return in_array($time, $this->slotsFor($business, $service, $date), true);
    }

    /**
     * How many slots each day of a range still has, keyed by Y-m-d.
     *
     * Lets the customer see which days are full *before* clicking one, instead
     * of discovering it after a round-trip.
     *
     * @return array<string, int>
     */
    public function openDaysBetween(Business $business, Service $service, string $from, string $to): array
    {
        $tz     = $this->timezone($business);
        $cursor = Carbon::parse($from, $tz)->startOfDay();
        $last   = Carbon::parse($to, $tz)->startOfDay();

        // Never look further back than today, nor beyond the horizon.
        $today = Carbon::now($tz)->startOfDay();
        if ($cursor->lessThan($today)) {
            $cursor = $today->copy();
        }
        $maxDay = $today->copy()->addDays(self::MAX_HORIZON_DAYS);
        if ($last->greaterThan($maxDay)) {
            $last = $maxDay;
        }

        $counts = [];
        while ($cursor->lessThanOrEqualTo($last)) {
            $key          = $cursor->toDateString();
            $counts[$key] = count($this->slotsFor($business, $service, $key));
            $cursor->addDay();
        }

        return $counts;
    }

    /**
     * Resolve the interval a booking would occupy, in the business timezone.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function intervalFor(Business $business, Service $service, string $date, string $time): array
    {
        $start = Carbon::parse($date . ' ' . substr($time, 0, 5), $this->timezone($business));

        return [$start, $start->copy()->addMinutes(max(1, (int) $service->duration))];
    }

    // ─── Internals ───────────────────────────────────────────────────────

    public function timezone(Business $business): string
    {
        return $business->timezone ?: config('app.timezone');
    }

    private function businessDate(Business $business, string $date): Carbon
    {
        return Carbon::parse($date, $this->timezone($business))->startOfDay();
    }

    /**
     * Opening and closing moments for a given day, or null when closed.
     *
     * Defensive on purpose: working_hours is merchant-edited JSON, so a missing
     * day, a missing bound or a close-before-open row must read as "closed"
     * rather than throw or emit a bizarre slot list.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    private function openingHours(Business $business, Carbon $day): ?array
    {
        $hours = $business->working_hours[self::DAYS_FR[$day->dayOfWeek]] ?? null;

        if (! is_array($hours) || ! ($hours['is_open'] ?? false)) {
            return null;
        }

        $open  = $this->parseClock($hours['open'] ?? null);
        $close = $this->parseClock($hours['close'] ?? null);

        if ($open === null || $close === null) {
            return null;
        }

        $opensAt  = $day->copy()->setTimeFromTimeString($open);
        $closesAt = $day->copy()->setTimeFromTimeString($close);

        return $closesAt->greaterThan($opensAt) ? [$opensAt, $closesAt] : null;
    }

    private function parseClock(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^([01]\d|2[0-3]):([0-5]\d)/', $value, $m)) {
            return null;
        }

        return $m[0];
    }

    /** The first moment a customer is allowed to book, on the business clock. */
    private function earliestStart(Business $business): Carbon
    {
        return Carbon::now($this->timezone($business))
            ->addMinutes(max(0, (int) $business->booking_notice));
    }

    /**
     * Live bookings touching a day, as [start, end] pairs.
     *
     * starts_at/ends_at hold *business-local wall time* (see the class docblock)
     * but Eloquent hands them back tagged with the app timezone. Comparing those
     * directly against slots built in the business timezone would silently shift
     * every business outside Africa/Douala by its UTC offset, so we re-anchor the
     * wall time into the business timezone here — one place, once.
     *
     * The window is widened by a day on each side so a booking that started late
     * the previous evening and runs past midnight is still seen.
     *
     * @return Collection<int, array{0: Carbon, 1: Carbon}>
     */
    private function bookedIntervals(Business $business, Carbon $day): Collection
    {
        $tz = $this->timezone($business);

        return Booking::query()
            ->forBusiness($business->id)
            ->active()
            ->whereBetween('starts_at', [
                $day->copy()->subDay(),
                $day->copy()->addDays(2),
            ])
            ->get(['starts_at', 'ends_at'])
            ->map(fn (Booking $b) => [
                Carbon::parse($b->starts_at->format('Y-m-d H:i:s'), $tz),
                Carbon::parse($b->ends_at->format('Y-m-d H:i:s'), $tz),
            ]);
    }

    /**
     * @param  Collection<int, array{0: Carbon, 1: Carbon}>  $intervals
     */
    private function collides(Carbon $start, Carbon $end, Collection $intervals): bool
    {
        foreach ($intervals as [$bookedStart, $bookedEnd]) {
            if ($start->lessThan($bookedEnd) && $end->greaterThan($bookedStart)) {
                return true;
            }
        }

        return false;
    }
}
