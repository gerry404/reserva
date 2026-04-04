<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Notifications\BookingStatusNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewBookingNotification;

class NotifyMerchantNewBooking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Booking $booking) {}

    public function handle(): void
    {
        $business = $this->booking->business;
        $user     = $business->user;

        $notification = new BookingStatusNotification($this->booking, 'new');
        $message      = $notification->buildMessage();

        // WhatsApp via Twilio
        if ($business->notifications_whatsapp && $business->whatsapp) {
            $this->sendWhatsApp($business->whatsapp, $message);
        }

        // SMS via Africa's Talking
        if ($business->notifications_sms && $business->phone) {
            $this->sendSms($business->phone, strip_tags($message));
        }

        // Email
        if ($business->notifications_email && $user->email) {
            $this->sendEmail($user->email, $this->booking, $business);
        }
    }

    private function sendWhatsApp(string $phone, string $message): void
    {
        $provider = config('services.whatsapp.provider', 'twilio');

        if ($provider === 'twilio') {
            $this->sendViaTwilio($phone, $message);
        } elseif ($provider === 'meta') {
            $this->sendViaMeta($phone, $message);
        }
    }

    private function sendViaTwilio(string $phone, string $message): void
    {
        try {
            $sid   = config('services.twilio.sid');
            $token = config('services.twilio.token');
            $from  = config('services.twilio.whatsapp_from');

            if (!$sid || !$token) return;

            Http::withBasicAuth($sid, $token)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To'   => 'whatsapp:+' . ltrim($phone, '+'),
                    'Body' => $message,
                ]);
        } catch (\Exception $e) {
            Log::error('Twilio WhatsApp error: ' . $e->getMessage());
        }
    }

    private function sendViaMeta(string $phone, string $message): void
    {
        try {
            $token   = config('services.meta_wa.token');
            $phoneId = config('services.meta_wa.phone_id');
            $version = config('services.meta_wa.version', 'v18.0');

            if (!$token || !$phoneId) return;

            Http::withToken($token)
                ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => ltrim($phone, '+'),
                    'type'              => 'text',
                    'text'              => ['body' => $message],
                ]);
        } catch (\Exception $e) {
            Log::error('Meta WhatsApp error: ' . $e->getMessage());
        }
    }

    private function sendEmail(string $email, Booking $booking, $business): void
    {
        try {
            Mail::to($email)->send(new NewBookingNotification($booking, $business));
        } catch (\Exception $e) {
            Log::error('Booking email notification error: ' . $e->getMessage());
        }
    }

    private function sendSms(string $phone, string $message): void
    {
        try {
            $username = config('services.africastalking.username');
            $apiKey   = config('services.africastalking.api_key');
            $sender   = config('services.africastalking.sender_id');

            if (!$username || !$apiKey) return;

            Http::withHeaders(['apiKey' => $apiKey, 'Accept' => 'application/json'])
                ->asForm()
                ->post('https://api.africastalking.com/version1/messaging', [
                    'username' => $username,
                    'to'       => $phone,
                    'message'  => $message,
                    'from'     => $sender,
                ]);
        } catch (\Exception $e) {
            Log::error('Africa\'s Talking SMS error: ' . $e->getMessage());
        }
    }
}
