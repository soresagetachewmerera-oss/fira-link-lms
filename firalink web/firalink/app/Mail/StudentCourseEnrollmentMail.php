<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Student Course Enrollment Mail
 *
 * This mail is sent to the student when they enroll in a course.
 *
 * @package App\Mail
 */
class StudentCourseEnrollmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public Carbon $enrolledAt;

    public function __construct(public Course $course, public User $student, ?Carbon $enrolledAt = null)
    {
        $this->subject('You enrolled in ' . $course->title);
        $this->enrolledAt = $enrolledAt ?? now();
    }

    public function build(): self
    {
        return $this->view('emails.notifications.student-course-enrollment');
    }
}
