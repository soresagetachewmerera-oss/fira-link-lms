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
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'terms' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Please correct the validation errors and try again.');
            return back()->withErrors($validator)->withInput();
        }

        try {
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'student',
                'is_approved' => true,
            ]);


            UserNotificationSetting::updateOrCreate(
                ['user_id' => $user->id],
                ['preferences' => $this->notificationPreferenceService->defaultsForUser($user)]
            );

            $emailPreferences = settings('email.preferences', []);

            try {
                if (!empty($emailPreferences['send_welcome_email'])) {
                    Mail::to($user->email)->send(new WelcomeMail($user));
                }

                if (!empty($emailPreferences['notify_admin_new_registration'])) {
                    $adminEmail = settings('contact.details.support_email', config('mail.from.address'));

                    if ($adminEmail) {
                        Mail::to($adminEmail)->send(new AdminNewRegistrationMail($user));
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('Registration welcome email failed: ' . $e->getMessage());
            }

            if (settings('authentication.security.email_verification_required', false)) {
                $user->sendEmailVerificationNotification();
                ToastMagic::success('Registration successful! Please check your email to verify your account.');
                return redirect()->route('login');
            } else {
                $user->markEmailAsVerified();
                Auth::login($user);
            }

            ToastMagic::success('Registration successful! Welcome to ' . site_name() . '.');
            return redirect()->route('student.dashboard');
        } catch (\Exception $e) {
            ToastMagic::error('An error occurred during registration. Please try again.');
            return back()->withInput();
        }
    }
}

