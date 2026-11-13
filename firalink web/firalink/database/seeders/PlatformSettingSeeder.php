<?php

namespace Database\Seeders;

use App\Services\SettingsRepository;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var SettingsRepository $repository */
        $repository = app(SettingsRepository::class);

        foreach ($this->defaults() as $key => $definition) {
            $repository->set(
                $key,
                $definition['value'],
                $definition['group'],
                $definition['type'] ?? (is_array($definition['value']) ? 'array' : gettype($definition['value'])),
                $definition['encrypted'] ?? false
            );
        }
    }

    protected function defaults(): array
    {
        return [
            'platform.general' => [
                'group' => 'platform',
                'type' => 'array',
                'value' => [
                    'site_name' => config('app.name', 'EduEx'),
                    'tagline' => 'Empower your learning journey',
                    'timezone' => config('app.timezone', 'UTC'),
                    'default_language' => config('app.locale', 'en'),
                    'default_currency' => config('app.currency', 'USD'),
                    'system_version' => '2.0.0',
                ],
            ],
            'email.preferences' => [
                'group' => 'email_notifications',
                'type' => 'array',
                'value' => [
                    'send_welcome_email' => true,
                    'notify_admin_new_registration' => true,
                    'course_update_digest' => true,
                    'marketing_opt_in_default' => false,
                ],
            ],
            'smtp.credentials' => [
                'group' => 'smtp',
                'type' => 'array',
                'value' => [
                    'host' => config('mail.mailers.smtp.host', ''),
                    'port' => config('mail.mailers.smtp.port', 587),
                    'encryption' => config('mail.mailers.smtp.encryption', 'tls'),
                    'username' => config('mail.mailers.smtp.username', ''),
                    'password' => null,
                    'from_address' => config('mail.from.address', 'noreply@example.com'),
                    'from_name' => config('mail.from.name', config('app.name', 'EduEx')),
                ],
            ],
            'account_deletion.preferences' => [
                'group' => 'account_deletion',
                'type' => 'array',
                'value' => [
                    'require_admin_approval' => true,
                    'auto_archive_instructor_courses' => true,
                    'notify_admin_email' => config('mail.from.address', 'admin@example.com'),
                ],
            ],
            'course.policies' => [
                'group' => 'course',
                'type' => 'array',
                'value' => [
                    'auto_publish_courses' => false,
                    'require_review_for_updates' => true,
                    'default_visibility' => 'private',
                    'max_topic_depth' => 3,
                ],
            ],
            'branding.assets' => [
                'group' => 'branding',
                'type' => 'array',
                'value' => [
                    'primary_logo_path' => 'assets/front/img/logo/logo.png',
                    'dark_logo_path' => 'assets/front/img/logo/logo-dark.png',
                    'favicon_path' => 'assets/front/img/favicon.png',
                    'primary_color' => '#6558f5',
                    'secondary_color' => '#f59e0b',
                ],
            ],
            'contact.details' => [
                'group' => 'contact',
                'type' => 'array',
                'value' => [
                    'support_email' => config('mail.from.address', 'support@example.com'),
                    'support_phone' => '+1 (555) 010-0900',
                    'address_line_1' => '123 Learning Way',
                    'address_line_2' => 'Suite 400',
                    'city' => 'Skillstown',
                    'country' => 'USA',
                ],
            ],
            'authentication.security' => [
                'group' => 'authentication',
                'type' => 'array',
                'value' => [
                    'email_verification_required' => false,
                    'login_otp_enabled' => false,
                    'otp_expiry_minutes' => 10,
                    'recaptcha_on_login' => false,
                ],
            ],
            'recaptcha.config' => [
                'group' => 'recaptcha',
                'type' => 'array',
                'value' => [
                    'enabled' => false,
                    'site_key' => env('RECAPTCHA_SITE_KEY', ''),
                    'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
                    'score_threshold' => 0.5,
                    'version' => 'v2_checkbox',
                ],
            ],
            'revenue.distribution' => [
                'group' => 'revenue',
                'type' => 'array',
                'value' => [
                    'mode' => 'percentage',
                    'value' => 20,
                ],
            ],
            'withdrawals.settings' => [
                'group' => 'withdrawals',
                'type' => 'array',
                'value' => [
                    'minimum_amount' => 10,
                    'currency' => config('app.currency', 'USD'),
                ],
            ],
            'support.channels' => [
                'group' => 'support',
                'type' => 'array',
                'value' => [
                    'help_center_url' => 'https://support.example.com',
                    'knowledge_base_enabled' => false,
                    'ticket_portal_enabled' => false,
                ],
            ],
        ];
    }
}
