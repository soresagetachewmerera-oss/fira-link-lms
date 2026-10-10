<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(
        private NotificationPreferenceService $notificationPreferenceService
    ) {
    }

    /**
     * Get user notification settings.
     */
    public function getNotificationSettings(Request $request)
    {
        $user = $request->user();
        $preferences = $user->notificationSettings?->preferences ?? [];
        $merged = $this->notificationPreferenceService->mergeForUser($user, $preferences);

        return response()->json([
            'success' => true,
            'data' => $merged
        ]);
    }

    /**
     * Update user notification settings.
     */
    public function updateNotificationSettings(Request $request)
    {
        $user = $request->user();

        $updates = [
            'email_notifications' => $request->boolean('email_notifications'),
            'course_reminders' => $request->boolean('course_reminders'),
            'progress_reports' => $request->boolean('progress_reports'),
            'marketing_emails' => $request->boolean('marketing_emails'),
        ];

        $settings = $user->notificationSettings ?: new UserNotificationSetting(['user_id' => $user->id]);

        $existing = $settings->preferences ?? [];
        $preferences = $this->notificationPreferenceService->mergeForUser($user, $existing, $updates);

        $settings->user_id = $user->id;
        $settings->preferences = $preferences;
        $settings->save();

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated.',
            'data' => $preferences
        ]);
    }
    /**
     * Update user profile information.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:500',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'bio' => $request->bio,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => $user
        ]);
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!\Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'The provided password does not match your current password.'
            ], 422);
        }

        $user->update([
            'password' => \Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.'
        ]);
    }

    /**
     * Delete user account.
     */
    /**
     * Request account deletion.
     */
    public function requestDeleteAccount(Request $request)
    {
        $user = $request->user();

        // Check if there is already a pending request
        $hasPending = $user->accountDeletionRequests()
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending account deletion request.'
            ], 400);
        }

        \App\Models\AccountDeletionRequest::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'role' => $user->role,
            'status' => 'pending',
            'reason' => $request->reason, // Optional reason
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your account deletion request has been submitted for admin approval.'
        ]);
    }

    /**
     * Get account deletion request status.
     */
    public function getDeleteRequestStatus(Request $request)
    {
        $user = $request->user();
        $latestRequest = $user->accountDeletionRequests()->latest()->first();

        return response()->json([
            'success' => true,
            'data' => $latestRequest
        ]);
    }

    /**
     * Update user profile photo.
     */
    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->profile_photo) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo);
            }

            $path = $request->file('photo')->store('profile-photos', 'public');
            $user->update(['profile_photo' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Profile photo updated successfully.',
                'photo_url' => asset('storage/' . $path),
                'user' => $user
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No photo uploaded.'
        ], 400);
    }
}
