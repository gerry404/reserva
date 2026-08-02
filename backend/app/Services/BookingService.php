<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Jobs\NotifyMerchantNewBooking;
use App\Mail\BookingReceivedNotification;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Creating a booking, safely.
 *
 * Two customers pressing "confirm" on the same slot at the same instant is the
 * normal case for a popular salon, not an edge case, so the write is guarded
 * three times over:
 *
 *   1. a row lock on the business serialises concurrent bookings for it;
 *   2. availability is re-checked *inside* that lock, never before it;
 *   3. the unique index on slot_key is the last line of defence if a booking
 *      is ever created down some other path.
 */
class BookingService
{
    public function __construct(
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * @param  array{customer_name: string, customer_phone: string, customer_email: ?string, date: string, time_slot: string, notes: ?string}  $data
     *
     * @throws BookingException
     */
    public function create(Business $business, Service $service, array $data): Booking
    {
        $booking = DB::transaction(function () use ($business, $service, $data) {
            // Serialise every booking attempt for this business. Availability
            // read before this line would be a guess; read after it, it holds.
            Business::query()->whereKey($business->id)->lockForUpdate()->first();

            $this->guardQuota($business);

            if (! $this->availability->isBookable($business, $service, $data['date'], $data['time_slot'])) {
                throw BookingException::slotUnavailable();
            }

            return Booking::create([
                'business_id'    => $business->id,
                'service_id'     => $service->id,
                'customer_name'  => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'notes'          => $data['notes'] ?? null,
                'date'           => $data['date'],
                'time_slot'      => $data['time_slot'],
                // Snapshot: editing the service later must not rewrite history.
                'duration'       => $service->duration,
                'price'          => $service->price,
                'status'         => Booking::STATUS_PENDING,
            ]);
        });

        $this->notify($booking->load('service', 'business.user'));

        return $booking;
    }

    /**
     * Free plans are capped per calendar month.
     *
     * Counted on created_at, not on the appointment date: booking next month's
     * diary is still using the product this month. Cancelled bookings are
     * excluded so a run of cancellations cannot lock a merchant out.
     */
    private function guardQuota(Business $business): void
    {
        $limit = $business->user?->monthlyBookingLimit();

        if ($limit === null) {
            return;
        }

        $used = Booking::query()
            ->forBusiness($business->id)
            ->where('bookings.status', '!=', Booking::STATUS_CANCELLED)
            ->whereBetween('bookings.created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        if ($used >= $limit) {
            throw BookingException::quotaReached();
        }
    }

    /**
     * Tell both sides. Notification failures never fail the booking — the slot
     * is already reserved, and a merchant who missed the alert still sees it in
     * the dashboard.
     */
    private function notify(Booking $booking): void
    {
        try {
            NotifyMerchantNewBooking::dispatch($booking);
        } catch (\Throwable $e) {
            Log::error('Merchant notification could not be queued', [
                'booking' => $booking->reference,
                'error'   => $e->getMessage(),
            ]);
        }

        if (! $booking->customer_email) {
            return;
        }

        try {
            Mail::to($booking->customer_email)
                ->queue(new BookingReceivedNotification($booking, $booking->business));
        } catch (\Throwable $e) {
            Log::error('Customer acknowledgement email could not be queued', [
                'booking' => $booking->reference,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
