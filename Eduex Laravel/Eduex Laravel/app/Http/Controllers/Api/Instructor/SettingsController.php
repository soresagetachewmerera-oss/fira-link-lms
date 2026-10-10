<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use App\Models\AccountDeletionRequest;
use App\Models\UserNotificationSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationPreferenceService;

/**
 * Settings Controller 
 *
 * This controller handles the settings functionality for the API.
 *
 * @package App\Http\Controllers\Api\Instructor
 */
class SettingsController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService, private NotificationPreferenceService $notificationPreferenceService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Get instructor settings
     */
    public function index()
    {
        $user = Auth::user();
        
        $latestDeletionRequest = $user->accountDeletionRequests()->latest()->first();
        $notificationSettings = $this->resolveNotificationPreferences($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? '',
                    'bio' => $user->bio ?? '',
                    'avatar' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : null,
                    'linkedin' => $user->linkedin ?? '',
                    'twitter' => $user->twitter ?? '',
                    'facebook' => $user->facebook ?? '',
                    'youtube' => $user->youtube ?? '',
                ],
                'latest_deletion_request' => $latestDeletionRequest,
                'notification_settings' => $notificationSettings,
            ]
        ]);
    }

    /**
     * Update instructor profile
     */
    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'linkedin' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'bio' => $request->bio,
                'linkedin' => $request->linkedin,
                'twitter' => $request->twitter,
                'facebook' => $request->facebook,
                'youtube' => $request->youtube,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating profile'
            ], 500);
        }
    }

    /**
     * Update profile photo
     */
    public function updateProfilePhoto(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        try {
            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            
            $user->profile_photo = $this->fileUploadService->uploadProfilePhoto($request->file('photo'));
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile photo updated successfully',
                'photo_url' => asset('storage/' . $user->profile_photo)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while uploading photo'
            ], 500);
        }
    }

    /**
     * Update instructor password
     */
    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'new_password_confirmation' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        try {
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating password'
            ], 500);
        }
    }

    /**
     * Submit account deletion request
     */
    public function deleteAccount(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $hasPending = $user->accountDeletionRequests()
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending account deletion request'
            ], 400);
        }

        AccountDeletionRequest::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'role' => $user->role,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account deletion request submitted for admin approval'
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateNotifications(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $updates = [
            'email_notifications' => $request->boolean('email_notifications'),
            'course_updates' => $request->boolean('course_updates'),
            'review_notifications' => $request->boolean('review_notifications'),
            'marketing_emails' => $request->boolean('marketing_emails'),
        ];

        $this->storeNotificationPreferences($user, $updates);

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully'
        ]);
    }

    private function resolveNotificationPreferences(User $user): array
    {
        $existing = $user->notificationSettings?->preferences ?? [];

        return $this->notificationPreferenceService->mergeForUser($user, $existing);
    }

    private function storeNotificationPreferences(User $user, array $updates): void
    {
        $settings = $user->notificationSettings ?: new UserNotificationSetting(['user_id' => $user->id]);

        $existing = $settings->preferences ?? [];
        $preferences = $this->notificationPreferenceService->mergeForUser($user, $existing, $updates);

        $settings->user_id = $user->id;
        $settings->preferences = $preferences;
        $settings->save();
    }
}
