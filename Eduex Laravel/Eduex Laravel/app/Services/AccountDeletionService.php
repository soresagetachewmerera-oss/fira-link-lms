<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Account Deletion Service
 *
 * This service is responsible for deleting a user's account.
 *
 * @package App\Services
 */
class AccountDeletionService
{
    public function deleteUser(User $user): void
    {
        $profilePhoto = $user->profile_photo;

        DB::transaction(function () use ($user, $profilePhoto) {
            if ($user->isStudent()) {
                $user->lessonProgress()->delete();
                $user->quizAttempts()->delete();
                $user->assignmentSubmissions()->delete();
                $user->eventBookings()->delete();
                $user->enrollments()->delete();
                $user->reviews()->delete();
            } elseif ($user->isInstructor()) {
                $user->courses()->each(function ($course) {
                    if ($course->status !== 'archived') {
                        $course->status = 'archived';
                        $course->deleted_date = now();
                        $course->save();
                    }
                });
                $user->instructedEvents()->detach();
                $user->eventBookings()->delete();
                $user->reviews()->delete();
            }

            $user->accountDeletionRequests()->update(['user_id' => null]);
            $user->delete();

            if ($profilePhoto) {
                DB::afterCommit(function () use ($profilePhoto) {
                    if (Storage::disk('public')->exists($profilePhoto)) {
                        Storage::disk('public')->delete($profilePhoto);
                    }
                });
            }
        });
    }
}
