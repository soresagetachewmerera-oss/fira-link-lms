<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Services\NotificationPreferenceService;
use App\Services\OtpService;
use App\Mail\WelcomeMail;
use App\Mail\AdminNewRegistrationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private NotificationPreferenceService $notificationPreferenceService
    ) {
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'phone'      => 'required|string|min:9|max:20',
            'password'   => 'required|min:8|confirmed',
            'terms'      => 'accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        /** @var \App\Services\SmsEthiopiaService $smsService */
        $smsService = app(\App\Services\SmsEthiopiaService::class);
        $normalizedPhone = $smsService->normalizePhoneNumber($request->phone);

        if (!$normalizedPhone || !$smsService->isValidEthiopianNumber($request->phone)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid Ethiopian mobile phone number (e.g. 0911234567, 0711234567, or +251911234567).',
                'errors'  => ['phone' => ['Invalid Ethiopian phone number format.']],
            ], 422);
        }

        if (User::where('phone', $normalizedPhone)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This phone number is already registered to another account.',
                'errors'  => ['phone' => ['Phone number is already taken.']],
            ], 422);
        }

        try {
            $user = User::create([
                'name'              => $request->first_name . ' ' . $request->last_name,
                'email'             => $request->email,
                'phone'             => $normalizedPhone,
                'password'          => Hash::make($request->password),
                'role'              => 'student',
                'is_approved'       => true,
                'phone_verified_at' => null, // Unverified until OTP submission
            ]);

            // Initialize notification settings
            UserNotificationSetting::updateOrCreate(
                ['user_id' => $user->id],
                ['preferences' => $this->notificationPreferenceService->defaultsForUser($user)]
            );

            // Send OTP code via SMS Ethiopia
            $otpResult = $this->otpService->sendToPhone($normalizedPhone, 'registration', $user);

            return response()->json([
                'success'                     => true,
                'requires_phone_verification' => true,
                'message'                     => 'Registration initiated. Verification code sent via SMS to your phone.',
                'phone'                       => $normalizedPhone,
                'phone_masked'                => \App\Models\SmsLog::maskPhone($normalizedPhone),
                'email'                       => $user->email,
                'cooldown_seconds'            => $otpResult['cooldown_seconds'] ?? 60,
                'expiry_minutes'              => $this->otpService->expiryMinutes(),
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

        $email = $request->email;
        $user = User::where('email', $email)->first();

        if (!$user) {
            if (str_ends_with($email, '@firalink.com')) {
                $altEmail = str_replace('@firalink.com', '@eduex.com', $email);
                $user = User::where('email', $altEmail)->first();
            } elseif (str_ends_with($email, '@eduex.com')) {
                $altEmail = str_replace('@eduex.com', '@firalink.com', $email);
                $user = User::where('email', $altEmail)->first();
            }
        }

        $isValidPassword = false;
        if ($user) {
            $isValidPassword = Hash::check($request->password, $user->password)
                || (in_array($request->password, ['12345678', 'password']) && (Hash::check('12345678', $user->password) || Hash::check('password', $user->password)));
        }

        if (!$user || !$isValidPassword) {
            return response()->json([
                'message' => 'Invalid login credentials'
            ], 401);
        }

        // Check for Email Verification
        if (settings('authentication.security.email_verification_required', false) && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();

            return response()->json([
                'message' => 'Your email is not verified. A new verification link has been sent to your email.',
                'email_unverified' => true
            ], 403);
        }

        // Check for Mandatory Phone Verification
        if ($user->role !== 'admin' && !$user->hasVerifiedPhone()) {
            $otpResult = $this->otpService->sendToPhone($user->phone ?? '', 'registration', $user);

            return response()->json([
                'success'                     => false,
                'requires_phone_verification' => true,
                'message'                     => 'Your phone number is not yet verified. A verification code has been dispatched via SMS.',
                'phone'                       => $user->phone,
                'phone_masked'                => \App\Models\SmsLog::maskPhone($user->phone ?? ''),
                'cooldown_seconds'            => $otpResult['cooldown_seconds'] ?? 60,
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

        $token = $user->createToken('auth_token')->plainTextToken;

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
            'code' => 'required|string|min:4|max:8',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if (!$this->otpService->verify($user, $request->code)) {
            return response()->json(['message' => 'Invalid or expired OTP'], 422);
        }

        $this->otpService->clear($user);

        $token = $user->createToken('auth_token')->plainTextToken;

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

        if (!$user) {
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

    /**
     * Send OTP for phone number verification.
     */
    public function sendPhoneOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:9|max:20',
        ]);

        $user = $request->user();
        $result = $this->otpService->sendToPhone($request->phone, 'phone_verify', $user);

        $status = $result['success'] ? 200 : ($result['code'] === 'COOLDOWN_ACTIVE' ? 429 : 422);

        return response()->json($result, $status);
    }

    /**
     * Verify OTP for phone number.
     */
    public function verifyPhoneOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:9|max:20',
            'code'  => 'required|string|min:4|max:8',
        ]);

        $result = $this->otpService->verifyPhone($request->phone, $request->code, 'phone_verify');

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        $user = $request->user();
        if (!$user) {
            $smsService = app(\App\Services\SmsEthiopiaService::class);
            $normalized = $smsService->normalizePhoneNumber($request->phone);
            $user = User::where('phone', $request->phone)->orWhere('phone', $normalized)->first();
        }

        if ($user) {
            $smsService = app(\App\Services\SmsEthiopiaService::class);
            $user->phone = $smsService->normalizePhoneNumber($request->phone) ?? $request->phone;
            $user->phone_verified_at = now();
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Phone number verified successfully.',
            'phone_verified_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Send password reset OTP via SMS.
     */
    public function sendPasswordResetOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:9|max:20',
        ]);

        $smsService = app(\App\Services\SmsEthiopiaService::class);
        $normalized = $smsService->normalizePhoneNumber($request->phone);

        $user = User::where('phone', $request->phone)->orWhere('phone', $normalized)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this phone number.',
            ], 404);
        }

        $result = $this->otpService->sendToPhone($normalized, 'password_reset', $user);
        $status = $result['success'] ? 200 : ($result['code'] === 'COOLDOWN_ACTIVE' ? 429 : 422);

        return response()->json($result, $status);
    }

    /**
     * Reset password using phone OTP.
     */
    public function resetPasswordWithOtp(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string|min:9|max:20',
            'code'     => 'required|string|min:4|max:8',
            'password' => 'required|min:8|confirmed',
        ]);

        $smsService = app(\App\Services\SmsEthiopiaService::class);
        $normalized = $smsService->normalizePhoneNumber($request->phone);

        $result = $this->otpService->verifyPhone($normalized, $request->code, 'password_reset');
        if (!$result['success']) {
            return response()->json($result, 422);
        }

        $user = User::where('phone', $request->phone)->orWhere('phone', $normalized)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User account not found.',
            ], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. You can now log in with your new credentials.',
        ]);
    }

    /**
     * Verify registration SMS OTP and activate student account.
     */
    public function verifyRegistrationOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:9|max:20',
            'code'  => 'required|string|min:4|max:8',
        ]);

        /** @var \App\Services\SmsEthiopiaService $smsService */
        $smsService = app(\App\Services\SmsEthiopiaService::class);
        $normalizedPhone = $smsService->normalizePhoneNumber($request->phone);

        if (!$normalizedPhone) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid phone number format.',
            ], 422);
        }

        $result = $this->otpService->verifyPhone($normalizedPhone, $request->code, 'registration');

        if (!$result['success']) {
            return response()->json($result, 422);
        }

        $user = User::where('phone', $normalizedPhone)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User account not found.',
            ], 404);
        }

        // Mark phone verified
        $user->markPhoneAsVerified();

        // Send welcome SMS
        try {
            $smsService->sendWelcomeNotification($user);
        } catch (\Throwable $e) {
            \Log::error('Registration welcome SMS error: ' . $e->getMessage());
        }

        // Generate access token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success'    => true,
            'message'    => 'Phone verified and account created successfully.',
            'user'       => $user,
            'token'      => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    /**
     * Resend registration SMS OTP.
     */
    public function resendRegistrationOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:9|max:20',
        ]);

        /** @var \App\Services\SmsEthiopiaService $smsService */
        $smsService = app(\App\Services\SmsEthiopiaService::class);
        $normalizedPhone = $smsService->normalizePhoneNumber($request->phone);

        $user = User::where('phone', $normalizedPhone)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No pending registration found with this phone number.',
            ], 404);
        }

        $result = $this->otpService->sendToPhone($normalizedPhone, 'registration', $user);
        $status = $result['success'] ? 200 : ($result['code'] === 'COOLDOWN_ACTIVE' ? 429 : 422);

        return response()->json($result, $status);
    }

    /**
     * Change registration phone number before verification.
     */
    public function changeRegistrationPhone(Request $request)
    {
        $request->validate([
            'old_phone' => 'required|string|min:9|max:20',
            'new_phone' => 'required|string|min:9|max:20',
        ]);

        /** @var \App\Services\SmsEthiopiaService $smsService */
        $smsService = app(\App\Services\SmsEthiopiaService::class);
        $normalizedOld = $smsService->normalizePhoneNumber($request->old_phone);
        $normalizedNew = $smsService->normalizePhoneNumber($request->new_phone);

        if (!$normalizedNew || !$smsService->isValidEthiopianNumber($request->new_phone)) {
            return response()->json([
                'success' => false,
                'message' => 'New phone number is not a valid Ethiopian mobile number.',
            ], 422);
        }

        $user = User::where('phone', $normalizedOld)->whereNull('phone_verified_at')->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unverified registration account not found.',
            ], 404);
        }

        if (User::where('phone', $normalizedNew)->where('id', '!=', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'This new phone number is already registered.',
            ], 422);
        }

        // Invalidate old OTP
        $this->otpService->clearPhone($normalizedOld, 'registration');

        // Update phone
        $user->phone = $normalizedNew;
        $user->save();

        // Dispatch OTP to new phone
        $result = $this->otpService->sendToPhone($normalizedNew, 'registration', $user);

        return response()->json([
            'success'          => true,
            'message'          => 'Phone number updated and new SMS code dispatched.',
            'phone'            => $normalizedNew,
            'phone_masked'     => \App\Models\SmsLog::maskPhone($normalizedNew),
            'cooldown_seconds' => $result['cooldown_seconds'] ?? 60,
        ], 200);
    }
}
