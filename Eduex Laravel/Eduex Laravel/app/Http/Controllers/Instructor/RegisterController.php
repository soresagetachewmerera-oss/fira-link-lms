<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\RecaptchaService;
use App\Mail\WelcomeMail;
use App\Mail\AdminNewRegistrationMail;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationPreferenceService;
use App\Models\UserNotificationSetting;

/**
 * Register Controller
 *
 * This controller handles the instructor registration functionality.
 *
 * @package App\Http\Controllers\Instructor
 */
class RegisterController extends Controller
{
    public function __construct(private FileUploadService $fileUploadService, private RecaptchaService $recaptcha, private NotificationPreferenceService $notificationPreferenceService)
    {
    }

    /**
     * Display the instructor registration form
     */
    public function showRegistrationForm()
    {
        return view('instructor.auth.become-instructor');
    }

    /**
     * Handle instructor registration
     */
    public function register(Request $request)
    {
        if ($this->recaptcha->enabled() && !$this->recaptcha->verify($request->input('g-recaptcha-response'), $request->ip(), 'instructor_register')) {
            return back()->withErrors([
                'g-recaptcha-response' => 'Recaptcha verification failed. Please try again.',
            ])->withInput();
        }

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
            'terms' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Please correct the validation errors and try again.');
            return back()->withErrors($validator)->withInput();
        }

        /** @var \App\Services\SmsEthiopiaService $smsService */
        $smsService = app(\App\Services\SmsEthiopiaService::class);
        $normalizedPhone = $smsService->normalizePhoneNumber($request->phone);

        if (!$normalizedPhone || !$smsService->isValidEthiopianNumber($request->phone)) {
            ToastMagic::warning('Please provide a valid Ethiopian mobile phone number.');
            return back()->withErrors([
                'phone' => 'Please provide a valid Ethiopian mobile phone number (e.g. 0911234567, 0711234567, or +251911234567).',
            ])->withInput();
        }

        if (User::where('phone', $normalizedPhone)->exists()) {
            ToastMagic::warning('This phone number is already registered.');
            return back()->withErrors([
                'phone' => 'This phone number is already registered to another account.',
            ])->withInput();
        }

        try {
            $user = new User([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'instructor',
                'is_approved' => false,
                'phone' => $normalizedPhone,
                'phone_verified_at' => null,
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

            UserNotificationSetting::updateOrCreate(
                ['user_id' => $user->id],
                ['preferences' => $this->notificationPreferenceService->defaultsForUser($user)]
            );

            // Dispatch SMS OTP code
            /** @var \App\Services\OtpService $otpService */
            $otpService = app(\App\Services\OtpService::class);
            $otpService->sendToPhone($normalizedPhone, 'registration', $user);

            // Save pending verification context in session
            session([
                'pending_verification_user_id' => $user->id,
                'pending_verification_phone'   => $normalizedPhone,
                'pending_verification_role'    => 'instructor',
            ]);

            ToastMagic::info('Application submitted! Please verify your phone number using the SMS code sent.');
            return redirect()->route('verification.phone.notice');
        } catch (\Exception $e) {
            ToastMagic::error('An error occurred during registration. Please try again.');
            return back()->withInput();
        }
    }
}

