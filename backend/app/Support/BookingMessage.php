<?php

namespace App\Support;

use App\Models\Booking;

/**
 * The plain-text bodies sent over WhatsApp and SMS.
 *
 * Previously a Notification class whose via() returned 'database', a channel
 * with no table behind it, so calling notify() would have thrown; only its
 * message builder was ever used. It is a formatter, so it is one now.
 *
 * SMS is billed per segment, hence the deliberately terse variants.
 */
final class BookingMessage
{
    /** New booking, addressed to the merchant. */
    public static function forMerchant(Booking $booking): string
    {
        return implode("\n", [
            "📅 *Nouvelle réservation*",
            '',
            "Client : {$booking->customer_name}",
            "Tél : {$booking->customer_phone}",
            'Service : ' . ($booking->service?->name ?? 'Service supprimé'),
            'Date : ' . self::when($booking),
            "Réf : {$booking->reference}",
            '',
            'Confirmez depuis votre tableau de bord : ' . config('app.frontend_url') . '/dashboard/bookings',
        ]);
    }

    /** Same thing, trimmed to fit an SMS or two. */
    public static function forMerchantSms(Booking $booking): string
    {
        return sprintf(
            'Nuvo. Nouvelle reservation : %s, %s, %s. Ref %s.',
            $booking->customer_name,
            $booking->service?->name ?? 'service',
            self::when($booking),
            $booking->reference,
        );
    }

    /** Day-before reminder, addressed to the customer. */
    public static function reminderForCustomer(Booking $booking): string
    {
        return implode("\n", [
            "⏰ *Rappel : {$booking->business->name}*",
            '',
            "Bonjour {$booking->customer_name},",
            "Vous avez rendez-vous demain à {$booking->time_slot}.",
            'Service : ' . ($booking->service?->name ?? 'Service supprimé'),
            "Réf : {$booking->reference}",
        ]);
    }

    private static function when(Booking $booking): string
    {
        return $booking->date->locale('fr')->isoFormat('dddd D MMMM') . ' à ' . $booking->time_slot;
    }
}
