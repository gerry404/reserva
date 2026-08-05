<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewBookingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public Business $business,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '📅 Nouvelle réservation : ' . $this->booking->customer_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-booking',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
