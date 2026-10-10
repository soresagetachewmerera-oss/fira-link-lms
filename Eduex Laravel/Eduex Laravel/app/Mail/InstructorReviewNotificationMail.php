<?php

namespace App\Mail;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Instructor Review Notification Mail
 *
 * This mail is sent to the instructor when a new review is received.
 *
 * @package App\Mail
 */
class InstructorReviewNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Review $review)
    {
        $courseTitle = $review->course?->title ?? 'Your course';
        $this->subject('New review on ' . $courseTitle);
    }

    public function build(): self
    {
        return $this->view('emails.notifications.instructor-review-notification');
    }
}
