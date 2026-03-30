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
        $thisMonth = now()->format('Y-m');
        $lastMonth = now()->subMonth()->format('Y-m');

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

        // Upcoming (confirmed + pending, today and future)
        $upcomingCount = Booking::forBusiness($bid)->upcoming()->count();

        // Pending confirmation
        $pendingCount = Booking::forBusiness($bid)
            ->where('status', 'pending')
            ->count();

        // Revenue this month (services price * confirmed bookings)
        $revenueThisMonth = Booking::forBusiness($bid)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->whereIn('status', ['confirmed', 'completed'])
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->sum('services.price');

        // Completion rate
        $totalMonth = $currentMonthTotal ?: 1;
        $completedMonth = Booking::forBusiness($bid)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->where('status', 'completed')
            ->count();

        $trend = $lastMonthTotal > 0
            ? round((($currentMonthTotal - $lastMonthTotal) / $lastMonthTotal) * 100, 1)
            : ($currentMonthTotal > 0 ? 100 : 0);

        return response()->json([
            'today_bookings'     => $todayCount,
            'upcoming_bookings'  => $upcomingCount,
            'pending_bookings'   => $pendingCount,
            'monthly_bookings'   => $currentMonthTotal,
            'monthly_trend'      => $trend,
            'revenue_this_month' => $revenueThisMonth,
            'completion_rate'    => round(($completedMonth / $totalMonth) * 100),
            'plan'               => $request->user()->plan,
            'plan_limit'         => $request->user()->getMonthlyBookingLimit(),
            'plan_used'          => $currentMonthTotal,
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

        // Last 7 days booking counts
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

        // Monthly chart (last 6 months)
        $monthly = Booking::forBusiness($business->id)
            ->where('date', '>=', now()->subMonths(5)->startOfMonth())
            ->select(DB::raw("TO_CHAR(date, 'YYYY-MM') as month, count(*) as total"))
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $monthLabels = [];
        $monthValues = [];
        for ($i = 5; $i >= 0; $i--) {
            $m            = now()->subMonths($i)->format('Y-m');
            $monthLabels[] = now()->subMonths($i)->locale('fr')->isoFormat('MMM YY');
            $monthValues[] = $monthly[$m] ?? 0;
        }

        return response()->json([
            'daily'   => ['labels' => $labels, 'values' => $values],
            'monthly' => ['labels' => $monthLabels, 'values' => $monthValues],
        ]);
    }
}
