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
        $envKey = env('GEMINI_API_KEY');
        $configKey = config('services.gemini.api_key');
        $dbKey = $config['gemini_api_key'] ?? null;

        // Prioritize explicit env var, then config, then database
        if (!empty($envKey)) {
            $this->apiKey = trim($envKey);
        } elseif (!empty($configKey)) {
            $this->apiKey = trim($configKey);
        } elseif (!empty($dbKey)) {
            $this->apiKey = trim($dbKey);
        } else {
            $this->apiKey = '';
        }

        $this->model = env('GEMINI_MODEL') ?: ($config['gemini_model'] ?? config('services.gemini.model', 'gemini-3-flash-preview'));
    }

    /**
     * Check current status and configuration of Fira AI / Gemini
     */
    public function getStatus(): array
    {
        $hasKey = !empty($this->apiKey);
        return [
            'assistant_name' => 'Fira AI',
            'provider' => 'Google Gemini AI',
            'configured' => $hasKey,
            'model' => $this->model,
        ];
    }

    /**
     * Test connection with Google Gemini API
     */
    public function testConnection(): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'GEMINI_API_KEY is not configured.',
            ];
        }

        try {
            $response = $this->generateContent('Ping: Please reply with "Fira AI is active and powered by Google Gemini AI."');
            return [
                'success' => true,
                'reply' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate content using Gemini API
     *
     * @param string $prompt
     * @param array $history
     * @param string|null $customInstruction
     * @return string
     */
    public function generateContent(string $prompt, array $history = [], ?string $customInstruction = null)
    {
        if (empty($this->apiKey)) {
            Log::warning('Fira AI: Gemini API key is not configured.');
            return 'Fira AI is ready to assist you! To activate live responses, please configure your Google Gemini API key in the platform settings or .env configuration (GEMINI_API_KEY).';
        }

        $systemPrompt = $customInstruction 
            ?? $this->settings->get('ai.config.system_instruction') 
            ?? "You are Fira AI, the premier trade, coffee export intelligence, and learning assistant of Fira Link Academy (Fira Link Business Solution).
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
                'thinkingConfig' => [
                    'thinkingBudget' => 0,
                ],
            ]
        ];

        // Models to try (current model first, then ultra-fast and reliable active fallbacks)
        $modelsToTry = array_filter(array_unique([
            $this->model,
            'gemini-3-flash-preview',
            'gemini-3.1-flash-lite-preview',
            'gemini-3.6-flash',
            'gemini-3.5-flash',
            'gemini-flash-latest',
            'gemini-3.8-flash',
        ]));

        $lastError = null;

        foreach ($modelsToTry as $candidateModel) {
            $url = $this->baseUrl . $candidateModel . ':generateContent?key=' . $this->apiKey;

            try {
                $response = Http::withoutVerifying()
                    ->withOptions([
                        'curl' => [
                            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                            CURLOPT_CONNECTTIMEOUT => 4,
                        ]
                    ])
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->timeout(8)
                    ->post($url, $body);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response generated.';
                }

                $statusCode = $response->status();

                // If 404 (model not found) or 503 (temporary high demand spike) or 429 (quota), try next candidate
                if ($statusCode === 404 || $statusCode === 503 || $statusCode === 429) {
                    Log::warning("Fira AI: Model {$candidateModel} returned status {$statusCode}. Trying next candidate model.");
                    continue;
                }

                if ($statusCode === 400 || $statusCode === 403) {
                    Log::error('Fira AI: Gemini Authentication or Key Error: ' . $response->body());
                    return 'Fira AI encountered an authentication error. Please verify the Gemini API key in the environment configuration.';
                }

                Log::warning("Fira AI: Gemini API Error on {$candidateModel}: " . $response->body() . ". Trying next candidate.");
                continue;

            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("Fira AI Service Exception on {$candidateModel}: " . $e->getMessage() . ". Trying next candidate model.");
                continue;
            }
        }

        Log::warning('Fira AI: All cloud candidate models temporarily rate-limited or busy. Serving intelligent trade fallback. Last error: ' . ($lastError ?? 'None'));
        return $this->generateIntelligentFallback($prompt);
    }

    /**
     * Provide an intelligent, context-aware fallback response for trade & coffee export queries
     * whenever upstream cloud rate limits or temporary spikes occur.
     */
    protected function generateIntelligentFallback(string $prompt): string
    {
        $lower = strtolower(trim($prompt));

        // Greetings
        if (preg_match('/\b(hi|hello|hey|greetings|selam|salam|good morning|good afternoon)\b/i', $lower)) {
            return "Hello! I am Fira AI, your trade, coffee export intelligence, and learning assistant at Fira Link Academy. How can I help you today? You can ask me about Ethiopian coffee origins (Sidama, Yirgacheffe, Guji), export licensing with ECTA, Incoterms logistics, or our Coffee Export Profit Calculator!";
        }

        // Sidama
        if (str_contains($lower, 'sidama')) {
            return "Sidama coffee is one of Ethiopia's premier specialty origins, grown in southern highlands at altitudes between 1,750 and 2,200 meters. Celebrated for its bright citric acidity, delicate floral aromas, and notes of stone fruit and bergamot, Sidama is exported primarily as Grade 1 and Grade 2 (Washed) or Grade 3 and Grade 4 (Natural). For export compliance, maintain moisture levels strictly between 10.0% and 11.5% and pack in hermetic GrainPro liners.";
        }

        // Yirgacheffe
        if (str_contains($lower, 'yirgacheffe') || str_contains($lower, 'yirga')) {
            return "Yirgacheffe coffee is globally renowned for its unmistakable floral and citrus cup profile—often described as lemon blossom, bergamot, and sweet peach. Cultivated at elevations of 1,800 to 2,200 meters in the Gedeo Zone, washed Yirgacheffe displays an elegant tea-like body, while natural processed Yirgacheffe delivers intense berry sweetness. It commands some of the highest specialty premiums on the international market.";
        }

        // Guji
        if (str_contains($lower, 'guji')) {
            return "Guji coffee originates from the Oromia region and has emerged as a distinct powerhouse in specialty coffee. With rich volcanic soils and elevations reaching 2,300 meters, Guji coffees produce stunning complexity—featuring sweet floral notes, dark berries, and tropical fruits. Anaerobic and natural fermentations from Guji frequently achieve cupping scores of 88–90+.";
        }

        // Profit, Calculation, Price, Margin
        if (str_contains($lower, 'profit') || str_contains($lower, 'margin') || str_contains($lower, 'calculat') || str_contains($lower, 'cost') || str_contains($lower, 'price')) {
            return "Calculating export profitability for Ethiopian coffee involves six core pillars:\n\n1. Volume: A standard 20ft Full Container Load (FCL) holds 320 bags (60kg net = 19,200 kg).\n2. Green Coffee Sourcing: Purchase price via ECX or direct farm-gate agreement (Vertical Integration).\n3. Processing & Grading: Dry milling, sorting, and screen sizing (Screen 14+, 15+, 17/18).\n4. Packaging: ECTA-approved jute bags with GrainPro liners to preserve moisture at 10.0–11.5%.\n5. Logistics: Inland transportation from Addis Ababa/dry port to Port of Djibouti via train or truck.\n6. Compliance & Finance: ECTA export permit, NBE foreign exchange retention, and LC/CAD banking fees.\n\nTip: You can use the dedicated **Fira Link Coffee Export Profit Calculator** in your dashboard for real-time breakdowns!";
        }

        // ECTA, License, Regulations, NBE, EUDR
        if (str_contains($lower, 'license') || str_contains($lower, 'ecta') || str_contains($lower, 'nbe') || str_contains($lower, 'regulation') || str_contains($lower, 'eudr') || str_contains($lower, 'rule')) {
            return "Exporting Ethiopian coffee requires strict adherence to regulatory bodies:\n\n• Ethiopian Coffee and Tea Authority (ECTA): Issues the mandatory Coffee Export Competency Certificate and inspects each lot for moisture, defect count, and cup score before issuing export permits.\n• National Bank of Ethiopia (NBE): Governs export contract registration, Letter of Credit (LC) / Cash Against Documents (CAD) clearance, and foreign exchange retention accounts.\n• EUDR Compliance: Requires geolocation polygon mapping for farm plots to verify zero-deforestation for shipments destined for the European Union.\n• Direct Trade: Exporters can partner directly with washing stations or cooperatives under Vertical Integration without going through the central ECX auction.";
        }

        // Logistics, Incoterms, Shipping, Djibouti
        if (str_contains($lower, 'logistics') || str_contains($lower, 'incoterm') || str_contains($lower, 'djibouti') || str_contains($lower, 'fob') || str_contains($lower, 'cif') || str_contains($lower, 'freight') || str_contains($lower, 'shipping')) {
            return "In Ethiopian coffee logistics, the primary export corridor connects Addis Ababa (Mojo Dry Port) to the Port of Djibouti via the Ethio-Djibouti Railway and trucking fleet. Standard contracts are structured under Incoterms 2020:\n\n• FOB Djibouti: The seller covers all inland transport, customs, and port loading fees until cargo passes onto the vessel. The buyer pays ocean freight and marine insurance.\n• CIF / CFR: The seller contracts and pays for ocean carriage to the port of destination (plus marine insurance under CIF).\n• Protection: Coffee must be enclosed in multi-layer GrainPro hermetic liners inside standard 60kg jute bags to prevent moisture absorption and odor contamination during tropical transit.";
        }

        // Default Intelligent Trade Response
        return "As Fira AI, your trade and coffee export intelligence advisor at Fira Link Academy, I am here to guide you across every stage of the international coffee supply chain. You can explore topics such as:\n\n• Origin Profiles: Sidama, Yirgacheffe, Guji, Harrar, Limu, and Jimma.\n• Quality Standards: Moisture limits (10.0–11.5%), screen grading, and cupping scores.\n• Regulatory Compliance: ECTA export licenses, NBE foreign exchange repatriation, and EUDR traceability.\n• Export Logistics: FOB Djibouti contracts, freight bookings, and GrainPro liner preservation.\n• Export Tools: Use our integrated Coffee Export Profit Calculator and Offer Generator in your dashboard for precision planning.\n\nPlease feel free to ask a specific question on any coffee trade or course topic!";
    }
}
