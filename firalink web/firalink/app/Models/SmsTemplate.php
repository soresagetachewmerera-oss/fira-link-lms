<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'content',
        'allowed_variables',
        'is_enabled',
    ];

    protected $casts = [
        'allowed_variables' => 'array',
        'is_enabled' => 'boolean',
    ];

    /**
     * Render the template with the provided variable values.
     */
    public function render(array $data = []): string
    {
        $message = $this->content;

        foreach ($data as $variable => $value) {
            $message = str_replace('{' . $variable . '}', (string) $value, $message);
        }

        return $message;
    }

    /**
     * Get default templates to seed.
     */
    public static function defaultTemplates(): array
    {
        return [
            [
                'key' => 'otp',
                'name' => 'OTP Verification Code',
                'content' => 'Your {app_name} verification code is: {otp}. It expires in {expiry} minutes. Do not share this code with anyone.',
                'allowed_variables' => ['app_name', 'otp', 'expiry', 'name'],
                'is_enabled' => true,
            ],
            [
                'key' => 'registration_welcome',
                'name' => 'Registration Welcome',
                'content' => 'Welcome to {app_name}, {name}! Your account has been registered successfully. Start learning today.',
                'allowed_variables' => ['app_name', 'name', 'phone'],
                'is_enabled' => true,
            ],
            [
                'key' => 'course_enrollment',
                'name' => 'Course Enrollment Confirmation',
                'content' => 'Congratulations {name}! You have been successfully enrolled in "{course_title}" on {app_name}. Access your course now.',
                'allowed_variables' => ['app_name', 'name', 'course_title'],
                'is_enabled' => true,
            ],
            [
                'key' => 'payment_confirmation',
                'name' => 'Payment Confirmation (Chapa)',
                'content' => 'Dear {name}, your payment of {amount} ETB for {item_title} was confirmed successfully. Ref: {tx_ref}. Thank you for choosing {app_name}!',
                'allowed_variables' => ['app_name', 'name', 'amount', 'item_title', 'tx_ref'],
                'is_enabled' => true,
            ],
            [
                'key' => 'password_reset',
                'name' => 'Password Reset Code',
                'content' => 'Your {app_name} password reset code is: {otp}. Valid for {expiry} minutes. If you did not request this, please secure your account.',
                'allowed_variables' => ['app_name', 'otp', 'expiry', 'name'],
                'is_enabled' => true,
            ],
            [
                'key' => 'cohort_announcement',
                'name' => 'Class / Cohort Announcement',
                'content' => 'Notice from {app_name}: {announcement_title} - {message_body}. Check your student portal for full details.',
                'allowed_variables' => ['app_name', 'name', 'announcement_title', 'message_body'],
                'is_enabled' => true,
            ],
        ];
    }
}
