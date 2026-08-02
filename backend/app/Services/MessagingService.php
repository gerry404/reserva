<?php

namespace App\Services;

use App\Rules\PhoneNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Outbound WhatsApp and SMS.
 *
 * Every provider here is optional. A merchant on the free plan with no gateway
 * configured still gets their booking by email and in the dashboard, so an
 * unconfigured or failing provider is logged and shrugged off — never thrown.
 * The caller is a queued job whose only other job is not to lose the booking.
 */
class MessagingService
{
    public function sendWhatsApp(string $phone, string $message): bool
    {
        return match (config('services.whatsapp.provider')) {
            'twilio' => $this->viaTwilio($phone, $message),
            'meta'   => $this->viaMeta($phone, $message),
            default  => false,
        };
    }

    public function sendSms(string $phone, string $message): bool
    {
        $username = config('services.africastalking.username');
        $apiKey   = config('services.africastalking.api_key');

        if (blank($apiKey)) {
            return false;
        }

        return $this->attempt('africastalking', fn () => Http::asForm()
            ->withHeaders(['apiKey' => $apiKey, 'Accept' => 'application/json'])
            ->timeout(15)
            ->post('https://api.africastalking.com/version1/messaging', [
                'username' => $username,
                'to'       => '+' . PhoneNumber::toE164($phone),
                'message'  => $message,
                'from'     => config('services.africastalking.sender_id'),
            ]));
    }

    private function viaTwilio(string $phone, string $message): bool
    {
        $sid   = config('services.twilio.sid');
        $token = config('services.twilio.token');

        if (blank($sid) || blank($token)) {
            return false;
        }

        return $this->attempt('twilio', fn () => Http::withBasicAuth($sid, $token)
            ->asForm()
            ->timeout(15)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                'From' => config('services.twilio.whatsapp_from'),
                'To'   => 'whatsapp:+' . PhoneNumber::toE164($phone),
                'Body' => $message,
            ]));
    }

    private function viaMeta(string $phone, string $message): bool
    {
        $token   = config('services.meta_wa.token');
        $phoneId = config('services.meta_wa.phone_id');

        if (blank($token) || blank($phoneId)) {
            return false;
        }

        $version = config('services.meta_wa.version', 'v21.0');

        return $this->attempt('meta', fn () => Http::withToken($token)
            ->timeout(15)
            ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to'                => PhoneNumber::toE164($phone),
                'type'              => 'text',
                'text'              => ['body' => $message, 'preview_url' => false],
            ]));
    }

    /**
     * Run a provider call, and report success without ever letting it escape.
     *
     * @param  callable(): \Illuminate\Http\Client\Response  $call
     */
    private function attempt(string $provider, callable $call): bool
    {
        try {
            $response = $call();

            if ($response->successful()) {
                return true;
            }

            Log::warning("Messaging provider [{$provider}] rejected the message", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("Messaging provider [{$provider}] is unreachable", [
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }
}
