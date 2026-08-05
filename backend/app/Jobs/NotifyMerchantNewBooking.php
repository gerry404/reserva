<?php

namespace App\Jobs;

use App\Mail\NewBookingNotification;
use App\Models\Booking;
use App\Services\MessagingService;
use App\Support\BookingMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Tell the merchant a booking just came in, over whichever channels they enabled.
 *
 * One channel failing must not stop the others: a merchant with WhatsApp *and*
 * email switched on should still get the email while Twilio is down. Retries are
 * spaced out because the failure mode is almost always a provider outage, and the
 * booking itself is safely stored either way.
 */
class NotifyMerchantNewBooking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [30, 300];

    public function __construct(
        public readonly Booking $booking,
    ) {}

    public function handle(MessagingService $messaging): void
    {
        $booking  = $this->booking->loadMissing('service', 'business.user');
        $business = $booking->business;

        if (! $business) {
            return;
        }

        if ($business->notifications_whatsapp && $business->whatsapp) {
            $messaging->sendWhatsApp($business->whatsapp, BookingMessage::forMerchant($booking));
        }

        if ($business->notifications_sms && $business->phone) {
            $messaging->sendSms($business->phone, BookingMessage::forMerchantSms($booking));
        }

        if ($business->notifications_email && $business->user?->email) {
            $this->sendEmail($booking);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Merchant notification permanently failed', [
            'booking' => $this->booking->reference,
            'error'   => $e->getMessage(),
        ]);
    }

    private function sendEmail(Booking $booking): void
    {
        try {
            Mail::to($booking->business->user->email)
                ->send(new NewBookingNotification($booking, $booking->business));
        } catch (\Throwable $e) {
            Log::error('New-booking email failed', [
                'booking' => $booking->reference,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
