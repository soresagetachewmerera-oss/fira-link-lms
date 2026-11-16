<?php

namespace App\Services\Market\Ai;

/** The language model behind Fira AI market answers. Returns the answer text, or null on failure. */
interface MarketLlmClient
{
    public function enabled(): bool;

    public function answer(string $systemPrompt, string $userPrompt): ?string;
}
