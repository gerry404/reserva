<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\PublicBookingResource;
use App\Http\Resources\PublicBusinessResource;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use App\Rules\PhoneNumber;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * The customer-facing booking flow.
 *
 * Nothing here is authenticated, so every route is rate limited (see
 * routes/api.php), every lookup is scoped to a single active business, and
 * responses go through the Public* resources, which deliberately expose less
 * than the merchant-facing ones.
 */
class PublicBookingController extends Controller
{
    /** The calendar never asks for more than a month; refuse to scan a decade. */
    private const MAX_RANGE_DAYS = 62;

    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly BookingService $bookings,
    ) {}

    public function show(string $slug): PublicBusinessResource
    {
        $business = $this->resolveBusiness($slug)->load('services');

        // Prices are rendered in the business currency, so each service needs
        // its parent. We already hold it, and handing it back beats one query per
        // service, and beats eager-loading the same row N times.
        $business->services->each->setRelation('business', $business);

        return new PublicBusinessResource($business);
    }

    /**
     * Bookable start times for one service on one day.
     *
     * The service matters: availability depends on how long it runs, so a day
     * can be wide open for a 30-minute cut and full for a three-hour braid.
     */
    public function availableSlots(Request $request, string $slug): JsonResponse
    {
        $business = $this->resolveBusiness($slug);

        $validated = $request->validate([
            'service_id' => ['required', 'integer'],
            'date'       => ['required', 'date_format:Y-m-d'],
        ]);

        $service = $this->resolveService($business, (int) $validated['service_id']);

        return response()->json([
            'date'  => $validated['date'],
            'slots' => $this->availability->slotsFor($business, $service, $validated['date']),
        ]);
    }

    /**
     * How many slots each day of a range still holds.
     *
     * Lets the calendar grey out full days up front, instead of making the
     * customer discover them one wasted tap at a time.
     */
    public function availability(Request $request, string $slug): JsonResponse
    {
        $business = $this->resolveBusiness($slug);

        $validated = $request->validate([
            'service_id' => ['required', 'integer'],
            'from'       => ['required', 'date_format:Y-m-d'],
            'to'         => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $service = $this->resolveService($business, (int) $validated['service_id']);

        $from = Carbon::parse($validated['from']);
        $to   = Carbon::parse($validated['to']);
        if ($from->diffInDays($to) > self::MAX_RANGE_DAYS) {
            $to = $from->copy()->addDays(self::MAX_RANGE_DAYS);
        }

        return response()->json([
            'days' => $this->availability->openDaysBetween(
                $business,
                $service,
                $from->toDateString(),
                $to->toDateString(),
            ),
        ]);
    }

    public function book(StoreBookingRequest $request, string $slug): JsonResponse
    {
        $business = $this->resolveBusiness($slug);
        $service  = $this->resolveService($business, $request->integer('service_id'));

        // Refusals surface as BookingException and are rendered centrally in
        // bootstrap/app.php with the status the customer should actually see.
        $booking = $this->bookings->create($business, $service, $request->validated());

        return (new PublicBookingResource($booking))
            ->additional(['message' => 'Réservation envoyée !'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Look up a booking from its reference and the phone used to make it.
     *
     * Reference plus phone is the credential; a mismatch on either returns the
     * same 404, so the endpoint cannot be used to probe whether a reference
     * exists.
     */
    public function track(Request $request): JsonResponse|PublicBookingResource
    {
        $booking = $this->findByCredentials($request);

        return $booking
            ? new PublicBookingResource($booking)
            : $this->notFound();
    }

    public function cancelByCustomer(Request $request): JsonResponse
    {
        $booking = $this->findByCredentials($request);

        if (! $booking) {
            return $this->notFound();
        }

        if (! $booking->isCancellable()) {
            return response()->json([
                'message' => 'Cette réservation ne peut plus être annulée. Contactez directement le commerce.',
            ], 422);
        }

        $booking->update(['status' => Booking::STATUS_CANCELLED]);

        return response()->json([
            'message' => 'Votre réservation a bien été annulée.',
            'booking' => new PublicBookingResource($booking->fresh()->load('service.business', 'business')),
        ]);
    }

    // ─── Internals ───────────────────────────────────────────────────────

    private function resolveBusiness(string $slug): Business
    {
        return Business::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /**
     * Scoped through the relation on purpose: a service id belonging to another
     * business must 404 here, not get booked into this one.
     */
    private function resolveService(Business $business, int $serviceId): Service
    {
        $service = $business->services()->findOrFail($serviceId);

        // The parent is already in hand; setting it back saves a query when the
        // resource formats the price in the business currency.
        $service->setRelation('business', $business);

        return $service;
    }

    private function findByCredentials(Request $request): ?Booking
    {
        $validated = $request->validate([
            'reference' => ['required', 'string', 'max:32'],
            'phone'     => ['required', 'string', 'max:32'],
        ]);

        $booking = Booking::query()
            ->with('service.business', 'business')
            ->where('reference', strtoupper(trim($validated['reference'])))
            ->first();

        if (! $booking || ! PhoneNumber::matches($booking->customer_phone, $validated['phone'])) {
            return null;
        }

        return $booking;
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message' => 'Aucune réservation ne correspond à ces informations.',
        ], 404);
    }
}
