<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly StatsService $stats,
    ) {}

    public function stats(Request $request): JsonResponse
    {
        return response()->json(
            $this->stats->overview($request->user()->business)
        );
    }

    public function upcoming(Request $request): JsonResponse
    {
        $bookings = Booking::query()
            ->forBusiness($request->user()->business->id)
            ->upcoming()
            ->with('service.business', 'business')
            ->limit(10)
            ->get();

        return BookingResource::collection($bookings)->response();
    }

    public function chart(Request $request): JsonResponse
    {
        return response()->json(
            $this->stats->charts($request->user()->business)
        );
    }

    /**
     * Pro-only. The route carries the `plan:pro` middleware, so reaching this
     * method already means the subscription is live.
     */
    public function analytics(Request $request): JsonResponse
    {
        return response()->json(
            $this->stats->analytics($request->user()->business)
        );
    }
}
