<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected ?string $credentialsPath;
    protected ?array $credentials = null;

    public function __construct()
    {
        $this->credentialsPath = config('services.firebase.credentials')
            ?: env('FIREBASE_CREDENTIALS', storage_path('app/firebase/firebase_credentials.json'));

        if (file_exists($this->credentialsPath)) {
            $json = file_get_contents($this->credentialsPath);
            $this->credentials = json_decode($json, true) ?: null;
        }
    }

    /**
     * Determine whether SSL verification should be enforced.
     */
    protected function shouldVerifySsl(): bool
    {
        if (config('services.firebase.ssl_verify') !== null) {
            return (bool) config('services.firebase.ssl_verify');
        }

        // On local/dev, if PHP on Windows has no local CA cert bundle configured, disable verify to prevent curl error 60
        $ca = ini_get('curl.cainfo') ?: ini_get('openssl.cafile');
        if ((app()->isLocal() || env('APP_DEBUG', false)) && (empty($ca) || !file_exists($ca))) {
            return false;
        }

        return true;
    }

    /**
     * Check if FCM is configured with a valid service account JSON.
     */
    public function isConfigured(): bool
    {
        return !empty($this->credentials['project_id'])
            && !empty($this->credentials['client_email'])
            && !empty($this->credentials['private_key']);
    }

    /**
     * Get or generate a cached OAuth2 access token for FCM HTTP v1.
     */
    public function getAccessToken(): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        return Cache::remember('fcm_v1_access_token', 3300, function () {
            return $this->generateAccessToken();
        });
    }

    /**
     * Generate Google OAuth2 access token via RS256 JWT assertion.
     */
    protected function generateAccessToken(): ?string
    {
        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $this->credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $encodedHeader = $this->base64UrlEncode(json_encode($header));
        $encodedClaims = $this->base64UrlEncode(json_encode($claims));
        $dataToSign = $encodedHeader . '.' . $encodedClaims;

        $signature = '';
        $privateKey = $this->credentials['private_key'];
        $success = openssl_sign($dataToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$success) {
            Log::error('[FCM] Failed to sign JWT with private key: ' . openssl_error_string());
            return null;
        }

        $jwt = $dataToSign . '.' . $this->base64UrlEncode($signature);

        $httpClient = $this->shouldVerifySsl() ? Http::asForm() : Http::withoutVerifying()->asForm();

        $response = $httpClient->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        Log::error('[FCM] OAuth2 token request failed: ' . $response->body());
        return null;
    }

    /**
     * Send push notification to a specific user across all their registered devices.
     */
    public function sendToUser(User|int $user, string $title, string $body, array $data = []): array
    {
        $userId = $user instanceof User ? $user->id : $user;
        $deviceTokens = UserDeviceToken::where('user_id', $userId)->pluck('fcm_token')->toArray();

        if (empty($deviceTokens)) {
            return ['success' => 0, 'failure' => 0, 'skipped' => 1];
        }

        $success = 0;
        $failure = 0;

        foreach ($deviceTokens as $token) {
            if ($this->sendToDevice($token, $title, $body, $data)) {
                $success++;
            } else {
                $failure++;
            }
        }

        return ['success' => $success, 'failure' => $failure, 'skipped' => 0];
    }

    /**
     * Send push notification to multiple users.
     */
    public function sendToUsers(iterable $users, string $title, string $body, array $data = []): array
    {
        $totalSuccess = 0;
        $totalFailure = 0;

        foreach ($users as $user) {
            $res = $this->sendToUser($user, $title, $body, $data);
            $totalSuccess += $res['success'];
            $totalFailure += $res['failure'];
        }

        return ['success' => $totalSuccess, 'failure' => $totalFailure];
    }

    /**
     * Send push notification to a specific device FCM token.
     */
    public function sendToDevice(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (!$this->isConfigured()) {
            Log::info("[FCM Simulated] Notification to token {$fcmToken}: {$title} - {$body}");
            return true;
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }

        $projectId = $this->credentials['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        // Convert all data payload values to string (FCM requirement)
        $stringData = [];
        foreach ($data as $k => $v) {
            $stringData[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v);
        }

        $payload = [
            'message' => [
                'token' => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'sound' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'channel_id' => 'high_importance_channel',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
            ],
        ];

        $client = $this->shouldVerifySsl()
            ? Http::withToken($accessToken)
            : Http::withoutVerifying()->withToken($accessToken);

        $response = $client->acceptJson()->post($url, $payload);

        if ($response->successful()) {
            // Update last used timestamp for token
            UserDeviceToken::where('fcm_token', $fcmToken)->update(['last_used_at' => now()]);
            return true;
        }

        // Handle unregistered / stale tokens by removing them
        $status = $response->status();
        $responseBody = $response->json();
        $errorCode = $responseBody['error']['details'][0]['errorCode'] ?? $responseBody['error']['status'] ?? '';

        if ($status === 404 || in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
            Log::info("[FCM] Removing invalid/unregistered token: {$fcmToken}");
            UserDeviceToken::where('fcm_token', $fcmToken)->delete();
        } else {
            Log::warning("[FCM] Message send error: " . $response->body());
        }

        return false;
    }

    /**
     * Send push notification to a topic (e.g. 'all_users', 'announcements').
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        if (!$this->isConfigured()) {
            Log::info("[FCM Simulated] Notification to topic '{$topic}': {$title} - {$body}");
            return true;
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return false;
        }

        $projectId = $this->credentials['project_id'];
        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $stringData = [];
        foreach ($data as $k => $v) {
            $stringData[(string) $k] = is_scalar($v) ? (string) $v : json_encode($v);
        }

        $payload = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
            ],
        ];

        $client = $this->shouldVerifySsl()
            ? Http::withToken($accessToken)
            : Http::withoutVerifying()->withToken($accessToken);

        $response = $client->acceptJson()->post($url, $payload);
        return $response->successful();
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
