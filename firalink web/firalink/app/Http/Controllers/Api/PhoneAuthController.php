<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\UserNotificationSetting;
use App\Services\Auth\FreshAuth;
use App\Services\NotificationPreferenceService;
use App\Services\OtpService;
use App\Services\SmsEthiopiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Phone sign-in: one step for new and returning users.
 *
 *   POST /api/auth/phone/start           send a 6-digit code by SMS
 *   POST /api/auth/phone/email-fallback  SMS failed: send the code to the account's email instead
 *   POST /api/auth/phone/verify          check the code -> signed in, or a sign-up ticket
 *   POST /api/auth/phone/complete        new users: full name -> account + token
 *
 * Rules: codes are hashed, valid 10 minutes, single use; 5 wrong tries kill a code; 3 killed
 * codes in an hour lock the phone for 30 minutes; per-phone and per-IP limits.
 * A returning user signs in only if exactly one account has this phone AND that account
 * verified it by code. Shared or unverified numbers are blocked and logged for manual review.
 */
class PhoneAuthController extends Controller
{
    public const PURPOSE = 'phone_signin';

    /** Per-IP limits (per hour). */
    public const IP_SENDS_PER_HOUR = 20;
    public const IP_VERIFIES_PER_HOUR = 30;

    private const SIGNUP_TICKET_MINUTES = 15;
    private const RESET_TICKET_MINUTES = 10;

    public function __construct(
        private OtpService $otp,
        private SmsEthiopiaService $sms,
        private NotificationPreferenceService $notificationPreferences,
    ) {
    }

    private function error(string $code, string $message, int $status, array $extra = []): JsonResponse
    {
        return response()->json(array_merge(['success' => false, 'code' => $code, 'message' => $message], $extra), $status);
    }

    /** Returns an error response if this IP exceeded the hourly limit for this action. */
    private function ipLimited(Request $request, string $action, int $max): ?JsonResponse
    {
        $key = "auth-{$action}-ip:" . $request->ip();
        if (RateLimiter::tooManyAttempts($key, $max)) {
            return $this->error('TOO_MANY_REQUESTS', 'Too many attempts from this network. Try again later.', 429, [
                'retry_after_seconds' => RateLimiter::availableIn($key),
            ]);
        }
        RateLimiter::hit($key, 3600);

        return null;
    }

    private function normalizedPhone(Request $request): ?string
    {
        $phone = (string) $request->input('phone', '');

        return $this->sms->isValidEthiopianNumber($phone) ? $this->sms->normalizePhoneNumber($phone) : null;
    }

    /** All accounts using this number, in any stored format. */
    private function accountsForPhone(string $normalized)
    {
        $local = '0' . substr($normalized, 3);
        $plus = '+' . $normalized;

        return User::whereIn('phone', [$normalized, $local, $plus])->get();
    }

    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'country_code' => 'nullable|string|max:5',
        ]);

        $country = ltrim((string) $request->input('country_code', '+251'), '+');
        if ($country !== '251') {
            return $this->error('SMS_COUNTRY_UNSUPPORTED', 'Codes by SMS are available for Ethiopian numbers only. Sign in with email instead.', 422);
        }

        $phone = $this->normalizedPhone($request);
        if (!$phone) {
            return $this->error('INVALID_PHONE', 'Enter a valid Ethiopian mobile number.', 422);
        }

        if (!$this->otp->smsAvailable()) {
            return $this->error('SMS_UNAVAILABLE', 'SMS is not available right now. Sign in with email instead.', 503, [
                'email_sign_in_available' => true,
            ]);
        }

        if ($limited = $this->ipLimited($request, 'send', self::IP_SENDS_PER_HOUR)) {
            return $limited;
        }

        $result = $this->otp->sendToPhone($phone, self::PURPOSE);

        if (!($result['success'] ?? false)) {
            return match ($result['code'] ?? null) {
                'COOLDOWN_ACTIVE' => $this->error('COOLDOWN_ACTIVE', 'Wait before asking for a new code.', 429, [
                    'cooldown_seconds' => $result['cooldown_remaining'] ?? $this->otp->resendCooldownSeconds(),
                ]),
                'LOCKED' => $this->error('LOCKED', 'Too many wrong codes. Try again later.', 423, [
                    'locked_until' => $result['locked_until'] ?? null,
                ]),
                'TOO_MANY_REQUESTS' => $this->error('TOO_MANY_REQUESTS', 'Too many codes requested. Try again in an hour.', 429),
                default => $this->error('SMS_FAILED', 'We could not send the SMS. You can get the code by email instead.', 502, [
                    'email_fallback_available' => true,
                ]),
            };
        }

        return response()->json([
            'success' => true,
            'phone_masked' => SmsLog::maskPhone($phone),
            'code_length' => $this->otp->codeLength(),
            'cooldown_seconds' => $result['cooldown_seconds'] ?? $this->otp->resendCooldownSeconds(),
            'expiry_minutes' => $this->otp->expiryMinutes(),
        ]);
    }

    /**
     * SMS failed. If exactly one account verified this phone and has an email, send the code there.
     * The response is the same whether or not such an account exists.
     */
    public function emailFallback(Request $request): JsonResponse
    {
        $request->validate(['phone' => 'required|string|max:20']);

        $phone = $this->normalizedPhone($request);
        if (!$phone) {
            return $this->error('INVALID_PHONE', 'Enter a valid Ethiopian mobile number.', 422);
        }

        if ($limited = $this->ipLimited($request, 'send', self::IP_SENDS_PER_HOUR)) {
            return $limited;
        }

        $accounts = $this->accountsForPhone($phone);
        $account = $accounts->count() === 1 ? $accounts->first() : null;

        if ($account && $account->phone_verified_at && !empty($account->email)) {
            $this->otp->sendToEmail($phone, self::PURPOSE, $account);
        }

        return response()->json([
            'success' => true,
            'message' => 'If this number belongs to an account with an email address, we sent the code there.',
            'expiry_minutes' => $this->otp->expiryMinutes(),
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'code' => 'required|digits:6',
        ]);

        $phone = $this->normalizedPhone($request);
        if (!$phone) {
            return $this->error('INVALID_PHONE', 'Enter a valid Ethiopian mobile number.', 422);
        }

        if ($limited = $this->ipLimited($request, 'verify', self::IP_VERIFIES_PER_HOUR)) {
            return $limited;
        }

        $result = $this->otp->verifyPhone($phone, (string) $request->input('code'), self::PURPOSE);

        if (!($result['success'] ?? false)) {
            return match ($result['code'] ?? null) {
                'LOCKED' => $this->error('LOCKED', 'Too many wrong codes. Try again later.', 423, [
                    'locked_until' => $result['locked_until'] ?? null,
                ]),
                'INVALID_CODE' => $this->error('INVALID_CODE', 'That code is not correct.', 422, [
                    'attempts_remaining' => $result['attempts_remaining'] ?? 0,
                ]),
                'MAX_ATTEMPTS_EXCEEDED' => $this->error('CODE_INVALIDATED', 'Too many wrong tries. Ask for a new code.', 422),
                default => $this->error('CODE_EXPIRED', 'This code has expired. Ask for a new one.', 422),
            };
        }

        $accounts = $this->accountsForPhone($phone);

        // New user: possession of the phone is proven; ask for a name next.
        if ($accounts->isEmpty()) {
            $ticket = Str::random(64);
            Cache::put('phone_signup_' . hash('sha256', $ticket), $phone, now()->addMinutes(self::SIGNUP_TICKET_MINUTES));

            return response()->json([
                'success' => true,
                'status' => 'new_user',
                'signup_ticket' => $ticket,
                'ticket_expires_in_minutes' => self::SIGNUP_TICKET_MINUTES,
            ]);
        }

        // Several accounts share this number: never merge or guess. Block and log for review.
        if ($accounts->count() > 1) {
            AuditLog::create([
                'action' => 'phone_signin_blocked_shared_phone',
                'auditable_type' => User::class,
                'after_state' => ['user_ids' => $accounts->pluck('id')->all(), 'phone_masked' => SmsLog::maskPhone($phone)],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->error('PHONE_SHARED', 'This number is linked to more than one account. Sign in with email, or contact support.', 409);
        }

        $user = $accounts->first();

        // The account has this number but never verified it by code: it cannot be used to sign in.
        if (!$user->phone_verified_at) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'phone_signin_blocked_unverified_phone',
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->error('PHONE_NOT_VERIFIED', 'This number is not verified on your account. Sign in with email, then verify it in Profile.', 409);
        }

        // Weak password flagged: the phone code proves identity, so go straight to choosing a new password.
        if ($user->must_reset_password) {
            $ticket = Str::random(64);
            Cache::put('password_reset_ticket_' . hash('sha256', $ticket), $user->id, now()->addMinutes(self::RESET_TICKET_MINUTES));

            return response()->json([
                'success' => true,
                'status' => 'password_reset_required',
                'reset_ticket' => $ticket,
            ]);
        }

        return $this->signedIn($user, false);
    }

    public function complete(Request $request): JsonResponse
    {
        $request->validate([
            'signup_ticket' => 'required|string|size:64',
            'full_name' => ['required', 'string', 'min:2', 'max:120', 'regex:/\S+/'],
        ]);

        $phone = Cache::pull('phone_signup_' . hash('sha256', (string) $request->input('signup_ticket')));
        if (!$phone) {
            return $this->error('TICKET_EXPIRED', 'This step has expired. Start again with your phone number.', 422);
        }

        // Someone registered this number meanwhile: do not create a second account.
        if ($this->accountsForPhone($phone)->isNotEmpty()) {
            return $this->error('PHONE_TAKEN', 'This number is already linked to an account. Sign in instead.', 409);
        }

        $user = new User();
        $user->forceFill([
            'name' => trim(preg_replace('/\s+/', ' ', (string) $request->input('full_name'))),
            'email' => null,
            'phone' => $phone,
            'phone_verified_at' => now(),
            'password' => Hash::make(Str::random(48)), // no usable password until the user sets one
            'role' => 'student',
            'is_approved' => true,
        ])->save();

        UserNotificationSetting::updateOrCreate(
            ['user_id' => $user->id],
            ['preferences' => $this->notificationPreferences->defaultsForUser($user)]
        );

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'account_created_by_phone',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->signedIn($user, true);
    }

    private function signedIn(User $user, bool $isNew): JsonResponse
    {
        $token = $user->createToken('auth_token');
        FreshAuth::markToken($token);

        return response()->json([
            'success' => true,
            'status' => 'signed_in',
            'is_new_user' => $isNew,
            'user' => $user->fresh(),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }
}
