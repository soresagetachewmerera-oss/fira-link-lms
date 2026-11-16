<?php

namespace App\Services\Market\Ai;

use App\Services\GeminiService;

/**
 * Gemini for market answers: low temperature, JSON response schema, no search or browsing tools.
 * On only when both MARKET_AI_ENABLED and GEMINI_ENABLED are true and the key is set.
 */
class GeminiMarketClient implements MarketLlmClient
{
    public function __construct(private readonly GeminiService $gemini) {}

    public function enabled(): bool
    {
        return (bool) config('market.ai_enabled') && $this->gemini->isEnabled();
    }

    public function answer(string $systemPrompt, string $userPrompt): ?string
    {
        $result = $this->gemini->generateStructured($systemPrompt, $userPrompt, [
            'type' => 'OBJECT',
            'properties' => [
                'answer' => ['type' => 'STRING'],
                'used_symbols' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            ],
            'required' => ['answer'],
        ], 0.1);

        $answer = $result['answer'] ?? null;

        return is_string($answer) && trim($answer) !== '' ? $answer : null;
    }
}
