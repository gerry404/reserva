<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class BookingStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Booking $booking,
        public readonly string  $event = 'new' // new | confirmed | reminder
    ) {}

    public function via(object $notifiable): array
    {
        return ['database']; // Extend with 'mail', 'vonage' (SMS) etc.
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'reference'  => $this->booking->reference,
            'event'      => $this->event,
            'message'    => $this->buildMessage(),
        ];
    }

    /**
     * Build WhatsApp/SMS message text.
     */
    public function buildMessage(): string
    {
        $b = $this->booking;
        $date = $b->date->locale('fr')->isoFormat('dddd D MMMM');

        return match($this->event) {
            'new' =>
                "📅 *Nouvelle réservation — {$b->business->name}*\n\n" .
                "Client : {$b->customer_name}\n" .
                "Tél : {$b->customer_phone}\n" .
                "Service : {$b->service?->name}\n" .
                "Date : {$date} à {$b->time_slot}\n" .
                "Réf : {$b->reference}\n\n" .
                "Connectez-vous pour confirmer.",

            'confirmed' =>
                "✅ *Réservation confirmée — {$b->business->name}*\n\n" .
                "Bonjour {$b->customer_name},\n" .
                "Votre réservation est confirmée !\n" .
                "📅 {$date} à {$b->time_slot}\n" .
                "Service : {$b->service?->name}\n" .
                "Réf : {$b->reference}",

            'reminder' =>
                "⏰ *Rappel de réservation — {$b->business->name}*\n\n" .
                "Bonjour {$b->customer_name},\n" .
                "Rappel : demain à {$b->time_slot}\n" .
                "Service : {$b->service?->name}\n" .
                "Réf : {$b->reference}",

            default => "Notification de réservation #{$b->reference}",
        };
    }
}
