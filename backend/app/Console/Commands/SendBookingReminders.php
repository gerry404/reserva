<?php

namespace App\Console\Commands;

use App\Mail\BookingReminderNotification;
use App\Models\Booking;
use App\Services\MessagingService;
use App\Support\BookingMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Remind customers about tomorrow's appointments.
 *
 * Reminders used to go out by email only — on a product whose customers give a
 * phone number and leave the optional email blank, so most of them reached
 * nobody. WhatsApp is the primary channel now, with email as a supplement.
 *
 * `reminder_sent` is written per booking as we go, which makes the command safe
 * to re-run: a crash halfway through resumes rather than messaging everyone it
 * already reached a second time.
 */
class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders {--dry-run : List what would be sent without sending it}';

    protected $description = 'Send reminders for tomorrow\'s confirmed bookings';

    public function handle(MessagingService $messaging): int
    {
        $dryRun  = (bool) $this->option('dry-run');
        $due     = 0;
        $reached = 0;

        $this->dueTomorrow()->chunkById(100, function ($bookings) use ($messaging, $dryRun, &$due, &$reached) {
            foreach ($bookings as $booking) {
                // Each business keeps its own clock, so "tomorrow" is decided
                // per booking rather than against the server's calendar.
                if (! $this->isTomorrowThere($booking)) {
                    continue;
                }

                $due++;

                if ($dryRun) {
                    $this->line("  → {$booking->reference}  {$booking->customer_name}  {$booking->time_slot}");

                    continue;
                }

                if ($this->remind($booking, $messaging)) {
                    $reached++;
                }

                $booking->forceFill(['reminder_sent' => true])->save();
            }
        });

        $this->info($dryRun
            ? "{$due} rappel(s) seraient envoyés."
            : "{$due} réservation(s) traitée(s), {$reached} client(s) joint(s).");

        return self::SUCCESS;
    }

    /**
     * Candidates: confirmed, not yet reminded, starting within the next two
     * days. The window is deliberately wide — the exact, timezone-aware
     * "tomorrow" test happens per booking.
     */
    private function dueTomorrow()
    {
        return Booking::query()
            ->with(['business.user', 'service'])
            ->where('status', Booking::STATUS_CONFIRMED)
            ->where('reminder_sent', false)
            ->whereBetween('starts_at', [now()->subDay(), now()->addDays(2)]);
    }

    private function isTomorrowThere(Booking $booking): bool
    {
        $tz = $booking->business?->timezone ?: config('app.timezone');

        return $booking->date->toDateString() === now($tz)->addDay()->toDateString();
    }

    /** @return bool Whether at least one channel accepted the message. */
    private function remind(Booking $booking, MessagingService $messaging): bool
    {
        $reached = false;

        if ($booking->customer_phone) {
            $reached = $messaging->sendWhatsApp(
                $booking->customer_phone,
                BookingMessage::reminderForCustomer($booking),
            );
        }

        if ($booking->customer_email) {
            try {
                Mail::to($booking->customer_email)
                    ->send(new BookingReminderNotification($booking, $booking->business));
                $reached = true;
            } catch (\Throwable $e) {
                Log::error('Reminder email failed', [
                    'booking' => $booking->reference,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        return $reached;
    }
}
