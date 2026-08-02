<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A booking was refused for a reason the customer can act on.
 *
 * Carries the HTTP status and a French, end-user-facing message so the booking
 * flow never has to translate exception classes into responses.
 */
class BookingException extends RuntimeException
{
    private function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }

    /** The slot was taken between rendering the page and pressing "confirm". */
    public static function slotUnavailable(): self
    {
        return new self(
            'Ce créneau vient d\'être réservé. Choisissez-en un autre.',
            409,
        );
    }

    /** The requested time is outside opening hours, too soon, or too far out. */
    public static function slotNotOffered(): self
    {
        return new self(
            'Ce créneau n\'est pas disponible à la réservation.',
            422,
        );
    }

    /** The merchant's free plan is out of bookings for the month. */
    public static function quotaReached(): self
    {
        return new self(
            'Ce commerce a atteint sa limite de réservations pour ce mois-ci. Contactez-le directement.',
            429,
        );
    }
}
