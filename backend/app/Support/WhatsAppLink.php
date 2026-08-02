<?php

namespace App\Support;

use App\Models\Booking;
use App\Rules\PhoneNumber;

/**
 * Pre-filled wa.me links.
 *
 * Most merchants on Réserva do not have (and cannot afford) the WhatsApp
 * Business API. The fallback that actually works is a deep link they tap, which
 * opens WhatsApp with the message already written — so these strings are a real
 * product surface, not a convenience.
 */
final class WhatsAppLink
{
    public static function to(?string $phone, string $message): ?string
    {
        $number = PhoneNumber::toE164($phone);

        if ($number === '') {
            return null;
        }

        return 'https://wa.me/' . $number . '?text=' . rawurlencode($message);
    }

    /** Merchant → customer, once a booking is confirmed. */
    public static function confirmation(Booking $booking): ?string
    {
        $business = $booking->business;

        $message = implode("\n", array_filter([
            "Bonjour {$booking->customer_name},",
            '',
            "✅ Votre réservation chez *{$business->name}* est confirmée !",
            '',
            '📋 *Détails*',
            "• Service : " . ($booking->service?->name ?? '—'),
            "• Date : " . $booking->date->locale('fr')->isoFormat('dddd D MMMM YYYY'),
            "• Heure : {$booking->time_slot}",
            "• Référence : {$booking->reference}",
            '',
            'À bientôt ! 🙏',
        ], fn ($line) => $line !== null));

        return self::to($booking->customer_phone, $message);
    }

    /** Merchant → customer, the day before. */
    public static function reminder(Booking $booking): ?string
    {
        $business = $booking->business;

        $message = implode("\n", [
            "Bonjour {$booking->customer_name},",
            '',
            "⏰ Petit rappel : vous avez rendez-vous demain chez *{$business->name}*.",
            '',
            "• Service : " . ($booking->service?->name ?? '—'),
            "• Heure : {$booking->time_slot}",
            "• Référence : {$booking->reference}",
            '',
            'À demain !',
        ]);

        return self::to($booking->customer_phone, $message);
    }
}
