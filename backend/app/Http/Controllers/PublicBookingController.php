<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicBookingController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)
            ->where('is_active', true)
            ->with('services')
            ->firstOrFail();

        return response()->json([
            'id'            => $business->id,
            'name'          => $business->name,
            'slug'          => $business->slug,
            'description'   => $business->description,
            'category'      => $business->category,
            'city'          => $business->city,
            'country'       => $business->country,
            'logo'          => $business->logo ? asset('storage/' . $business->logo) : null,
            'cover_image'   => $business->cover_image ? asset('storage/' . $business->cover_image) : null,
            'working_hours' => $business->working_hours,
            'slot_duration' => $business->slot_duration,
            'booking_notice' => $business->booking_notice,
            'accent_color'  => $business->accent_color,
            'services'      => $business->services->map(fn($s) => [
                'id'                 => $s->id,
                'name'               => $s->name,
                'description'        => $s->description,
                'duration'           => $s->duration,
                'price'              => $s->price,
                'formatted_price'    => $s->formatted_price,
                'formatted_duration' => $s->formatted_duration,
                'color'              => $s->color,
                'category'           => $s->category,
                'images'             => $s->images,
            ]),
        ]);
    }

    public function availableSlots(Request $request, string $slug): JsonResponse
    {
        $request->validate(['date' => 'required|date|after_or_equal:today']);

        $business = Business::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $date       = \Carbon\Carbon::parse($request->date);
        $dayFr      = $this->dayToFrench($date->dayOfWeek);
        $workHours  = $business->working_hours[$dayFr] ?? null;

        if (!$workHours || !$workHours['is_open']) {
            return response()->json(['slots' => [], 'message' => 'Fermé ce jour.']);
        }

        $slots         = $this->generateSlots($workHours['open'], $workHours['close'], $business->slot_duration);
        $booked        = Booking::where('business_id', $business->id)
            ->where('date', $request->date)
            ->whereNotIn('status', ['cancelled'])
            ->pluck('time_slot')
            ->toArray();

        $noticeMinutes = $business->booking_notice ?? 60;
        $minTime       = now()->addMinutes($noticeMinutes);

        $available = array_filter($slots, function ($slot) use ($booked, $date, $minTime) {
            if (in_array($slot, $booked)) return false;
            // If today, filter past slots
            if ($date->isToday()) {
                $slotTime = \Carbon\Carbon::parse($date->format('Y-m-d') . ' ' . $slot);
                if ($slotTime->lessThan($minTime)) return false;
            }
            return true;
        });

        return response()->json(['slots' => array_values($available)]);
    }

    public function book(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'service_id'     => 'required|exists:services,id',
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'date'           => 'required|date|after_or_equal:today',
            'time_slot'      => 'required|string|max:5',
            'notes'          => 'nullable|string|max:500',
        ]);

        // Check service belongs to business
        $service = $business->services()->findOrFail($validated['service_id']);

        // Check slot availability
        $conflict = Booking::where('business_id', $business->id)
            ->where('date', $validated['date'])
            ->where('time_slot', $validated['time_slot'])
            ->whereNotIn('status', ['cancelled'])
            ->exists();

        if ($conflict) {
            return response()->json(['message' => 'Ce créneau n\'est plus disponible.'], 409);
        }

        // Check monthly limit for free plan
        $user      = $business->user;
        $thisMonth = now()->format('Y-m');
        $used      = Booking::forBusiness($business->id)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->count();

        if ($used >= $user->getMonthlyBookingLimit()) {
            return response()->json(['message' => 'Le commerce a atteint sa limite mensuelle de réservations.'], 429);
        }

        $booking = Booking::create(array_merge($validated, [
            'business_id' => $business->id,
            'status'      => 'pending',
        ]));

        // Send notification to merchant
        try {
            dispatch(new \App\Jobs\NotifyMerchantNewBooking($booking->load('service', 'business')));
        } catch (\Exception $e) {
            \Log::error('Booking notification failed: ' . $e->getMessage());
        }

        return response()->json([
            'message'   => 'Réservation créée avec succès !',
            'booking'   => [
                'reference'      => $booking->reference,
                'customer_name'  => $booking->customer_name,
                'service'        => $service->name,
                'date'           => $booking->date->locale('fr')->isoFormat('dddd D MMMM YYYY'),
                'time'           => $booking->time_slot,
                'status'         => $booking->status,
                'business_name'  => $business->name,
                'business_phone' => $business->whatsapp ?? $business->phone,
            ],
        ], 201);
    }

    /**
     * Track a booking by reference and phone.
     */
    public function track(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => 'required|string',
            'phone'     => 'required|string',
        ]);

        $booking = Booking::with('service', 'business')
            ->where('reference', $validated['reference'])
            ->where('customer_phone', $validated['phone'])
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'Aucune réservation trouvée avec ces informations.'], 404);
        }

        return response()->json([
            'reference'      => $booking->reference,
            'customer_name'  => $booking->customer_name,
            'service'        => $booking->service?->name,
            'business_name'  => $booking->business->name,
            'business_phone' => $booking->business->whatsapp ?? $booking->business->phone,
            'date'           => $booking->date->locale('fr')->isoFormat('dddd D MMMM YYYY'),
            'time'           => $booking->time_slot,
            'status'         => $booking->status,
            'status_label'   => $booking->status_label,
            'can_cancel'     => in_array($booking->status, ['pending', 'confirmed']) && $booking->date->isFuture(),
        ]);
    }

    /**
     * Cancel a booking by the customer.
     */
    public function cancelByCustomer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference' => 'required|string',
            'phone'     => 'required|string',
        ]);

        $booking = Booking::where('reference', $validated['reference'])
            ->where('customer_phone', $validated['phone'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('date', '>=', now()->toDateString())
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'Impossible d\'annuler cette réservation.'], 404);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Votre réservation a été annulée.']);
    }

    private function generateSlots(string $open, string $close, int $duration): array
    {
        $slots   = [];
        $current = \Carbon\Carbon::parse($open);
        $end     = \Carbon\Carbon::parse($close);

        while ($current->lessThan($end)) {
            $slots[] = $current->format('H:i');
            $current->addMinutes($duration);
        }

        return $slots;
    }

    private function dayToFrench(int $dayOfWeek): string
    {
        return match($dayOfWeek) {
            1 => 'lundi',
            2 => 'mardi',
            3 => 'mercredi',
            4 => 'jeudi',
            5 => 'vendredi',
            6 => 'samedi',
            0 => 'dimanche',
        };
    }
}
