<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsEthiopiaService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * PhoneVerificationController
 *
 * Handles mandatory phone number OTP verification for web registration and accounts.
 *
 * @package App\Http\Controllers\Auth
 */
class PhoneVerificationController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private SmsEthiopiaService $smsService
    ) {
    }

    /**
     * Resolve the target user and phone undergoing verification.
     */
    protected function resolveTarget(Request $request): ?array
    {
        if (Auth::check()) {
            $user = Auth::user();
            return [
                'user'  => $user,
                'phone' => $user->phone,
                'role'  => $user->role,
            ];
        }

        $userId = session('pending_verification_user_id');
        $phone  = session('pending_verification_phone');
        $role   = session('pending_verification_role', 'student');

        if ($userId && $phone) {
            $user = User::find($userId);
            if ($user) {
                return [
                    'user'  => $user,
                    'phone' => $phone,
                    'role'  => $role,
                ];
            }
        }

        return null;
    }

    /**
     * Show the phone verification notice & input screen.
     */
    public function notice(Request $request)
    {
        $target = $this->resolveTarget($request);

        if (!$target) {
            ToastMagic::warning('No pending phone verification found. Please sign up or sign in.');
            return redirect()->route('login');
        }

        $user = $target['user'];

        if ($user->hasVerifiedPhone()) {
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            return redirect()->route('student.dashboard');
        }

        $phone = $target['phone'];
        $maskedPhone = SmsLog::maskPhone($phone);
        $cooldownRemaining = $this->otpService->getCooldownRemaining($phone, 'registration');
        $codeLength = $this->otpService->codeLength();
        $expiryMinutes = $this->otpService->expiryMinutes();

        return view('auth.verify-phone', compact(
            'user',
            'phone',
            'maskedPhone',
            'cooldownRemaining',
            'codeLength',
            'expiryMinutes'
        ));
    }

    /**
     * Verify the submitted SMS OTP code.
     */
    public function verify(Request $request)
    {
        $target = $this->resolveTarget($request);

        if (!$target) {
            ToastMagic::warning('Verification session expired. Please sign in again.');
            return redirect()->route('login');
        }

        $codeLength = $this->otpService->codeLength();

        $request->validate([
            'code' => ['required', 'string', 'min:4', 'max:8'],
        ]);

        $user = $target['user'];
        $phone = $target['phone'];

        $result = $this->otpService->verifyPhone($phone, $request->input('code'), 'registration');

        if (!$result['success']) {
            ToastMagic::error($result['message'] ?? 'Invalid verification code.');
            return back()->withErrors(['code' => $result['message']])->withInput();
        }

        // Successfully verified
        $user->markPhoneAsVerified();

        // Clear session keys
        session()->forget([
            'pending_verification_user_id',
            'pending_verification_phone',
            'pending_verification_role',
        ]);

        // Send registration welcome SMS
        try {
            $this->smsService->sendWelcomeNotification($user);
        } catch (\Throwable $e) {
            \Log::error('Welcome SMS failed after phone verification: ' . $e->getMessage());
        }

        if ($user->role === 'student') {
            Auth::login($user);
            $request->session()->regenerate();
            ToastMagic::success('Phone verified successfully! Welcome to ' . site_name() . '.');
            return redirect()->route('student.dashboard');
        }

        // Instructor flow: application pending admin review
        ToastMagic::success('Phone verified successfully! Your instructor application is being reviewed by our administration.');
        return redirect()->route('login');
    }

    /**
     * Resend the SMS verification OTP code.
     */
    public function resend(Request $request)
    {
        $target = $this->resolveTarget($request);

        if (!$target) {
            ToastMagic::warning('Verification session expired. Please sign in again.');
            return redirect()->route('login');
        }

        $phone = $target['phone'];
        $user  = $target['user'];

        if ($this->otpService->isInCooldown($phone, 'registration')) {
            $remaining = $this->otpService->getCooldownRemaining($phone, 'registration');
            ToastMagic::warning("Please wait {$remaining} seconds before requesting a new code.");
            return back();
        }

        $dispatchResult = $this->otpService->sendToPhone($phone, 'registration', $user);

        if ($dispatchResult['success'] ?? false) {
            ToastMagic::success('A new verification code has been dispatched via SMS.');
        } else {
            ToastMagic::error($dispatchResult['message'] ?? 'Failed to send SMS code. Please try again.');
        }

        return back();
    }

    /**
     * Allow user to correct phone number if mistyped.
     */
    public function changePhone(Request $request)
    {
        $target = $this->resolveTarget($request);

        if (!$target) {
            ToastMagic::warning('Verification session expired. Please sign in again.');
            return redirect()->route('login');
        }

        $request->validate([
            'phone' => ['required', 'string', 'min:9', 'max:20'],
        ]);

        $oldPhone = $target['phone'];
        $user = $target['user'];

        $normalized = $this->smsService->normalizePhoneNumber($request->phone);

        if (!$normalized || !$this->smsService->isValidEthiopianNumber($request->phone)) {
            ToastMagic::warning('Please provide a valid Ethiopian mobile phone number.');
            return back()->withErrors(['phone' => 'Invalid Ethiopian phone format. Must start with 09, 07, or +251.'])->withInput();
        }

        if (User::where('phone', $normalized)->where('id', '!=', $user->id)->exists()) {
            ToastMagic::warning('This phone number is already registered to another account.');
            return back()->withErrors(['phone' => 'This phone number is already in use by another account.'])->withInput();
        }

        // Invalidate old OTP
        $this->otpService->clearPhone($oldPhone, 'registration');

        // Update phone
        $user->phone = $normalized;
        $user->save();

        session(['pending_verification_phone' => $normalized]);

        // Send OTP to the new phone
        $this->otpService->sendToPhone($normalized, 'registration', $user);

        ToastMagic::success('Phone number updated! A fresh verification code has been dispatched via SMS.');
        return back();
    }
}
