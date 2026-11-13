<?php

namespace App\Policies;

use App\Models\User;

/**
 * Enrollment Policy
 *
 * This policy determines the actions that can be performed on an enrollment.
 *
 * @package App\Policies
 */
class EnrollmentPolicy
{
    /**
     * Determine if the user can enroll in the course.
     */
    public function enroll(User $user, $course): bool
    {
        return $user->isStudent();
    }

    /**
     * Determine if the user can unenroll from the course.
     */
    public function unenroll(User $user, $course): bool
    {
        return $user->isStudent() && $user->enrollments()->where('course_id', $course->id)->exists();
    }
}

