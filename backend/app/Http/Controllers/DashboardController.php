<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $business = $request->user()->business;
        $bid      = $business->id;

        $today = now()->toDateString();

        // Current month bookings
        $currentMonthTotal = Booking::forBusiness($bid)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->count();

        // Last month bookings
        $lastMonthTotal = Booking::forBusiness($bid)
            ->whereYear('date', now()->subMonth()->year)
            ->whereMonth('date', now()->subMonth()->month)
            ->count();

        // Today
        $todayCount = Booking::forBusiness($bid)->today()->count();

        // Pending confirmation
        $pendingCount = Booking::forBusiness($bid)
            ->where('status', 'pending')
            ->count();

        // Revenue this month (confirmed + completed)
        $revenueThisMonth = Booking::forBusiness($bid)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->whereIn('status', ['confirmed', 'completed'])
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->sum('services.price');

        // Revenue last month
        $revenueLastMonth = Booking::forBusiness($bid)
            ->whereYear('date', now()->subMonth()->year)
            ->whereMonth('date', now()->subMonth()->month)
            ->whereIn('status', ['confirmed', 'completed'])
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->sum('services.price');

        // Completion rate this month
        $completedMonth = Booking::forBusiness($bid)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->where('status', 'completed')
            ->count();

        $cancelledMonth = Booking::forBusiness($bid)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->where('status', 'cancelled')
            ->count();

        // Unique clients
        $totalClients = Booking::forBusiness($bid)
            ->distinct('customer_phone')
            ->count('customer_phone');

        $monthlyClients = Booking::forBusiness($bid)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->distinct('customer_phone')
            ->count('customer_phone');

        // Returning clients (booked more than once)
        $returningClients = Booking::forBusiness($bid)
            ->select('customer_phone')
            ->groupBy('customer_phone')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $trend = $lastMonthTotal > 0
            ? round((($currentMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100, 1)
            : ($currentMonthTotal > 0 ? 100 : 0);

        $revenueTrend = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100 : 0);

        return response()->json([
            'today_bookings'     => $todayCount,
            'pending_bookings'   => $pendingCount,
            'monthly_bookings'   => $currentMonthTotal,
            'monthly_trend'      => $trend,
            'revenue_this_month' => $revenueThisMonth,
            'revenue_trend'      => $revenueTrend,
            'completion_rate'    => $currentMonthTotal > 0 ? round(($completedMonth / $currentMonthTotal) * 100) : 0,
            'cancellation_rate'  => $currentMonthTotal > 0 ? round(($cancelledMonth / $currentMonthTotal) * 100) : 0,
            'total_clients'      => $totalClients,
            'monthly_clients'    => $monthlyClients,
            'returning_clients'  => $returningClients,
            'plan'               => $request->user()->plan,
            'plan_limit'         => $request->user()->getMonthlyBookingLimit(),
            'plan_used'          => $currentMonthTotal,
            'has_services'       => $business->services()->count() > 0,
        ]);
    }

    public function upcoming(Request $request): JsonResponse
    {
        $business = $request->user()->business;

        $bookings = Booking::forBusiness($business->id)
            ->upcoming()
            ->with('service')
            ->limit(10)
            ->get();

        return response()->json($bookings);
    }

    public function chart(Request $request): JsonResponse
    {
        $business = $request->user()->business;

        // Last 7 days
        $data = Booking::forBusiness($business->id)
            ->where('date', '>=', now()->subDays(6)->toDateString())
            ->select(DB::raw('date, count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $labels = [];
        $values = [];
        for ($i = 6; $i >= 0; $i--) {
            $day      = now()->subDays($i)->toDateString();
            $labels[] = now()->subDays($i)->locale('fr')->isoFormat('ddd D');
            $values[] = $data[$day] ?? 0;
        }

        // Monthly (last 6 months)
        $monthLabels = [];
        $monthValues = [];
        for ($i = 5; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end   = now()->subMonths($i)->endOfMonth();
            $monthLabels[] = $start->locale('fr')->isoFormat('MMM YY');
            $monthValues[] = Booking::forBusiness($business->id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->count();
        }

        return response()->json([
            'daily'   => ['labels' => $labels, 'values' => $values],
            'monthly' => ['labels' => $monthLabels, 'values' => $monthValues],
        ]);
    }

    public function analytics(Request $request): JsonResponse
    {
        $business = $request->user()->business;
        $bid = $business->id;

        // Top services (by booking count this month)
        $topServices = Booking::forBusiness($bid)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->select('service_id', DB::raw('COUNT(*) as bookings_count'))
            ->groupBy('service_id')
            ->orderByDesc('bookings_count')
            ->limit(5)
            ->with('service:id,name,color,price')
            ->get()
            ->map(fn($b) => [
                'name'     => $b->service?->name ?? 'Supprimé',
                'color'    => $b->service?->color ?? '#9ca3af',
                'price'    => $b->service?->price ?? 0,
                'count'    => $b->bookings_count,
            ]);

        // All-time top services for revenue
        $topRevenue = Booking::forBusiness($bid)
            ->whereIn('status', ['confirmed', 'completed'])
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->select('services.name', 'services.color', DB::raw('SUM(services.price) as revenue'), DB::raw('COUNT(*) as bookings_count'))
            ->groupBy('services.name', 'services.color')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // Peak hours (distribution of bookings by hour)
        $peakHours = Booking::forBusiness($bid)
            ->select(DB::raw("SUBSTRING(time_slot, 1, 2) as hour"), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();

        // Peak days (distribution by weekday)
        $peakDays = Booking::forBusiness($bid)
            ->select(DB::raw('DAYOFWEEK(date) as dow'), DB::raw('COUNT(*) as count'))
            ->groupBy('dow')
            ->orderBy('dow')
            ->pluck('count', 'dow')
            ->toArray();

        $dayNames = [1 => 'Dim', 2 => 'Lun', 3 => 'Mar', 4 => 'Mer', 5 => 'Jeu', 6 => 'Ven', 7 => 'Sam'];
        $peakDaysFormatted = [];
        for ($i = 2; $i <= 7; $i++) { // Mon to Sat first
            $peakDaysFormatted[] = ['day' => $dayNames[$i], 'count' => $peakDays[$i] ?? 0];
        }
        $peakDaysFormatted[] = ['day' => $dayNames[1], 'count' => $peakDays[1] ?? 0]; // Sunday last

        // Recent activity (last 10 status changes)
        $recentActivity = Booking::forBusiness($bid)
            ->with('service:id,name,color')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get()
            ->map(fn($b) => [
                'id'            => $b->id,
                'customer_name' => $b->customer_name,
                'service_name'  => $b->service?->name,
                'service_color' => $b->service?->color ?? '#9ca3af',
                'status'        => $b->status,
                'date'          => $b->date->format('Y-m-d'),
                'time_slot'     => $b->time_slot,
                'reference'     => $b->reference,
                'updated_at'    => $b->updated_at->diffForHumans(),
            ]);

        // Status distribution (all time)
        $statusDistribution = Booking::forBusiness($bid)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'top_services'        => $topServices,
            'top_revenue'         => $topRevenue,
            'peak_hours'          => $peakHours,
            'peak_days'           => $peakDaysFormatted,
            'recent_activity'     => $recentActivity,
            'status_distribution' => $statusDistribution,
        ]);
    }
}
