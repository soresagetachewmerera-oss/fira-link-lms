<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RecaptchaService;
use App\Mail\WelcomeMail;
use App\Mail\AdminNewRegistrationMail;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationPreferenceService;
use App\Models\UserNotificationSetting;

/**
 * Register Controller
 *
 * This controller handles the student registration functionality.
 *
 * @package App\Http\Controllers\Student
 */
class RegisterController extends Controller
{
    public function __construct(private RecaptchaService $recaptcha, private NotificationPreferenceService $notificationPreferenceService)
    {
    }

    /**
     * Display the student registration form
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle student registration
     */
    public function register(Request $request)
    {
        if ($this->recaptcha->enabled() && !$this->recaptcha->verify($request->input('g-recaptcha-response'), $request->ip(), 'student_register')) {
            return back()->withErrors([
                'g-recaptcha-response' => 'Recaptcha verification failed. Please try again.',
            ])->withInput();
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'phone'      => 'required|string|min:9|max:20',
            'password'   => 'required|min:8|confirmed',
            'terms'      => 'required|accepted',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Please correct the validation errors and try again.');
            return back()->withErrors($validator)->withInput();
        }

        // Validate and normalize Ethiopian phone number via SMS Ethiopia service
        /** @var \App\Services\SmsEthiopiaService $smsService */
        $smsService = app(\App\Services\SmsEthiopiaService::class);
        $normalizedPhone = $smsService->normalizePhoneNumber($request->phone);

        if (!$normalizedPhone || !$smsService->isValidEthiopianNumber($request->phone)) {
            ToastMagic::warning('Please provide a valid Ethiopian mobile phone number.');
            return back()->withErrors([
                'phone' => 'Please provide a valid Ethiopian mobile phone number (e.g. 0911234567, 0711234567, or +251911234567).',
            ])->withInput();
        }

        // Prevent duplicate accounts with the same normalized phone
        if (User::where('phone', $normalizedPhone)->exists()) {
            ToastMagic::warning('This phone number is already registered.');
            return back()->withErrors([
                'phone' => 'This phone number is already registered to another account. Please sign in or use another number.',
            ])->withInput();
        }

        try {
            $user = User::create([
                'name'              => $request->first_name . ' ' . $request->last_name,
                'email'             => $request->email,
                'phone'             => $normalizedPhone,
                'password'          => Hash::make($request->password),
                'role'              => 'student',
                'is_approved'       => true,
                'phone_verified_at' => null, // Unverified until OTP confirmation
            ]);

            UserNotificationSetting::updateOrCreate(
                ['user_id' => $user->id],
                ['preferences' => $this->notificationPreferenceService->defaultsForUser($user)]
            );

            // Send verification code via SMS Ethiopia
            /** @var \App\Services\OtpService $otpService */
            $otpService = app(\App\Services\OtpService::class);
            $otpService->sendToPhone($normalizedPhone, 'registration', $user);

            // Save pending verification context in session
            session([
                'pending_verification_user_id' => $user->id,
                'pending_verification_phone'   => $normalizedPhone,
                'pending_verification_role'    => 'student',
            ]);

            ToastMagic::success('Registration initiated! Please enter the SMS code sent to your phone.');
            return redirect()->route('verification.phone.notice');
        } catch (\Exception $e) {
            ToastMagic::error('An error occurred during registration. Please try again.');
            return back()->withInput();
        }
    }
}

