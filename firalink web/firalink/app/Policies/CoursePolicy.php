<?php

namespace App\Policies;

use App\Models\User;

/**
 * Course Policy
 *
 * This policy determines the actions that can be performed on a course.
 *
 * @package App\Policies
 */
class CoursePolicy
{
    /**
     * Determine if the user can create courses.
     */
    public function create(User $user): bool
    {
        return $user->isApprovedInstructor();
    }

    /**
     * Determine if the user can update the course.
     */
    public function update(User $user, $course): bool
    {
        return $user->isApprovedInstructor() && $course->instructor_id == $user->id;
    }

    /**
     * Determine if the user can delete the course.
     */
    public function delete(User $user, $course): bool
    {
        return $user->isAdmin() || ($user->isApprovedInstructor() && $course->instructor_id == $user->id);
    }

    /**
     * Determine if the user can view the course.
     */
    public function view(User $user, $course): bool
    {
        return true;
    }
}

