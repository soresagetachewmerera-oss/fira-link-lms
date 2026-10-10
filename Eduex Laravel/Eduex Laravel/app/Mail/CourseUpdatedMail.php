<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Course Updated Mail
 *
 * This mail is sent to the instructor when a course is updated.
 *
 * @package App\Mail
 */
class CourseUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Carbon $occurredAt;

    public function __construct(public Course $course, public User $instructor, public ?string $summary = null, ?Carbon $occurredAt = null, public ?string $studentName = null)
    {
        $this->subject('Updates in ' . $course->title);
        $this->occurredAt = $occurredAt ?? now();
    }

    public function build(): self
    {
        return $this->view('emails.notifications.course-updated');
    }
}
