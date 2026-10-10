<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Services\NotificationPreferenceService;
use App\Services\OtpService;
use App\Services\FileUploadService;
use App\Mail\WelcomeMail;
use App\Mail\AdminNewRegistrationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private NotificationPreferenceService $notificationPreferenceService,
        private FileUploadService $fileUploadService
    ) {
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|min:8|confirmed',
            'bio' => 'required|string|max:500',
            'years_experience' => 'required|string',
            'specialization' => 'required|string|max:255',
            'previous_experience' => 'required|string',
            'highest_degree' => 'required|string',
            'field_of_study' => 'required|string|max:255',
            'certifications' => 'nullable|string',
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'resume' => 'required|mimes:pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = new User([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'instructor',
                'is_approved' => false,
                'phone' => $request->phone,
                'bio' => $request->bio,
                'years_experience' => $request->years_experience,
                'specialization' => $request->specialization,
                'previous_experience' => $request->previous_experience,
                'highest_degree' => $request->highest_degree,
                'field_of_study' => $request->field_of_study,
                'certifications' => $request->certifications,
            ]);

            if ($request->hasFile('profile_photo')) {
                $user->profile_photo = $this->fileUploadService->uploadProfilePhoto($request->file('profile_photo'));
            }

            if ($request->hasFile('resume')) {
                $user->resume = $this->fileUploadService->uploadResume($request->file('resume'));
            }

            $user->save();

            // Initialize notification settings
            UserNotificationSetting::updateOrCreate(
                ['user_id' => $user->id],
                ['preferences' => $this->notificationPreferenceService->defaultsForUser($user)]
            );

            // Send emails (Welcome & Admin Notification)
            $emailPreferences = settings('email.preferences', []);

            try {
                if (!empty($emailPreferences['send_welcome_email'])) {
                    Mail::to($user->email)->send(new WelcomeMail($user));
                }

                if (!empty($emailPreferences['notify_admin_new_registration'])) {
                    $adminEmail = settings('contact.details.support_email', config('mail.from.address'));
                    if ($adminEmail) {
                        Mail::to($adminEmail)->send(new AdminNewRegistrationMail($user));
                    }
                }
            } catch (\Exception $e) {
                // Log email errors but don't fail registration
                \Log::error('Registration email failed: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Your instructor application has been submitted successfully! We will review it and get back to you soon.',
                'user' => $user,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during registration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        if ($user->role !== 'instructor') {
            return response()->json([
                'message' => 'This account is not registered as an instructor.'
            ], 403);
        }


        // Check for Email Verification
        if (settings('authentication.security.email_verification_required', false) && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();

            return response()->json([
                'message' => 'Your email is not verified. A new verification link has been sent to your email.',
                'email_unverified' => true
            ], 403);
        }

        if ($this->otpService->enabled()) {
            $this->otpService->send($user);

            return response()->json([
                'message' => 'OTP sent to your email',
                'otp_required' => true,
                'email' => $user->email
            ]);
        }

        $token = $user->createToken('instructor_auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
            'otp_required' => false
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->role !== 'instructor') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$this->otpService->verify($user, $request->code)) {
            return response()->json(['message' => 'Invalid or expired OTP'], 422);
        }

        $this->otpService->clear($user);

        $token = $user->createToken('instructor_auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->role !== 'instructor') {
            return response()->json(['message' => 'User not found'], 404);
        }

        $this->otpService->send($user);

        return response()->json(['message' => 'OTP resent successfully']);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->where('role', 'instructor')->first();

        if (!$user) {
            return response()->json([
                'message' => 'No instructor account found with that email address.'
            ], 404);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => 'Password reset link sent to your email.'
            ], 200);
        }

        return response()->json([
            'message' => 'Unable to send reset link.'
        ], 500);
    }
}
