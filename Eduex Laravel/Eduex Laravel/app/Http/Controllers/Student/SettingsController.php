<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use App\Models\AccountDeletionRequest;
use App\Models\UserNotificationSetting;
use App\Services\NotificationPreferenceService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Settings Controller
 *
 * This controller handles the settings functionality.
 *
 * @package App\Http\Controllers\Student
 */
class SettingsController extends Controller
{
    protected $fileUploadService;

    protected NotificationPreferenceService $notificationPreferenceService;

    public function __construct(FileUploadService $fileUploadService, NotificationPreferenceService $notificationPreferenceService)
    {
        $this->fileUploadService = $fileUploadService;
        $this->notificationPreferenceService = $notificationPreferenceService;
    }

    /**
     * Display the settings page
     */
    public function show()
    {
        $user = $this->currentUser();
        $latestDeletionRequest = $user->accountDeletionRequests()->latest()->first();
        $notificationSettings = $this->resolveNotificationPreferences($user);

        return view('student.settings', compact('user', 'latestDeletionRequest', 'notificationSettings'));
    }

    /**
     * Update user profile settings
     */
    public function update(Request $request)
    {
        $user = $this->currentUser();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'current_password' => 'nullable|required_with:password',
            'password' => 'nullable|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Please correct the validation errors and try again.');
            return back()->withErrors($validator)->withInput();
        }

        try {
            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->filled('phone')) {
                $user->phone = $request->phone;
            }

            if ($request->hasFile('profile_photo')) {
                if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                }
                
                $user->profile_photo = $this->fileUploadService->uploadProfilePhoto($request->file('profile_photo'));
            }

            if ($request->filled('password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    ToastMagic::error('Current password is incorrect.');
                    return back()->withInput();
                }
                $user->password = Hash::make($request->password);
            }

            $user->save();

            ToastMagic::success('Your profile has been updated successfully!');
            return redirect()->route('student.settings');
        } catch (\Exception $e) {
            ToastMagic::error('An error occurred while updating your profile. Please try again.');
            return back()->withInput();
        }
    }

    /**
     * Delete the authenticated student's account.
     */
    public function destroy(Request $request)
    {
        $user = $this->currentUser();

        $hasPending = $user->accountDeletionRequests()
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            ToastMagic::warning('You already have a pending account deletion request.');

            return redirect()->route('student.settings');
        }

        AccountDeletionRequest::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'role' => $user->role,
            'status' => 'pending',
        ]);

        ToastMagic::success('Your account deletion request has been submitted for admin approval.');

        return redirect()->route('student.settings');
    }

    public function updateNotifications(Request $request)
    {
        $user = $this->currentUser();

        $updates = [
            'email_notifications' => $request->boolean('email_notifications'),
            'course_reminders' => $request->boolean('course_reminders'),
            'progress_reports' => $request->boolean('progress_reports'),
            'marketing_emails' => $request->boolean('marketing_emails'),
        ];

        $this->storeNotificationPreferences($user, $updates);

        ToastMagic::success('Notification preferences updated.');

        return redirect()->route('student.settings');
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

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

