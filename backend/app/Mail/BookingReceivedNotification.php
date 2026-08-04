<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the customer the moment a booking is made.
 *
 * Distinct from BookingConfirmedNotification, which goes out when the merchant
 * accepts. Until now nothing at all reached the customer at this point: they
 * pressed "confirm" and left with a reference on a screen they were about to
 * close. This is the written trace of what they asked for, and the link they
 * need to check or cancel it.
 */
class BookingReceivedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public Business $business,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre demande de réservation chez ' . $this->business->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-received',
            with: [
                'trackUrl' => config('app.frontend_url') . '/track?' . http_build_query([
                    'ref' => $this->booking->reference,
                ]),
            ],
        );
    }
}
