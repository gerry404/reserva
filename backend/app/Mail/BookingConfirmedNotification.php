<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public Business $business,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ Réservation confirmée — ' . $this->business->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-confirmed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
