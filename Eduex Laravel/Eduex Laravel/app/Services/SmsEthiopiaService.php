<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * SmsEthiopiaService
 *
 * Official implementation of SMS Ethiopia Gateway:
 * - API Endpoint: https://smsethiopia.com/api/sms/send
 * - Authentication: Header "KEY: <API_KEY>"
 * - Request Payload: {"msisdn": "2519XXXXXXXX", "text": "Message"}
 * - Response: {"sent": true, "id": 0, "description": "Accepted for delivery"}
 *
 * Fully dynamic via SettingsRepository (PlatformSetting) with fallback to config/env.
 * Sanitized audit logging in `sms_logs` table.
 *
 * @package App\Services
 */
class SmsEthiopiaService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $senderId;
    protected bool $enabled;
    protected int $timeout;

    public function __construct()
    {
        $this->apiUrl   = (string) settings('sms.provider.api_url', config('services.sms_ethiopia.api_url', env('SMS_ETHIOPIA_API_URL', 'https://smsethiopia.com/api/sms/send')));
        $this->apiKey   = (string) settings('sms.provider.api_key', config('services.sms_ethiopia.api_key', env('SMS_ETHIOPIA_API_KEY', '')));
        $this->senderId = (string) settings('sms.provider.sender_id', config('services.sms_ethiopia.sender_id', env('SMS_ETHIOPIA_SENDER_ID', 'FiraLink')));
        
        $globalEnabled  = settings('sms.provider.enabled', env('SMS_ETHIOPIA_ENABLED', true));
        $this->enabled  = filter_var($globalEnabled, FILTER_VALIDATE_BOOLEAN);
        $this->timeout  = (int) settings('sms.provider.timeout', 15);
        if ($this->timeout <= 0) {
            $this->timeout = 15;
        }
    }

    /**
     * Check if the SMS delivery service is globally enabled and has an API key configured.
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->apiKey);
    }

    /**
     * Normalize an Ethiopian phone number to official E.164 without leading plus: 2519XXXXXXXX or 2517XXXXXXXX.
     *
     * Accepted inputs:
     * - "0911234567"  -> "251911234567"
     * - "0711234567"  -> "251711234567"
     * - "911234567"   -> "251911234567"
     * - "+251911234567" -> "251911234567"
     * - "251911234567" -> "251911234567"
     */
    public function normalizePhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Remove non-digit characters except leading plus
        $clean = preg_replace('/[^\d+]/', '', trim($phone));

        if (empty($clean)) {
            return null;
        }

        // Strip leading '+'
        if (str_starts_with($clean, '+')) {
            $clean = substr($clean, 1);
        }

        // If local format starting with 0: 09XXXXXXXX or 07XXXXXXXX
        if (preg_match('/^0([79]\d{8})$/', $clean, $matches)) {
            return '251' . $matches[1];
        }

        // If 9 digits starting with 7 or 9
        if (preg_match('/^([79]\d{8})$/', $clean, $matches)) {
            return '251' . $matches[1];
        }

        // If standard 12 digits: 2519XXXXXXXX or 2517XXXXXXXX
        if (preg_match('/^251[79]\d{8}$/', $clean)) {
            return $clean;
        }

        // Fallback: general 251 followed by 9 digits
        if (preg_match('/^251\d{9}$/', $clean)) {
            return $clean;
        }

        return $clean;
    }

    /**
     * Validate whether a phone number matches Ethiopian mobile format.
     */
    public function isValidEthiopianNumber(?string $phone): bool
    {
        $normalized = $this->normalizePhoneNumber($phone);
        if (!$normalized) {
            return false;
        }

        // Ethiopian mobile numbers are 12 digits: 251 + (9 or 7) + 8 digits
        return (bool) preg_match('/^251[79]\d{8}$/', $normalized);
    }

    /**
     * Send a single SMS to an Ethiopian recipient following official SMS Ethiopia API specs.
     *
     * @param string $to
     * @param string $message
     * @param string|null $templateKey
     * @param User|null $user
     * @return array
     */
    public function send(string $to, string $message, ?string $templateKey = null, ?User $user = null): array
    {
        $normalizedRecipient = $this->normalizePhoneNumber($to);
        $maskedPhone = SmsLog::maskPhone($to);
        $preview = SmsLog::sanitizeMessage($message);
        $ip = request()->ip() ?? '127.0.0.1';

        // 1. Validation check
        if (!$this->isValidEthiopianNumber($to)) {
            $err = "Invalid Ethiopian phone number: '{$maskedPhone}'. Must start with 09, 07, or +251.";
            Log::warning("SMS Ethiopia rejected number: {$err}");

            SmsLog::create([
                'user_id'           => $user?->id,
                'recipient'         => $to,
                'recipient_masked'  => $maskedPhone,
                'message_preview'   => $preview,
                'template_key'      => $templateKey,
                'status'            => 'invalid_number',
                'provider_response' => ['error' => 'invalid_ethiopian_phone'],
                'error_message'     => $err,
                'ip_address'        => $ip,
            ]);

            return [
                'success' => false,
                'message' => $err,
                'code'    => 'INVALID_NUMBER',
            ];
        }

        // 2. Disabled / Missing API key check
        if (!$this->isEnabled()) {
            $reason = empty($this->apiKey) ? 'SMS Ethiopia API key is not configured.' : 'SMS service is globally disabled in platform settings.';
            Log::info("SMS Ethiopia skipped: {$reason}", ['recipient' => $maskedPhone, 'template' => $templateKey]);

            SmsLog::create([
                'user_id'           => $user?->id,
                'recipient'         => $normalizedRecipient,
                'recipient_masked'  => $maskedPhone,
                'message_preview'   => $preview,
                'template_key'      => $templateKey,
                'status'            => 'disabled',
                'provider_response' => ['status' => 'disabled', 'reason' => $reason],
                'error_message'     => $reason,
                'ip_address'        => $ip,
            ]);

            return [
                'success' => false,
                'message' => $reason,
                'code'    => 'DISABLED',
            ];
        }

        // 3. Official API Request construction
        // Official specs: POST to https://smsethiopia.com/api/sms/send
        // Headers: KEY: <API_KEY>, Content-Type: application/json
        // Body: {"msisdn": "...", "text": "..."}
        $payload = [
            'msisdn' => $normalizedRecipient,
            'text'   => $message,
        ];

        try {
            $ch = curl_init($this->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'KEY: ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

            $rawResponse = curl_exec($ch);
            $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError   = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                $err = 'Connection timeout or cURL error: ' . $curlError;
                Log::error('SMS Ethiopia cURL error', ['error' => $curlError, 'recipient' => $maskedPhone]);

                SmsLog::create([
                    'user_id'           => $user?->id,
                    'recipient'         => $normalizedRecipient,
                    'recipient_masked'  => $maskedPhone,
                    'message_preview'   => $preview,
                    'template_key'      => $templateKey,
                    'status'            => 'failed',
                    'provider_response' => ['curl_error' => $curlError],
                    'error_message'     => $err,
                    'ip_address'        => $ip,
                ]);

                return [
                    'success' => false,
                    'message' => $err,
                    'code'    => 'CURL_ERROR',
                ];
            }

            $responseData = json_decode($rawResponse, true) ?? [];
            
            // Official SMS Ethiopia response format:
            // Success: {"sent": true, "id": 0, "description": "Accepted for delivery"}
            $isSent = false;
            if ($httpCode >= 200 && $httpCode < 300) {
                if (isset($responseData['sent']) && $responseData['sent'] === true) {
                    $isSent = true;
                } elseif (isset($responseData['status']) && strtolower((string)$responseData['status']) === 'success') {
                    $isSent = true;
                }
            }

            $statusStr = $isSent ? 'delivered' : 'failed';
            $errMsg = null;
            if (!$isSent) {
                $errMsg = $responseData['description'] 
                    ?? $responseData['message'] 
                    ?? $responseData['error'] 
                    ?? "Provider returned HTTP {$httpCode}";
            }

            // Log outcome
            SmsLog::create([
                'user_id'           => $user?->id,
                'recipient'         => $normalizedRecipient,
                'recipient_masked'  => $maskedPhone,
                'message_preview'   => $preview,
                'template_key'      => $templateKey,
                'status'            => $statusStr,
                'provider_response' => $responseData,
                'error_message'     => $errMsg,
                'ip_address'        => $ip,
            ]);

            Log::info('SMS Ethiopia dispatch completed', [
                'recipient' => $maskedPhone,
                'http_code' => $httpCode,
                'sent'      => $isSent,
                'response'  => $responseData,
            ]);

            return [
                'success'     => $isSent,
                'http_code'   => $httpCode,
                'message'     => $isSent ? 'SMS sent successfully.' : ($errMsg ?? 'SMS dispatch failed.'),
                'data'        => $responseData,
                'provider_id' => $responseData['id'] ?? null,
            ];
        } catch (\Throwable $e) {
            $err = 'SMS Service Exception: ' . $e->getMessage();
            Log::error($err, ['trace' => $e->getTraceAsString()]);

            SmsLog::create([
                'user_id'           => $user?->id,
                'recipient'         => $normalizedRecipient,
                'recipient_masked'  => $maskedPhone,
                'message_preview'   => $preview,
                'template_key'      => $templateKey,
                'status'            => 'failed',
                'provider_response' => ['exception' => $e->getMessage()],
                'error_message'     => $err,
                'ip_address'        => $ip,
            ]);

            return [
                'success' => false,
                'message' => $err,
                'code'    => 'EXCEPTION',
            ];
        }
    }

    /**
     * Send SMS by rendering a registered dynamic template.
     *
     * @param string $to
     * @param string $templateKey
     * @param array $data
     * @param User|null $user
     * @return array
     */
    public function sendFromTemplate(string $to, string $templateKey, array $data = [], ?User $user = null): array
    {
        $template = SmsTemplate::where('key', $templateKey)->first();

        if (!$template) {
            if ($templateKey === 'otp_verification') {
                $template = SmsTemplate::where('key', 'otp')->first();
            } elseif ($templateKey === 'otp') {
                $template = SmsTemplate::where('key', 'otp_verification')->first();
            }
        }

        if (!$template) {
            Log::warning("SMS template '{$templateKey}' not found.");
            return [
                'success' => false,
                'message' => "SMS template '{$templateKey}' not found.",
                'code'    => 'TEMPLATE_NOT_FOUND',
            ];
        }

        if (!$template->is_enabled) {
            Log::info("SMS template '{$templateKey}' is currently disabled.");
            return [
                'success' => false,
                'message' => "SMS template '{$templateKey}' is currently disabled.",
                'code'    => 'TEMPLATE_DISABLED',
            ];
        }

        // Default variables
        if (!isset($data['app_name'])) {
            $data['app_name'] = config('app.name', 'Fira Link');
        }
        if ($user && !isset($data['name'])) {
            $data['name'] = $user->name ?? 'Student';
        }

        $message = $template->render($data);

        return $this->send($to, $message, $template->key, $user);
    }

    /**
     * Send OTP verification code using dynamic template 'otp'.
     *
     * @param string $phone
     * @param string $code
     * @param int $expiryMinutes
     * @param User|null $user
     * @return array
     */
    public function sendOtp(string $phone, string $code, int $expiryMinutes = 10, ?User $user = null): array
    {
        $data = [
            'otp'     => $code,
            'expiry'  => $expiryMinutes,
            'minutes' => $expiryMinutes,
            'name'    => $user?->name ?? 'Learner',
        ];

        return $this->sendFromTemplate($phone, 'otp', $data, $user);
    }

    /**
     * Send welcome registration SMS.
     *
     * @param User $user
     * @return array
     */
    public function sendWelcomeNotification(User $user): array
    {
        if (empty($user->phone)) {
            return ['success' => false, 'message' => 'User does not have a phone number.'];
        }

        return $this->sendFromTemplate($user->phone, 'registration_welcome', [
            'name' => $user->name,
        ], $user);
    }

    /**
     * Send enrollment confirmation SMS.
     *
     * @param Enrollment $enrollment
     * @return array
     */
    public function sendEnrollmentNotification(Enrollment $enrollment): array
    {
        $user = $enrollment->user;
        if (!$user || empty($user->phone)) {
            return ['success' => false, 'message' => 'User phone not found for enrollment.'];
        }

        $courseTitle = $enrollment->course?->title ?? 'your course';

        return $this->sendFromTemplate($user->phone, 'course_enrollment', [
            'name'         => $user->name,
            'course_title' => $courseTitle,
        ], $user);
    }

    /**
     * Send payment confirmation notification.
     *
     * @param User $user
     * @param string $courseTitle
     * @param float|int|string $amount
     * @param string $reference
     * @return array
     */
    public function sendPaymentConfirmation(User $user, string $courseTitle, $amount, string $reference): array
    {
        if (empty($user->phone)) {
            return ['success' => false, 'message' => 'User phone not found for payment confirmation.'];
        }

        return $this->sendFromTemplate($user->phone, 'payment_confirmation', [
            'name'         => $user->name,
            'item_title'   => $courseTitle,
            'course_title' => $courseTitle,
            'amount'       => number_format((float)$amount, 2),
            'tx_ref'       => $reference,
            'reference'    => $reference,
        ], $user);
    }

    /**
     * Send test SMS to an administrative phone number.
     *
     * @param string $to
     * @param string $text
     * @return array
     */
    public function testConnection(string $to, string $text = 'This is a test SMS from Fira Link via SMS Ethiopia.'): array
    {
        return $this->send($to, $text, 'admin_test');
    }
}
