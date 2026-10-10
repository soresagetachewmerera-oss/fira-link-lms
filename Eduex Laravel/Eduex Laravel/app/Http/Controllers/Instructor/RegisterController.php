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

        try {
            $user = new User([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'instructor',
                'is_approved' => false,
                'phone' => $request->phone,
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

            ToastMagic::success('Your instructor application has been submitted successfully! We will review it and get back to you soon.');
            return redirect()->route('login');
        } catch (\Exception $e) {
            ToastMagic::error('An error occurred during registration. Please try again.');
            return back()->withInput();
        }
    }
}

