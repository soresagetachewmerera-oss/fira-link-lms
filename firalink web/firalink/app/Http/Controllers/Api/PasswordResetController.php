<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Auth\PasswordRules;
use App\Services\Auth\SessionRevoker;
use App\Services\OtpService;
use App\Services\SmsEthiopiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Forgot password by phone or email.
 *
 *   POST /api/auth/password/forgot       {channel: phone|email, phone|email}
 *   POST /api/auth/password/verify-code  {channel, phone|email, code}  -> reset_ticket
 *   POST /api/auth/password/reset        {reset_ticket, password, password_confirmation}
 *
 * The forgot and verify responses are identical whether or not the account exists, so this
 * flow cannot be used to find out who has an account. Codes: 6 digits, 10 minutes, single use,
 * same lockout as sign-in. After the reset every token and web session is revoked.
 */
class PasswordResetController extends Controller
{
    public const PURPOSE = 'password_reset';

    private const TICKET_MINUTES = 10;

    public function __construct(private OtpService $otp, private SmsEthiopiaService $sms)
    {
    }

    private const GENERIC_SENT = 'If an account matches, a 6-digit code is on its way.';

    private const GENERIC_BAD_CODE = 'That code is not correct or has expired.';

    /**
     * Returns [identifier, user|null] for the request, or null if the input is malformed.
     * Phone: only an account that verified this exact number, and only if it is the only one.
     */
    private function resolve(Request $request): ?array
    {
        if ($request->input('channel') === 'phone') {
            $raw = (string) $request->input('phone', '');
            if (!$this->sms->isValidEthiopianNumber($raw)) {
                return null;
            }
            $phone = $this->sms->normalizePhoneNumber($raw);
            $accounts = User::whereIn('phone', [$phone, '0' . substr($phone, 3), '+' . $phone])->get();
            $user = $accounts->count() === 1 && $accounts->first()->phone_verified_at ? $accounts->first() : null;

            return [$phone, $user];
        }

        $email = Str::lower(trim((string) $request->input('email', '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return [$email, User::where('email', $email)->first()];
    }

    private function ipLimited(Request $request, string $action, int $max): ?JsonResponse
    {
        $key = "auth-reset-{$action}-ip:" . $request->ip();
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

    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['channel' => 'required|in:phone,email']);

        $resolved = $this->resolve($request);
        if ($resolved === null) {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_INPUT',
                'message' => $request->input('channel') === 'phone'
                    ? 'Enter a valid Ethiopian mobile number.'
                    : 'Enter a valid email address.',
            ], 422);
        }

        // Applies to everyone equally, so it reveals nothing about accounts.
        if ($request->input('channel') === 'phone' && !$this->otp->smsAvailable()) {
            return response()->json([
                'success' => false,
                'code' => 'SMS_UNAVAILABLE',
                'message' => 'SMS is not available right now. Reset with your email instead.',
            ], 503);
        }

        if ($limited = $this->ipLimited($request, 'send', PhoneAuthController::IP_SENDS_PER_HOUR)) {
            return $limited;
        }

        [$identifier, $user] = $resolved;

        // The reply body is identical either way. Delivery happens inline, so an existing account
        // takes slightly longer to answer (one SMS or email call); moving delivery to a queue
        // would remove that timing difference once a queue worker runs in production.
        if ($user) {
            if ($request->input('channel') === 'phone') {
                $this->otp->sendToPhone($identifier, self::PURPOSE, $user);
            } else {
                $this->otp->sendToEmail($identifier, self::PURPOSE, $user);
            }
        }

        return response()->json([
            'success' => true,
            'message' => self::GENERIC_SENT,
            'code_length' => 6,
            'expiry_minutes' => $this->otp->expiryMinutes(),
            'cooldown_seconds' => $this->otp->resendCooldownSeconds(),
        ]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'channel' => 'required|in:phone,email',
            'code' => 'required|digits:6',
        ]);

        if ($limited = $this->ipLimited($request, 'verify', PhoneAuthController::IP_VERIFIES_PER_HOUR)) {
            return $limited;
        }

        $resolved = $this->resolve($request);
        [$identifier, $user] = $resolved ?? [null, null];

        $result = $identifier
            ? ($request->input('channel') === 'phone'
                ? $this->otp->verifyPhone($identifier, (string) $request->input('code'), self::PURPOSE)
                : $this->otp->verifyEmailIdentifier($identifier, (string) $request->input('code'), self::PURPOSE))
            : ['success' => false];

        // One answer for wrong, expired, locked and unknown-account, so nothing leaks.
        if (!($result['success'] ?? false) || !$user) {
            return response()->json([
                'success' => false,
                'code' => 'INVALID_CODE',
                'message' => self::GENERIC_BAD_CODE,
            ], 422);
        }

        $ticket = Str::random(64);
        Cache::put('password_reset_ticket_' . hash('sha256', $ticket), $user->id, now()->addMinutes(self::TICKET_MINUTES));

        return response()->json([
            'success' => true,
            'reset_ticket' => $ticket,
            'ticket_expires_in_minutes' => self::TICKET_MINUTES,
        ]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'reset_ticket' => 'required|string|size:64',
            'password' => PasswordRules::rules(),
        ]);

        $userId = Cache::pull('password_reset_ticket_' . hash('sha256', (string) $request->input('reset_ticket')));
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'TICKET_EXPIRED',
                'message' => 'This reset has expired. Start again.',
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make((string) $request->input('password')),
            'must_reset_password' => false,
        ])->setRememberToken(Str::random(60));
        $user->save();

        $revoked = SessionRevoker::revokeAll($user);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'password_reset_completed',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'after_state' => $revoked,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated. You were signed out on your other devices.',
            'signed_out_everywhere' => true,
        ]);
    }
}
