<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Mail\BookingReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';
    protected $description = 'Send reminder notifications for tomorrow\'s bookings';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $bookings = Booking::with(['business.user', 'service'])
            ->where('date', $tomorrow)
            ->where('status', 'confirmed')
            ->where('reminder_sent', false)
            ->get();

        $sent = 0;

        foreach ($bookings as $booking) {
            // Email reminder to customer
            if ($booking->customer_email) {
                try {
                    Mail::to($booking->customer_email)
                        ->send(new BookingReminderNotification($booking, $booking->business));
                    $sent++;
                } catch (\Exception $e) {
                    Log::error("Reminder email failed for booking {$booking->reference}: {$e->getMessage()}");
                }
            }

            $booking->update(['reminder_sent' => true]);
        }

        $this->info("Sent {$sent} reminders for {$bookings->count()} bookings on {$tomorrow}.");

        return self::SUCCESS;
    }
}
