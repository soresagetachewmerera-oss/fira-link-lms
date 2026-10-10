<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsRepository;
use App\Services\FileUploadService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;

/**
 * Settings Controller
 *
 * This controller handles the management of settings.
 *
 * @package App\Http\Controllers\Admin
 */
class SettingsController extends Controller
{
    public function __construct(private SettingsRepository $settings, private FileUploadService $fileUploadService)
    {
    }

    /**
     * Display the settings index
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $settingsSections = [
            [
                'title' => 'Platform Configuration',
                'description' => 'Manage general site information, branding, and contact details.',
                'icon' => 'fas fa-sliders-h',
                'route' => route('admin.settings.platform'),
                'badge' => null,
            ],
            [
                'title' => 'AI Configuration',
                'description' => 'Configure AI providers and models.',
                'icon' => 'fas fa-robot',
                'route' => route('admin.settings.ai'),
                'badge' => 'New',
            ],
            [
                'title' => 'Payment Gateways',
                'description' => 'Configure online gateways and offline payment instructions.',
                'icon' => 'fas fa-credit-card',
                'route' => route('admin.settings.gateways.index'),
                'badge' => null,
            ],
            [
                'title' => 'Email & Notifications',
                'description' => 'Update email templates, notification preferences, and sender details.',
                'icon' => 'fas fa-envelope-open-text',
                'route' => route('admin.settings.email'),
                'badge' => null,
            ],
            [
                'title' => 'Course Settings',
                'description' => 'Adjust course approval rules, categories, and defaults.',
                'icon' => 'fas fa-book-open',
                'route' => route('admin.settings.course'),
                'badge' => null,
            ],
            [
                'title' => 'Account Deletion Requests',
                'description' => 'Review and process pending account deletion requests.',
                'icon' => 'fas fa-user-slash',
                'route' => route('admin.settings.account_deletion'),
                'badge' => null,
            ],
            [
                'title' => 'Branding & Assets',
                'description' => 'Configure logos, favicon, and brand palette.',
                'icon' => 'fas fa-paint-brush',
                'route' => route('admin.settings.branding'),
                'badge' => null,
            ],
            [
                'title' => 'Contact Details',
                'description' => 'Manage primary contact information shared across the platform.',
                'icon' => 'fas fa-address-book',
                'route' => route('admin.settings.contact'),
                'badge' => null,
            ],
            [
                'title' => 'Authentication & OTP',
                'description' => 'Control login verification, OTP, and security thresholds.',
                'icon' => 'fas fa-shield-alt',
                'route' => route('admin.settings.authentication'),
                'badge' => null,
            ],
            [
                'title' => 'SMTP Credentials',
                'description' => 'Update outgoing mail server credentials and sender identity.',
                'icon' => 'fas fa-mail-bulk',
                'route' => route('admin.settings.smtp'),
                'badge' => null,
            ],
            [
                'title' => 'Recaptcha Configuration',
                'description' => 'Manage Recaptcha keys and anti-bot settings.',
                'icon' => 'fas fa-robot',
                'route' => route('admin.settings.recaptcha'),
                'badge' => null,
            ],
            [
                'title' => 'Revenue Distribution',
                'description' => 'Control platform commission structure for course sales.',
                'icon' => 'fas fa-chart-pie',
                'route' => route('admin.settings.revenue'),
                'badge' => null,
            ],
            [
                'title' => 'Withdrawal Settings',
                'description' => 'Set minimum payout thresholds and withdrawal rules.',
                'icon' => 'fas fa-hand-holding-usd',
                'route' => route('admin.settings.withdrawals'),
                'badge' => null,
            ],
            [
                'title' => 'Chat Configuration',
                'description' => 'Configure student-instructor messaging, polling, and file uploads.',
                'icon' => 'fas fa-comments',
                'route' => route('admin.settings.chat'),
                'badge' => 'New',
            ],
            [
                'title' => 'Live Class Configuration',
                'description' => 'Enable/disable live stream lessons, Zoom/Meet schedules, and configure reminder times.',
                'icon' => 'fas fa-video',
                'route' => route('admin.settings.live_class'),
                'badge' => 'New',
            ],
            [
                'title' => 'Custom CSS & JS',
                'description' => 'Inject custom CSS, JavaScript, and header/footer scripts into the frontend.',
                'icon' => 'fas fa-code',
                'route' => route('admin.settings.custom_scripts'),
                'badge' => null,
            ],
            [
                'title' => 'Cron Jobs & Scheduler',
                'description' => 'Configure server cron jobs, live class reminders, and view scheduled task status.',
                'icon' => 'fas fa-clock',
                'route' => route('admin.settings.cron_setup'),
                'badge' => 'New',
            ],
        ];

        return view('admin.settings.index', compact('settingsSections'));
    }

    /**
     * Get the currency options
     *
     * @return array
     */
    protected function currencyOptions(): array
    {
        return [
            'USD' => 'USD · United States Dollar',
            'EUR' => 'EUR · Euro',
            'GBP' => 'GBP · British Pound',
            'INR' => 'INR · Indian Rupee',
            'BDT' => 'BDT · Bangladeshi Taka',
            'PKR' => 'PKR · Pakistani Rupee',
            'AUD' => 'AUD · Australian Dollar',
            'CAD' => 'CAD · Canadian Dollar',
            'SGD' => 'SGD · Singapore Dollar',
            'NZD' => 'NZD · New Zealand Dollar',
            'JPY' => 'JPY · Japanese Yen',
            'CNY' => 'CNY · Chinese Yuan',
            'AED' => 'AED · UAE Dirham',
            'SAR' => 'SAR · Saudi Riyal',
            'ZAR' => 'ZAR · South African Rand',
            'BRL' => 'BRL · Brazilian Real',
            'MXN' => 'MXN · Mexican Peso',
            'PHP' => 'PHP · Philippine Peso',
            'HKD' => 'HKD · Hong Kong Dollar',
            'CHF' => 'CHF · Swiss Franc',
            'SEK' => 'SEK · Swedish Krona',
            'NOK' => 'NOK · Norwegian Krone',
            'DKK' => 'DKK · Danish Krone',
            'PLN' => 'PLN · Polish Zloty',
            'CZK' => 'CZK · Czech Koruna',
            'HUF' => 'HUF · Hungarian Forint',
            'ILS' => 'ILS · Israeli New Shekel',
            'TRY' => 'TRY · Turkish Lira',
            'RUB' => 'RUB · Russian Ruble',
            'UAH' => 'UAH · Ukrainian Hryvnia',
            'KRW' => 'Korean Won',
            'MYR' => 'MYR · Malaysian Ringgit',
            'THB' => 'THB · Thai Baht',
            'IDR' => 'IDR · Indonesian Rupiah',
            'VND' => 'VND · Vietnamese Dong',
            'TND' => 'TND · Tunisian Dinar',
            'MAD' => 'MAD · Moroccan Dirham',
            'NGN' => 'NGN · Nigerian Naira',
            'KES' => 'KES · Kenyan Shilling',
            'TZS' => 'TZS · Tanzanian Shilling',
            'UGX' => 'UGX · Ugandan Shilling',
            'ZMW' => 'ZMW · Zambian Kwacha',
            'MWK' => 'MWK · Malawian Kwacha',
            'BIF' => 'BIF · Burundian Franc',
            'ETB' => 'ETB · Ethiopian Birr',
            'GHS' => 'GHS · Ghanaian Cedi',

            'NAD' => 'NAD · Namibian Dollar',
            'GMD' => 'GMD · Gambian Dalasi',
            'SLL' => 'SLL · Sierra Leonean Leone',
            'SCR' => 'SCR · Seychellois Rupee',
            'LRD' => 'LRD · Liberian Dollar',
            'GIP' => 'GIP · Gibraltar Pound',
            'FKP' => 'FKP · Falkland Islands Pound',
        ];
    }

    /**
     * Display the platform settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function platform()
    {
        $settings = $this->settings->get('platform.general', []);
        $currencyOptions = $this->currencyOptions();
        $selectedCurrency = $settings['default_currency'] ?? 'USD';

        return view('admin.settings.platform', compact('settings', 'currencyOptions', 'selectedCurrency'));
    }

    /**
     * Update the platform settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePlatform(Request $request)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'max:120'],
            'default_language' => ['required', 'string', 'max:10'],
            'default_currency' => ['required', 'string', 'max:10'],
        ]);

        $this->settings->set('platform.general', $data, 'platform', 'array');

        ToastMagic::success('Platform configuration updated successfully.');

        return back();
    }

    /**
     * Display the email notifications settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function emailNotifications()
    {
        $settings = $this->settings->get('email.preferences', []);

        return view('admin.settings.email', compact('settings'));
    }

    /**
     * Update the email notifications settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateEmailNotifications(Request $request)
    {
        $data = [
            'send_welcome_email' => $request->boolean('send_welcome_email'),
            'notify_admin_new_registration' => $request->boolean('notify_admin_new_registration'),
            'course_update_digest' => $request->boolean('course_update_digest'),
            'marketing_opt_in_default' => $request->boolean('marketing_opt_in_default'),
        ];

        $this->settings->set('email.preferences', $data, 'email_notifications', 'array');

        ToastMagic::success('Email & notification preferences updated.');

        return back();
    }

    /**
     * Display the SMTP settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function smtp()
    {
        $settings = $this->settings->get('smtp.credentials', []);

        return view('admin.settings.smtp', compact('settings'));
    }

    /**
     * Update the SMTP settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSmtp(Request $request)
    {
        $existing = $this->settings->get('smtp.credentials', []);

        $data = $request->validate([
            'host' => ['required', 'string', 'max:191'],
            'port' => ['required', 'integer'],
            'encryption' => ['nullable', 'string', 'max:10'],
            'username' => ['nullable', 'string', 'max:191'],
            'password' => ['nullable', 'string', 'max:191'],
            'from_address' => ['required', 'email', 'max:191'],
            'from_name' => ['required', 'string', 'max:191'],
        ]);

        if (blank($data['password'] ?? null) && isset($existing['password'])) {
            $data['password'] = $existing['password'];
        }

        $this->settings->set('smtp.credentials', $data, 'smtp', 'array');

        ToastMagic::success('SMTP settings saved.');

        return back();
    }

    /**
     * Display the account deletion settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function accountDeletion()
    {
        $settings = $this->settings->get('account_deletion.preferences', []);

        return view('admin.settings.account-deletion', compact('settings'));
    }

    /**
     * Update the account deletion settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateAccountDeletion(Request $request)
    {
        $data = $request->validate([
            'require_admin_approval' => ['nullable', 'boolean'],
            'auto_archive_instructor_courses' => ['nullable', 'boolean'],
            'notify_admin_email' => ['required', 'email', 'max:191'],
        ]);

        $payload = [
            'require_admin_approval' => (bool) ($data['require_admin_approval'] ?? false),
            'auto_archive_instructor_courses' => (bool) ($data['auto_archive_instructor_courses'] ?? false),
            'notify_admin_email' => $data['notify_admin_email'],
        ];

        $this->settings->set('account_deletion.preferences', $payload, 'account_deletion', 'array');

        ToastMagic::success('Account deletion settings updated.');

        return back();
    }

    /**
     * Display the course policies settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function coursePolicies()
    {
        $settings = $this->settings->get('course.policies', []);

        return view('admin.settings.course', compact('settings'));
    }

    /**
     * Update the course policies settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCoursePolicies(Request $request)
    {
        $data = $request->validate([
            'auto_publish_courses' => ['nullable', 'boolean'],
            'require_review_for_updates' => ['nullable', 'boolean'],
            'default_visibility' => ['required', 'string', 'max:30'],
            'max_topic_depth' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $payload = [
            'auto_publish_courses' => (bool) ($data['auto_publish_courses'] ?? false),
            'require_review_for_updates' => (bool) ($data['require_review_for_updates'] ?? false),
            'default_visibility' => $data['default_visibility'],
            'max_topic_depth' => $data['max_topic_depth'],
        ];

        $this->settings->set('course.policies', $payload, 'course', 'array');

        ToastMagic::success('Course policies saved.');

        return back();
    }

    /**
     * Display the branding settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function branding()
    {
        $settings = $this->settings->get('branding.assets', []);

        return view('admin.settings.branding', compact('settings'));
    }

    /**
     * Update the branding settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateBranding(Request $request)
    {
        $validated = $request->validate([
            'primary_logo' => ['nullable', 'image', 'max:2048'],
            'dark_logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'image', 'max:1024'],
            'primary_color' => ['required', 'string', 'max:20'],
            'secondary_color' => ['required', 'string', 'max:20'],
        ]);

        $current = $this->settings->get('branding.assets', []);
        $payload = $current;

        if ($request->hasFile('primary_logo')) {
            if (!empty($current['primary_logo_path'])) {
                $this->fileUploadService->deleteFile($current['primary_logo_path']);
            }

            $payload['primary_logo_path'] = $this->fileUploadService->uploadBrandAsset($request->file('primary_logo'), 'logos');
        }

        if ($request->hasFile('dark_logo')) {
            if (!empty($current['dark_logo_path'])) {
                $this->fileUploadService->deleteFile($current['dark_logo_path']);
            }

            $payload['dark_logo_path'] = $this->fileUploadService->uploadBrandAsset($request->file('dark_logo'), 'logos');
        }

        if ($request->hasFile('favicon')) {
            if (!empty($current['favicon_path'])) {
                $this->fileUploadService->deleteFile($current['favicon_path']);
            }

            $payload['favicon_path'] = $this->fileUploadService->uploadBrandAsset($request->file('favicon'), 'favicons');
        }

        $payload['primary_color'] = $validated['primary_color'];
        $payload['secondary_color'] = $validated['secondary_color'];

        if (!isset($payload['dark_logo_path']) && isset($current['dark_logo_path'])) {
            $payload['dark_logo_path'] = $current['dark_logo_path'];
        }

        $this->settings->set('branding.assets', $payload, 'branding', 'array');

        ToastMagic::success('Branding settings updated.');

        return back();
    }

    /**
     * Display the chat configuration settings
     */
    public function chat()
    {
        $settings = $this->settings->get('chat.config', [
            'enabled'            => true,
            'poll_timeout'       => 25,
            'poll_interval'      => 2,
            'max_file_size'      => 10240,
            'messages_per_page'  => 50,
            'allowed_file_types' => 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,zip',
        ]);

        return view('admin.settings.chat', compact('settings'));
    }

    /**
     * Update the chat configuration settings
     */
    public function updateChat(Request $request)
    {
        $validated = $request->validate([
            'poll_timeout'       => ['required', 'integer', 'min:5', 'max:60'],
            'poll_interval'      => ['required', 'integer', 'min:1', 'max:10'],
            'max_file_size'      => ['required', 'integer', 'min:512', 'max:51200'],
            'messages_per_page'  => ['required', 'integer', 'min:10', 'max:200'],
            'allowed_file_types' => ['required', 'string', 'max:500'],
        ]);

        $validated['enabled'] = $request->boolean('enabled');

        $this->settings->set('chat.config', $validated, 'chat', 'array');

        ToastMagic::success('Chat configuration updated.');

        return back();
    }

    /**
     * Display the contact settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function contact()
    {
        $settings = $this->settings->get('contact.details', []);

        return view('admin.settings.contact', compact('settings'));
    }

    /**
     * Update the contact settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateContact(Request $request)
    {
        $data = $request->validate([
            'support_email' => ['required', 'email', 'max:191'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'address_line_1' => ['nullable', 'string', 'max:191'],
            'address_line_2' => ['nullable', 'string', 'max:191'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
        ]);

        $this->settings->set('contact.details', $data, 'contact', 'array');

        ToastMagic::success('Contact information updated.');

        return back();
    }

    /**
     * Display the authentication settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function authentication()
    {
        $settings = $this->settings->get('authentication.security', []);

        return view('admin.settings.authentication', compact('settings'));
    }

    /**
     * Update the authentication settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateAuthentication(Request $request)
    {
        $data = $request->validate([
            'email_verification_required' => ['nullable', 'boolean'],
            'login_otp_enabled' => ['nullable', 'boolean'],
            'otp_expiry_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'recaptcha_on_login' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'email_verification_required' => (bool) ($data['email_verification_required'] ?? false),
            'login_otp_enabled' => (bool) ($data['login_otp_enabled'] ?? false),
            'otp_expiry_minutes' => $data['otp_expiry_minutes'],
            'recaptcha_on_login' => (bool) ($data['recaptcha_on_login'] ?? false),
        ];

        $this->settings->set('authentication.security', $payload, 'authentication', 'array');

        ToastMagic::success('Authentication settings saved.');

        return back();
    }

    /**
     * Display the recaptcha settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function recaptcha()
    {
        $settings = $this->settings->get('recaptcha.config', []);

        return view('admin.settings.recaptcha', compact('settings'));
    }

    /**
     * Update the recaptcha settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRecaptcha(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'site_key' => ['nullable', 'string', 'max:191'],
            'secret_key' => ['nullable', 'string', 'max:191'],
            'score_threshold' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'version' => ['required', 'in:v2_checkbox,v3_invisible'],
        ]);

        $payload = [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'site_key' => $data['site_key'] ?? '',
            'secret_key' => $data['secret_key'] ?? '',
            'score_threshold' => $data['score_threshold'] ?? 0.5,
            'version' => $data['version'],
        ];

        $this->settings->set('recaptcha.config', $payload, 'recaptcha', 'array');

        ToastMagic::success('Recaptcha configuration updated.');

        return back();
    }

    /**
     * Display the revenue settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function revenue()
    {
        $settings = $this->settings->get('revenue.distribution', [
            'mode' => 'percentage',
            'value' => 0,
        ]);

        return view('admin.settings.revenue', compact('settings'));
    }

    /**
     * Update the revenue settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRevenue(Request $request)
    {
        $data = $request->validate([
            'mode' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
        ]);

        $mode = $data['mode'];
        $value = (float) $data['value'];

        if ($mode === 'percentage' && $value > 100) {
            return back()->withErrors([
                'value' => 'Percentage commission cannot exceed 100%.',
            ])->withInput();
        }

        $payload = [
            'mode' => $mode,
            'value' => $value,
        ];

        $this->settings->set('revenue.distribution', $payload, 'revenue', 'array');

        ToastMagic::success('Revenue distribution settings updated.');

        return back();
    }

    /**
     * Display the withdrawal settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function withdrawals()
    {
        $currency = currency_code();
        $settings = $this->settings->get('withdrawals.settings', [
            'minimum_amount' => 10,
            'currency' => $currency,
        ]);

        $settings['currency'] = $settings['currency'] ?? $currency;

        return view('admin.settings.withdrawals', compact('settings'));
    }

    /**
     * Update the withdrawal settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateWithdrawals(Request $request)
    {
        $currency = currency_code();

        $data = $request->validate([
            'minimum_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $payload = [
            'minimum_amount' => (float) $data['minimum_amount'],
            'currency' => $currency,
        ];

        $this->settings->set('withdrawals.settings', $payload, 'withdrawals', 'array');

        ToastMagic::success('Withdrawal settings updated.');

        return back();
    }

    /**
     * Display the support settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function support()
    {
        $settings = $this->settings->get('support.channels', []);

        return view('admin.settings.support', compact('settings'));
    }

    /**
     * Update the support settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSupport(Request $request)
    {
        $data = $request->validate([
            'help_center_url' => ['nullable', 'url'],
            'knowledge_base_enabled' => ['nullable', 'boolean'],
            'ticket_portal_enabled' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'help_center_url' => $data['help_center_url'] ?? null,
            'knowledge_base_enabled' => (bool) ($data['knowledge_base_enabled'] ?? false),
            'ticket_portal_enabled' => (bool) ($data['ticket_portal_enabled'] ?? false),
        ];

        $this->settings->set('support.channels', $payload, 'support', 'array');

        ToastMagic::success('Support settings saved.');

        return back();
    }

    /**
     * Display the AI settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function ai()
    {
        $settings = $this->settings->get('ai.config', []);
        $defaultModel = 'gemini-2.5-flash';

        $models = [
            'gemini-pro' => 'Gemini Pro',
            'gemini-1.5-pro' => 'Gemini 1.5 Pro',
            'gemini-1.5-flash' => 'Gemini 1.5 Flash',
            'gemini-2.0-flash' => 'Gemini 2.0 Flash',
            'gemini-2.5-flash' => 'Gemini 2.5 Flash',
        ];

        return view('admin.settings.ai', compact('settings', 'models', 'defaultModel'));
    }

    /**
     * Update the AI settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateAi(Request $request)
    {
        $data = $request->validate([
            'gemini_api_key' => ['nullable', 'string', 'max:255'],
            'gemini_model' => ['required', 'string', 'max:50'],
        ]);

        $this->settings->set('ai.config', $data, 'ai', 'array', true);

        ToastMagic::success('AI configuration updated.');

        return back();
    }

    /**
     * Display the custom scripts settings
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function customScripts()
    {
        $settings = $this->settings->get('platform.custom_scripts', [
            'custom_css' => '',
            'custom_js' => '',
            'header_script' => '',
            'footer_script' => '',
        ]);

        return view('admin.settings.custom-scripts', compact('settings'));
    }

    /**
     * Update the custom scripts settings
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCustomScripts(Request $request)
    {
        $data = $request->validate([
            'custom_css' => ['nullable', 'string'],
            'custom_js' => ['nullable', 'string'],
            'header_script' => ['nullable', 'string'],
            'footer_script' => ['nullable', 'string'],
        ]);

        $this->settings->set('platform.custom_scripts', $data, 'custom_scripts', 'array');

        ToastMagic::success('Custom scripts updated successfully.');

        return back();
    }

    /**
     * Display the cron job setup page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function cronSetup()
    {
        $phpBinary = PHP_BINARY ?: '/usr/local/bin/php';
        $projectRoot = base_path();

        // Read cron status from the status file
        $cronStatus = null;
        $statusFile = storage_path('app/cron_last_run.json');
        if (file_exists($statusFile)) {
            $cronStatus = json_decode(file_get_contents($statusFile), true);
        }

        // Check if cron_key is set, if not, generate one automatically
        $cronKey = $this->settings->get('cron_key');
        if (empty($cronKey)) {
            $cronKey = bin2hex(random_bytes(16));
            $this->settings->set('cron_key', $cronKey, 'live_class', 'string');
        }

        // Get cron settings
        $settings = [
            'cron_key' => $cronKey,
        ];

        return view('admin.settings.cron-setup', compact('phpBinary', 'projectRoot', 'cronStatus', 'settings'));
    }

    /**
     * Update cron-related settings (cron key)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateCronSetup(Request $request)
    {
        $data = $request->validate([
            'cron_key' => ['required', 'string', 'min:8', 'max:64'],
        ]);

        $this->settings->set('cron_key', trim($data['cron_key']), 'live_class', 'string');

        ToastMagic::success('Scheduler configuration updated.');

        return back();
    }

    /**
     * Display the live class settings page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function liveClassSettings()
    {
        $settings = [
            'live_class_reminder_minutes' => (int) $this->settings->get('live_class_reminder_minutes', 30),
            'live_class_enabled' => (bool) $this->settings->get('live_class_enabled', true),
        ];

        return view('admin.settings.live-class', compact('settings'));
    }

    /**
     * Update live class settings (enable toggle, reminder minutes)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateLiveClassSettings(Request $request)
    {
        $data = $request->validate([
            'live_class_reminder_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'live_class_enabled' => ['nullable', 'in:0,1,true,false,on,off'],
        ]);

        $enabled = filter_var($request->input('live_class_enabled', false), FILTER_VALIDATE_BOOLEAN);

        $this->settings->set('live_class_reminder_minutes', (int) $data['live_class_reminder_minutes'], 'live_class', 'integer');
        $this->settings->set('live_class_enabled', $enabled, 'live_class', 'boolean');

        ToastMagic::success('Live class configuration updated.');

        return back();
    }
}
