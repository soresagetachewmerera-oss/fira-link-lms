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
            return response()->json(['success' => false, 'code' => 'INVALID_CREDENTIALS', 'message' => 'That code or password is not correct.'], 422);
        }

        FreshAuth::markRequest($request);

        return response()->json([
            'success' => true,
            'fresh_for_minutes' => FreshAuth::WINDOW_MINUTES,
        ]);
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

        return response()->json(['success' => true, 'message' => 'Phone number updated.', 'user' => $user->fresh()]);
    }
}
