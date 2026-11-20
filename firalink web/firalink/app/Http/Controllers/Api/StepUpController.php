<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\Auth\FreshAuth;
use App\Services\OtpService;
use App\Services\SmsEthiopiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Fresh authentication for sensitive actions, for a user who is already signed in.
 *
 *   POST /api/auth/step-up/code    send a 6-digit code to the verified phone (or email if SMS is unavailable)
 *   POST /api/auth/step-up/verify  {code} or {password} -> this token is "fresh" for 10 minutes
 *   POST /api/user/phone           change the phone: code sent to the NEW number + fresh auth
 */
class StepUpController extends Controller
{
    public const PURPOSE = 'step_up';

    public function __construct(private OtpService $otp, private SmsEthiopiaService $sms)
    {
    }

    private function identifier(User $user): string
    {
        return 'user:' . $user->id;
    }

    public function sendCode(Request $request): JsonResponse
    {
        $user = $request->user();

        $usePhone = $user->phone && $user->phone_verified_at && $this->otp->smsAvailable();
        if (!$usePhone && empty($user->email)) {
            return response()->json([
                'success' => false,
                'code' => 'NO_CHANNEL',
                'message' => 'We cannot send you a code. Confirm with your password instead.',
            ], 422);
        }

        if ($usePhone) {
            // Code stored under the user identifier, delivered by SMS to the verified phone.
            $result = $this->otp->sendToPhoneForIdentifier($this->identifier($user), $user->phone, self::PURPOSE, $user);
        } else {
            $result = $this->otp->sendToEmail($this->identifier($user), self::PURPOSE, $user);
        }

        if (!($result['success'] ?? false)) {
            $status = match ($result['code'] ?? null) {
                'LOCKED' => 423,
                'TOO_MANY_REQUESTS', 'COOLDOWN_ACTIVE' => 429,
                default => 502,
            };

            return response()->json(array_merge(['success' => false], $result), $status);
        }

        return response()->json([
            'success' => true,
            'channel' => $usePhone ? 'sms' : 'email',
            'destination_masked' => $usePhone ? SmsLog::maskPhone($user->phone) : null,
            'code_length' => 6,
            'expiry_minutes' => $this->otp->expiryMinutes(),
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'nullable|required_without:password|digits:6',
            'password' => 'nullable|required_without:code|string',
        ]);

        $user = $request->user();
        $key = 'auth-stepup:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return response()->json(['success' => false, 'code' => 'TOO_MANY_REQUESTS', 'message' => 'Too many attempts. Try again later.'], 429);
        }
        RateLimiter::hit($key, 3600);

        $ok = $request->filled('password')
            ? Hash::check((string) $request->input('password'), $user->password)
            : ($this->otp->verifyForIdentifier($this->identifier($user), (string) $request->input('code'), self::PURPOSE)['success'] ?? false);

        if (!$ok) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'security.step_up_failed',
                'after_state' => ['method' => $request->filled('password') ? 'password' : 'code'],
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]);

            return response()->json(['success' => false, 'code' => 'INVALID_CREDENTIALS', 'message' => 'That code or password is not correct.'], 422);
        }

        FreshAuth::markRequest($request);

        return response()->json([
            'success' => true,
            'fresh_for_minutes' => FreshAuth::WINDOW_MINUTES,
        ]);
    }

    private function emailChangeKey(User $user): string
    {
        return 'email_change_pending_' . $user->id;
    }

    /**
     * Start an email change: send a 6-digit code to the NEW address. Needs fresh auth.
     * The answer is the same whether or not the address is already used by another account
     * (no code is sent then), so this cannot be used to look up who has an account.
     */
    public function sendEmailChangeCode(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email:rfc|max:191']);
        $user = $request->user();
        $email = \Illuminate\Support\Str::lower(trim((string) $request->input('email')));

        if ($email === \Illuminate\Support\Str::lower((string) $user->email)) {
            return response()->json(['success' => false, 'code' => 'SAME_EMAIL', 'message' => 'This is already your email address.'], 422);
        }

        $takenByOther = User::where('email', $email)->where('id', '!=', $user->id)->exists();
        if (!$takenByOther) {
            \Illuminate\Support\Facades\Cache::put($this->emailChangeKey($user), $email, now()->addMinutes($this->otp->expiryMinutes()));
            $draft = (clone $user)->forceFill(['email' => $email]);
            $this->otp->sendToEmail('email_change:' . $user->id, 'email_change', $draft, withCooldown: true);
        }

        return response()->json([
            'success' => true,
            'message' => 'If this address can be used, a 6-digit code is on its way to it.',
            'code_length' => 6,
            'expiry_minutes' => $this->otp->expiryMinutes(),
        ]);
    }

    /**
     * Confirm the email change with the code sent to the new address. Needs fresh auth.
     */
    public function changeEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email:rfc|max:191',
            'code' => 'required|digits:6',
        ]);
        $user = $request->user();
        $email = \Illuminate\Support\Str::lower(trim((string) $request->input('email')));

        $pending = \Illuminate\Support\Facades\Cache::get($this->emailChangeKey($user));
        $result = $pending === $email
            ? $this->otp->verifyForIdentifier('email_change:' . $user->id, (string) $request->input('code'), 'email_change')
            : ['success' => false];

        if (!($result['success'] ?? false) || User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
            return response()->json(['success' => false, 'code' => 'INVALID_CODE', 'message' => 'That code is not correct or has expired.'], 422);
        }

        \Illuminate\Support\Facades\Cache::forget($this->emailChangeKey($user));
        $before = $user->email;
        $user->forceFill(['email' => $email, 'email_verified_at' => now()])->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'email_changed',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'before_state' => ['had_email' => !empty($before)],
            'after_state' => ['email_domain' => substr(strrchr($email, '@'), 1)],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // In the app and by email to the new address, and by email to the old address too.
        \App\Services\Auth\SecurityEvents::record($user, 'email_changed', $request, ['email_domain' => substr(strrchr($email, '@'), 1)], audit: false, alsoMail: $before ?: null);

        return response()->json(['success' => true, 'message' => 'Email updated.', 'user' => $user->fresh()]);
    }

    /**
     * Change the verified phone. Needs fresh auth (route middleware) and a code sent to the new
     * number with POST /api/auth/phone/verify/send. A number used by another account is refused.
     */
    public function changePhone(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'code' => 'required|digits:6',
        ]);

        $raw = (string) $request->input('phone');
        if (!$this->sms->isValidEthiopianNumber($raw)) {
            return response()->json(['success' => false, 'code' => 'INVALID_PHONE', 'message' => 'Enter a valid Ethiopian mobile number.'], 422);
        }
        $phone = $this->sms->normalizePhoneNumber($raw);
        $user = $request->user();

        $taken = User::whereIn('phone', [$phone, '0' . substr($phone, 3), '+' . $phone])
            ->where('id', '!=', $user->id)
            ->exists();
        if ($taken) {
            return response()->json(['success' => false, 'code' => 'PHONE_TAKEN', 'message' => 'This number is linked to another account.'], 409);
        }

        $result = $this->otp->verifyPhone($phone, (string) $request->input('code'), 'phone_verify');
        if (!($result['success'] ?? false)) {
            return response()->json(['success' => false, 'code' => 'INVALID_CODE', 'message' => 'That code is not correct or has expired.'], 422);
        }

        $before = $user->phone;
        $user->forceFill(['phone' => $phone, 'phone_verified_at' => now()])->save();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'phone_changed',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'before_state' => ['phone_masked' => $before ? SmsLog::maskPhone($before) : null],
            'after_state' => ['phone_masked' => SmsLog::maskPhone($phone)],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        \App\Services\Auth\SecurityEvents::record($user, 'phone_changed', $request, ['phone_masked' => SmsLog::maskPhone($phone)], audit: false);

        return response()->json(['success' => true, 'message' => 'Phone number updated.', 'user' => $user->fresh()]);
    }
}
