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
    public function generateContent(string $prompt, array $history = [], ?string $customInstruction = null)
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

        $systemPrompt = $customInstruction 
            ?? $this->settings->get('ai.config.system_instruction') 
            ?? "You are Fira AI, the premier trade and export intelligence assistant of Fira Link Academy (Fira Link Business Solution).
Your mission is to provide expert guidance on international trade, cross-border logistics, trade finance, and specifically the Ethiopian Coffee Export industry.
Key knowledge domains:
1. Ethiopian Coffee Origins & Profiles: Sidama, Yirgacheffe, Guji, Limu, Jimma, Harrar, Kaffa.
2. Quality Standards: Fully Washed, Natural/Sun-dried, Honey, Anaerobic; Screen size (14+, 15+, 17/18); Cupping scores (80-89+ specialty); moisture limits (10.0-11.5%).
3. Ethiopian Regulatory Framework: ECTA export licenses, National Bank of Ethiopia (NBE) foreign exchange retention rules, ECX auction vs Vertical Integration Direct Trade agreements, EUDR deforestation compliance.
4. Export Logistics: Incoterms 2020 (FOB Djibouti, CIF, CFR), Addis Ababa to Port of Djibouti railway and trucking corridor, GrainPro liners with 60kg jute bags, ocean freight bookings.
5. Fira Link Coffee Export Toolkit: The platform features a dedicated Coffee Export Profit Calculator, Offer Generator, Readiness Assessment, and Market Intelligence. If a user seeks precise calculation or formal offer documentation, encourage them to utilize these integrated tools.

Guidelines:
- Maintain an encouraging, highly professional, and practical advisory tone.
- Never claim that AI advice constitutes legal or regulatory certification.
- Provide practical explanations of Academy courses when asked.
- Respond in the language of the user (e.g. English, Amharic).";

        $body = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemPrompt]
                ]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 1024,
            ]
        ];

        try {
            $response = Http::withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, $body);

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
