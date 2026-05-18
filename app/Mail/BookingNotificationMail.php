<?php

namespace App\Mail;

use App\Models\Activity;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email notification for booking status updates.
 *
 * Sends booking confirmation, cancellation, and other status notifications.
 */
class BookingNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $customerName,
        public string $activityTitle,
        public string $bookingStatus,
        public string $notificationType = 'confirmation'
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subjects = [
            'confirmation' => __('Trip Has Been Booked'),
            'payment_completed' => __('Booking Payment Completed'),
            'cancellation' => __('Booking Cancelled'),
            'waiting_list' => __('Added to Waiting List'),
            'no_seats' => __('No Available Seats'),
            'trip_completed' => __('Trip Completed'),
        ];

        return new Envelope(
            subject: $subjects[$this->notificationType] ?? __('Booking Update'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-notification',
            with: [
                'customerName' => $this->customerName,
                'activityTitle' => $this->activityTitle,
                'bookingStatus' => $this->bookingStatus,
                'notificationType' => $this->notificationType,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
