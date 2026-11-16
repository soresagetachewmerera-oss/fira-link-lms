<?php

namespace App\Services\Market\Ai;

/** Fake model for tests and the evaluation command: returns a scripted answer (null = the model fails). */
class ScriptedMarketClient implements MarketLlmClient
{
    public ?string $lastSystemPrompt = null;
    public ?string $lastUserPrompt = null;

    public function __construct(private ?string $reply = null, private bool $on = true) {}

    public function enabled(): bool
    {
        return $this->on;
    }

    public function answer(string $systemPrompt, string $userPrompt): ?string
    {
        $this->lastSystemPrompt = $systemPrompt;
        $this->lastUserPrompt = $userPrompt;

        return $this->reply;
    }
}
