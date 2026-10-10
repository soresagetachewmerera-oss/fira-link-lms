<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SmsEthiopiaService
 *
 * Handles SMS messaging via the SMS Ethiopia gateway for OTP verification,
 * course enrollments, and broadcast notifications.
 *
 * @package App\Services
 */
class SmsEthiopiaService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $senderId;
    protected bool $enabled;

    public function __construct()
    {
        $this->apiUrl   = config('services.sms_ethiopia.api_url', env('SMS_ETHIOPIA_API_URL', 'https://api.sms-ethiopia.com/v1/send'));
        $this->apiKey   = config('services.sms_ethiopia.api_key', env('SMS_ETHIOPIA_API_KEY', ''));
        $this->senderId = config('services.sms_ethiopia.sender_id', env('SMS_ETHIOPIA_SENDER_ID', 'FiraLink'));
        $this->enabled  = (bool) config('services.sms_ethiopia.enabled', env('SMS_ETHIOPIA_ENABLED', !empty($this->apiKey)));
    }

    /**
     * Check if the SMS service is configured and enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled && !empty($this->apiKey);
    }

    /**
     * Normalize an Ethiopian phone number to international standard (2519XXXXXXXX or 2517XXXXXXXX).
     */
    public function normalizePhoneNumber(string $phone): ?string
    {
        // Strip out non-digit characters except leading plus
        $clean = preg_replace('/[^\d+]/', '', trim($phone));

        if (empty($clean)) {
            return null;
        }

        // Remove leading '+'
        if (str_starts_with($clean, '+')) {
            $clean = substr($clean, 1);
        }

        // If format is 09XXXXXXXX or 07XXXXXXXX, replace leading 0 with 251
        if (preg_match('/^0([79]\d{8})$/', $clean, $matches)) {
            return '251' . $matches[1];
        }

        // If format is 9XXXXXXXX or 7XXXXXXXX (9 digits), prepend 251
        if (preg_match('/^([79]\d{8})$/', $clean, $matches)) {
            return '251' . $matches[1];
        }

        // If already 2519XXXXXXXX or 2517XXXXXXXX (12 digits)
        if (preg_match('/^251[79]\d{8}$/', $clean)) {
            return $clean;
        }

        return $clean;
    }

    /**
     * Send an SMS message to one or multiple recipients.
     *
     * @param string|array $to
     * @param string $message
     * @return array
     */
    public function send(string|array $to, string $message): array
    {
        if (empty($this->apiKey)) {
            Log::info('SMS Ethiopia dispatch skipped: SMS_ETHIOPIA_API_KEY not configured.', [
                'to' => $to,
                'message' => $message,
            ]);
            return [
                'success' => false,
                'message' => 'SMS Ethiopia API key is not configured.',
            ];
        }

        $recipients = is_array($to) ? $to : [$to];
        $normalizedRecipients = [];

        foreach ($recipients as $number) {
            $normalized = $this->normalizePhoneNumber((string) $number);
            if ($normalized) {
                $normalizedRecipients[] = $normalized;
            }
        }

        if (empty($normalizedRecipients)) {
            return [
                'success' => false,
                'message' => 'No valid Ethiopian phone numbers provided.',
            ];
        }

        try {
            $payload = [
                'api_key'    => $this->apiKey,
                'sender_id'  => $this->senderId,
                'recipients' => implode(',', $normalizedRecipients),
                'to'         => count($normalizedRecipients) === 1 ? $normalizedRecipients[0] : $normalizedRecipients,
                'message'    => $message,
            ];

            $ch = curl_init($this->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'Accept: application/json',
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);

            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                Log::error('SMS Ethiopia cURL error', ['error' => $curlError, 'recipients' => $normalizedRecipients]);
                return [
                    'success' => false,
                    'message' => 'cURL error: ' . $curlError,
                ];
            }

            $responseData = json_decode($response, true) ?? [];
            $isSuccess = ($httpCode >= 200 && $httpCode < 300) && ($responseData['status'] ?? '') !== 'error';

            Log::info('SMS Ethiopia dispatch result', [
                'http_code' => $httpCode,
                'recipients' => $normalizedRecipients,
                'response'  => $responseData,
            ]);

            return [
                'success' => $isSuccess,
                'http_code' => $httpCode,
                'data' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error('SMS Ethiopia exception', ['exception' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send OTP verification code to a user via SMS.
     */
    public function sendOtp(User $user, string $code): bool
    {
        if (empty($user->phone)) {
            return false;
        }

        $appName = config('app.name', 'Fira Link');
        $message = "Your {$appName} login verification code is: {$code}. It expires in 10 minutes.";

        $result = $this->send($user->phone, $message);
        return $result['success'] ?? false;
    }

    /**
     * Send enrollment confirmation notification to student.
     */
    public function sendEnrollmentNotification(Enrollment $enrollment): bool
    {
        $user = $enrollment->user;
        if (!$user || empty($user->phone)) {
            return false;
        }

        $courseTitle = $enrollment->course?->title ?? 'your course';
        $appName = config('app.name', 'Fira Link');
        $message = "Congratulations {$user->name}! You have been successfully enrolled in '{$courseTitle}' on {$appName}. Happy learning!";

        $result = $this->send($user->phone, $message);
        return $result['success'] ?? false;
    }

    /**
     * Send generic SMS notification to a user.
     */
    public function sendNotification(User $user, string $message): bool
    {
        if (empty($user->phone)) {
            return false;
        }

        $result = $this->send($user->phone, $message);
        return $result['success'] ?? false;
    }
}
