<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Event Booking Bulk Mail
 *
 * This mail is sent to the event attendees when a new event is created.
 *
 * @package App\Mail
 */
class EventBookingBulkMail extends Mailable
{
    use Queueable, SerializesModels;

    public Event $event;
    public EventBooking $booking;
    public string $messageBody;

    public function __construct(
        Event $event,
        public string $mailSubject,
        string $body,
        EventBooking $booking
    ) {
        $this->event = $event;
        $this->booking = $booking;
        $this->messageBody = $body;
    }

    public function build(): self
    {
        return $this
            ->subject($this->mailSubject)
            ->view('emails.event-booking-bulk')
            ->with([
                'event' => $this->event,
                'booking' => $this->booking,
                'bodyContent' => $this->messageBody,
            ]);
    }
}

