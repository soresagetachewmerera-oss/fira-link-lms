<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Instructor Course Enrollment Mail
 *
 * This mail is sent to the instructor when a student enrolls in a course.
 *
 * @package App\Mail
 */
class InstructorCourseEnrollmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public Carbon $enrolledAt;

    public function __construct(public Course $course, public User $student, ?Carbon $enrolledAt = null)
    {
        $this->subject('New enrollment in ' . $course->title);
        $this->enrolledAt = $enrolledAt ?? now();
    }

    public function build(): self
    {
        return $this->view('emails.notifications.instructor-course-enrollment');
    }
}
