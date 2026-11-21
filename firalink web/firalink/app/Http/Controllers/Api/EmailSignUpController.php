<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Services\Auth\FreshAuth;
use App\Services\Auth\PasswordRules;
use App\Services\NotificationPreferenceService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Email and password sign-up, for people without an Ethiopian phone number.
 *
 *   POST /api/auth/email/register/start   {full_name, email, password, password_confirmation, terms}
 *   POST /api/auth/email/register/verify  {email, code}  -> account + token
 *
 * The account is created only after the emailed 6-digit code is confirmed. Same hashing,
 * expiry (10 minutes), single use, lockout and rate limits as phone codes. The start answer
 * is identical when the email already has an account (no code is sent then).
 */
class EmailSignUpController extends Controller
{
    public const PURPOSE = 'email_signup';

    private const PENDING_MINUTES = 15;

    public function __construct(private OtpService $otp, private NotificationPreferenceService $notificationPreferences)
    {
    }

    private function pendingKey(string $email): string
    {
        return 'email_signup_pending_' . hash('sha256', $email);
    }

    private function ipLimited(Request $request, string $action, int $max): ?JsonResponse
    {
        $key = "auth-email-signup-{$action}-ip:" . $request->ip();
        if (RateLimiter::tooManyAttempts($key, $max)) {
            return response()->json([
                'success' => false,
                'code' => 'TOO_MANY_REQUESTS',
                'message' => 'Too many attempts from this network. Try again later.',
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ], 429);
        }
        RateLimiter::hit($key, 3600);

        return null;
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:120', 'regex:/\S+/'],
            'email' => 'required|email:rfc|max:191',
            'password' => PasswordRules::rules(),
            'terms' => 'accepted',
        ]);

        if ($limited = $this->ipLimited($request, 'send', PhoneAuthController::IP_SENDS_PER_HOUR)) {
            return $limited;
        }

        $email = Str::lower(trim($validated['email']));

        if (!User::where('email', $email)->exists()) {
            // Keep the details until the code is confirmed; only the password hash is stored.
            Cache::put($this->pendingKey($email), [
                'name' => trim(preg_replace('/\s+/', ' ', $validated['full_name'])),
                'email' => $email,
                'password_hash' => Hash::make($validated['password']),
            ], now()->addMinutes(self::PENDING_MINUTES));

            // Cooldown, hourly cap and lockout still apply, but are not reported: a "wait" or
            // "locked" answer would only ever appear for unregistered emails and so reveal them.
            $draft = new User(['name' => $validated['full_name'], 'email' => $email]);
            $this->otp->sendToEmail($email, self::PURPOSE, $draft, withCooldown: true);
        }

        return response()->json([
            'success' => true,
            'message' => 'If this email can be used, a 6-digit code is on its way.',
            'code_length' => 6,
            'expiry_minutes' => $this->otp->expiryMinutes(),
            'cooldown_seconds' => $this->otp->resendCooldownSeconds(),
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email:rfc|max:191',
            'code' => 'required|digits:6',
        ]);

        if ($limited = $this->ipLimited($request, 'verify', PhoneAuthController::IP_VERIFIES_PER_HOUR)) {
            return $limited;
        }

        $email = Str::lower(trim((string) $request->input('email')));
        $result = $this->otp->verifyEmailIdentifier($email, (string) $request->input('code'), self::PURPOSE);

        $pending = ($result['success'] ?? false) ? Cache::pull($this->pendingKey($email)) : null;

        // One answer for wrong, expired, locked and already-registered emails.
        if (!$pending) {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_CODE',
                'message' => 'That code is not correct or has expired.',
            ], 422);
        }

        // Someone registered this email meanwhile: never create a second account.
        if (User::where('email', $email)->exists()) {
            return response()->json(['success' => false, 'code' => 'EMAIL_TAKEN', 'message' => 'This email already has an account. Sign in instead.'], 409);
        }

        $user = new User();
        $user->forceFill([
            'name' => $pending['name'],
            'email' => $email,
            'email_verified_at' => now(), // proven by the code
            'password' => $pending['password_hash'],
            'phone' => null,
            'role' => 'student',
            'is_approved' => true,
        ])->save();

        UserNotificationSetting::updateOrCreate(
            ['user_id' => $user->id],
            ['preferences' => $this->notificationPreferences->defaultsForUser($user)]
        );

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'account_created_by_email',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // The email was just confirmed with a code: welcome.
        app(\App\Services\Notifications\WelcomeMessage::class)->send($user);

        $token = $user->createToken('auth_token');
        FreshAuth::markToken($token);

        return response()->json([
            'success' => true,
            'status' => 'signed_in',
            'is_new_user' => true,
            'user' => $user->fresh(),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }
}
