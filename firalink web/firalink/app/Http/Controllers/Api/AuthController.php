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
            'password'   => \App\Services\Auth\PasswordRules::rules(),
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

        // Exact email only (no @firalink.com / @eduex.com aliasing) and the exact password only.
        $user = User::where('email', $request->email)->first();
        $isValidPassword = $user ? Hash::check($request->password, $user->password) : false;

        if (!$user || !$isValidPassword) {
            return response()->json([
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Invalid email or password.'
            ], 401);
        }

        // Flagged by auth:flag-weak-passwords: no token until a new password is chosen.
        if ($user->must_reset_password) {
            return response()->json([
                'success' => false,
                'code' => 'PASSWORD_RESET_REQUIRED',
                'message' => 'For your security, choose a new password before signing in.',
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

        // Check for Mandatory Phone Verification
        if ($user->role !== 'admin' && !$user->hasVerifiedPhone()) {
            if (empty($user->phone)) {
                // No phone on file (older email accounts): nothing to verify.
            } elseif (app()->environment('local')) {
                // Development only: environment-enforced, never in production.
                $user->markPhoneAsVerified();
            } elseif (!$this->otpService->smsAvailable()) {
                // Production without SMS: refuse rather than mark the phone verified without a code.
                return response()->json([
                    'success' => false,
                    'code'    => 'SMS_UNAVAILABLE',
                    'message' => 'SMS is not available right now, so your phone cannot be verified. Try again later.',
                ], 503);
            } else {
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
        }

        if ($this->otpService->enabled()) {
            try {
                $this->otpService->send($user);
            } catch (\Throwable $e) {
                // If already dispatched and in active cooldown, reuse active code
            }

            return response()->json([
                'message' => 'OTP sent to your email',
                'otp_required' => true,
                'email' => $user->email
            ]);
        }

        $newToken = $user->createToken('auth_token');
        \App\Services\Auth\FreshAuth::markToken($newToken);
        $token = $newToken->plainTextToken;

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

        // Same answer for unknown accounts and wrong codes.
        if (!$user || !$this->otpService->verify($user, $request->code)) {
            return response()->json(['code' => 'INVALID_CODE', 'message' => 'Invalid or expired code.'], 422);
        }

        if ($user->must_reset_password) {
            return response()->json([
                'success' => false,
                'code' => 'PASSWORD_RESET_REQUIRED',
                'message' => 'For your security, choose a new password before signing in.',
            ], 403);
        }

        $this->otpService->clear($user);

        $newToken = $user->createToken('auth_token');
        \App\Services\Auth\FreshAuth::markToken($newToken);
        $token = $newToken->plainTextToken;

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

        // Same answer whether or not the account exists.
        if ($user) {
            try {
                $this->otpService->send($user);
            } catch (\Throwable $e) {
                // Cooldown, hourly cap or lockout: the existing code (if any) stays valid.
            }
        }

        return response()->json(['message' => 'If the account exists, a new code is on its way.']);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            if ($request->boolean('all_devices')) {
                $user->tokens()->delete();
            } else {
                $user->currentAccessToken()?->delete();
            }
        }

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

        // The app's token is read from the sanctum guard (this route is outside auth:sanctum).
        $user = auth('sanctum')->user();

        // Guests may only get a code for an existing account whose phone is not verified yet (GAP-030):
        // anything else gets the same generic answer and no SMS, so the endpoint cannot pump SMS to any number.
        if (!$user) {
            $normalized = app(\App\Services\SmsEthiopiaService::class)->normalizePhoneNumber($request->phone);
            $owner = $normalized
                ? \App\Models\User::whereIn('phone', array_unique([$normalized, '+' . $normalized, (string) $request->phone]))->whereNull('phone_verified_at')->first()
                : null;
            if (!$owner) {
                return response()->json(['success' => true, 'message' => 'If this number can be verified, a code is on its way.']);
            }
        }

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

        // Mark the number verified only on the single account that already has it.
        // This endpoint never changes anyone's phone: that is POST /api/user/phone (fresh auth + code).
        // Several accounts with the same number: none is marked (manual review).
        $smsService = app(\App\Services\SmsEthiopiaService::class);
        $normalized = $smsService->normalizePhoneNumber($request->phone);
        $accounts = User::where('phone', $request->phone)->orWhere('phone', $normalized)->get();

        if ($accounts->count() === 1) {
            $accounts->first()->forceFill(['phone' => $normalized, 'phone_verified_at' => now()])->save();
        } elseif ($accounts->count() > 1) {
            \App\Models\AuditLog::create([
                'action' => 'phone_verify_blocked_shared_phone',
                'auditable_type' => User::class,
                'after_state' => ['user_ids' => $accounts->pluck('id')->all()],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
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

        // Legacy endpoint kept for older app versions; delegates to the new flow so it gives
        // the same answer whether or not an account exists.
        $request->merge(['channel' => 'phone']);

        return app(PasswordResetController::class)->forgot($request);
    }

    /**
     * Reset password using phone OTP.
     */
    public function resetPasswordWithOtp(Request $request)
    {
        $request->validate([
            'phone'    => 'required|string|min:9|max:20',
            'code'     => 'required|string|min:4|max:8',
            'password' => \App\Services\Auth\PasswordRules::rules(),
        ]);

        $smsService = app(\App\Services\SmsEthiopiaService::class);
        $normalized = $smsService->normalizePhoneNumber($request->phone);

        $result = $normalized ? $this->otpService->verifyPhone($normalized, $request->code, 'password_reset') : ['success' => false];
        $accounts = $normalized ? User::where('phone', $request->phone)->orWhere('phone', $normalized)->get() : collect();
        $user = $accounts->count() === 1 ? $accounts->first() : null;

        // One answer for wrong code and unknown account.
        if (!($result['success'] ?? false) || !$user) {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_CODE',
                'message' => 'That code is not correct or has expired.',
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'must_reset_password' => false,
        ])->setRememberToken(\Illuminate\Support\Str::random(60));
        $user->save();
        \App\Services\Auth\SessionRevoker::revokeAll($user);

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

        // Generate access token (fresh: the user just proved the phone with a code)
        $newToken = $user->createToken('auth_token');
        \App\Services\Auth\FreshAuth::markToken($newToken);
        $token = $newToken->plainTextToken;

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
