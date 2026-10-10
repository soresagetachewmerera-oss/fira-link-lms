<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use App\Models\AccountDeletionRequest;
use App\Models\UserNotificationSetting;
use Devrabiul\ToastMagic\Facades\ToastMagic;
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
 * This controller handles the settings functionality.
 *
 * @package App\Http\Controllers\Instructor
 */
class SettingsController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService, private NotificationPreferenceService $notificationPreferenceService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Display instructor settings page
     */
    public function index()
    {
        $user = $this->currentUser();
        
        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $latestDeletionRequest = $user->accountDeletionRequests()->latest()->first();
        $notificationSettings = $this->resolveNotificationPreferences($user);

        $profile = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'bio' => $user->bio ?? 'Experienced instructor passionate about teaching and sharing knowledge.',
            'avatar' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('assets/front/img/home-1/courses/client-01.png'),
            'linkedin' => $user->linkedin ?? '',
            'twitter' => $user->twitter ?? '',
            'facebook' => $user->facebook ?? '',
            'youtube' => $user->youtube ?? '',
        ];

        return view('instructor.settings', compact('user', 'profile', 'latestDeletionRequest', 'notificationSettings'));
    }

    /**
     * Update instructor profile
     */
    public function updateProfile(Request $request)
    {
        $user = $this->currentUser();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'linkedin' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'facebook' => 'nullable|url|max:255',
            'youtube' => 'nullable|url|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
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
            if ($request->filled('bio')) {
                $user->bio = $request->bio;
            }
            if ($request->filled('linkedin')) {
                $user->linkedin = $request->linkedin;
            }
            if ($request->filled('twitter')) {
                $user->twitter = $request->twitter;
            }
            if ($request->filled('facebook')) {
                $user->facebook = $request->facebook;
            }
            if ($request->filled('youtube')) {
                $user->youtube = $request->youtube;
            }

            if ($request->hasFile('avatar')) {
                if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                    Storage::disk('public')->delete($user->profile_photo);
                }
                
                $user->profile_photo = $this->fileUploadService->uploadProfilePhoto($request->file('avatar'));
            }

            $user->save();

            ToastMagic::success('Your profile has been updated successfully!');
            return redirect()->route('instructor.settings');
        } catch (\Exception $e) {
            ToastMagic::error('An error occurred while updating your profile. Please try again.');
            return back()->withInput();
        }
    }

    /**
     * Update instructor password
     */
    public function updatePassword(Request $request)
    {
        $user = $this->currentUser();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'new_password_confirmation' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Please correct the validation errors and try again.');
            return back()->withErrors($validator)->withInput();
        }

        if (!Hash::check($request->current_password, $user->password)) {
            ToastMagic::error('Current password is incorrect.');
            return back()->withInput();
        }

        try {
            $user->password = Hash::make($request->new_password);
            $user->save();

            ToastMagic::success('Your password has been updated successfully!');
            return redirect()->route('instructor.settings');
        } catch (\Exception $e) {
            ToastMagic::error('An error occurred while updating your password. Please try again.');
            return back()->withInput();
        }
    }

    /**
     * Delete the authenticated instructor's account.
     */
    public function destroy(Request $request)
    {
        $user = $this->currentUser();

        $hasPending = $user->accountDeletionRequests()
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            ToastMagic::warning('You already have a pending account deletion request.');

            return redirect()->route('instructor.settings');
        }

        AccountDeletionRequest::create([
            'user_id' => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'role' => $user->role,
            'status' => 'pending',
        ]);

        ToastMagic::success('Your account deletion request has been submitted for admin approval.');

        return redirect()->route('instructor.settings');
    }

    public function updateNotifications(Request $request)
    {
        $user = $this->currentUser();

        $updates = [
            'email_notifications' => $request->boolean('email_notifications'),
            'course_updates' => $request->boolean('course_updates'),
            'review_notifications' => $request->boolean('review_notifications'),
            'marketing_emails' => $request->boolean('marketing_emails'),
        ];

        $this->storeNotificationPreferences($user, $updates);

        ToastMagic::success('Notification preferences updated.');

        return redirect()->route('instructor.settings');
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
