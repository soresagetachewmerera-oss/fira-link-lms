<?php

namespace App\Services;

use App\Services\SettingsRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;
    protected $model;
    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    protected $settings;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
        $config = $settings->get('ai.config', []);
        $this->apiKey = $config['gemini_api_key'] ?? null;
        $this->model = $config['gemini_model'] ?? 'gemini-2.5-flash';
    }

    /**
     * Generate content using Gemini API
     *
     * @param string $prompt
     * @param array $history
     * @return string|null
     */
    public function generateContent(string $prompt, array $history = [])
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API key is not configured.');
            return 'AI configuration is missing. Please contact the administrator.';
        }

        $url = $this->baseUrl . $this->model . ':generateContent?key=' . $this->apiKey;

        $contents = [];

        // Format history for Gemini API
        foreach ($history as $message) {
            $contents[] = [
                'role' => $message->is_ai ? 'model' : 'user',
                'parts' => [
                    ['text' => $message->message]
                ]
            ];
        }

        // Add current prompt
        $contents[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $prompt]
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                        'contents' => $contents,
                        'generationConfig' => [
                            'temperature' => 0.7,
                            'topK' => 40,
                            'topP' => 0.95,
                            'maxOutputTokens' => 1024,
                        ]
                    ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response generated.';
            }

            Log::error('Gemini API Error: ' . $response->body());
            return 'Sorry, I encountered an error providing a response. Please try again later.';

        } catch (\Exception $e) {
            Log::error('Gemini Service Exception: ' . $e->getMessage());
            return 'An error occurred while connecting to the AI service.';
        }
    }
}
