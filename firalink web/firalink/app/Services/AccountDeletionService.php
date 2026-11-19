<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        if ($user->isAdmin()) {
            throw new \RuntimeException('Cannot delete administrator accounts via deletion service.');
        }

        $profilePhoto = $user->profile_photo;

        DB::transaction(function () use ($user, $profilePhoto) {
            $user->tokens()->delete();
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

            // Clean up device tokens
            if (Schema::hasTable('device_tokens')) {
                DB::table('device_tokens')->where('user_id', $user->id)->delete();
            }

            // Remove organization memberships
            if (Schema::hasTable('organization_memberships')) {
                DB::table('organization_memberships')->where('user_id', $user->id)->delete();
            }

            // Clean up private verification files uploaded by this user
            if (Schema::hasTable('organization_verification_documents')) {
                $docs = DB::table('organization_verification_documents')
                    ->where('uploaded_by', $user->id)
                    ->get();
                foreach ($docs as $doc) {
                    if (!empty($doc->file_path)) {
                        $path = $doc->file_path;
                        DB::afterCommit(function () use ($path) {
                            if (Storage::disk('verification')->exists($path)) {
                                Storage::disk('verification')->delete($path);
                            }
                        });
                    }
                }
                DB::table('organization_verification_documents')
                    ->where('uploaded_by', $user->id)
                    ->update(['uploaded_by' => null]);
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
