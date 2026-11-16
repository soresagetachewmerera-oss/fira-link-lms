<?php

namespace App\Console\Commands;

use App\Services\Market\Ai\GeminiMarketClient;
use App\Services\Market\Ai\MarketAiEvalCases;
use App\Services\Market\Ai\MarketAiService;
use App\Services\Market\Ai\MarketAnswerGuard;
use App\Services\Market\Ai\MarketSnapshotTool;
use App\Services\Market\Ai\ScriptedMarketClient;
use Illuminate\Console\Command;

/**
 * Regression check for Fira AI market answers. Default: scripted model answers (no network, no key).
 * --live: the real Gemini client (needs MARKET_AI_ENABLED and GEMINI_ENABLED); then only the safety
 * checks apply (nothing absent shows up, every price in the answer is in the snapshot).
 */
class MarketAiEvalCommand extends Command
{
    protected $signature = 'market:ai-eval {--live : Ask the real model instead of the scripted answers}';
    protected $description = 'Run the Fira AI market evaluation set and report the pass rate';

    public function handle(MarketSnapshotTool $tool, MarketAnswerGuard $guard): int
    {
        $live = (bool) $this->option('live');
        $gemini = app(GeminiMarketClient::class);
        if ($live && !$gemini->enabled()) {
            $this->error('Live evaluation needs MARKET_AI_ENABLED=true and GEMINI_ENABLED=true with a key. Nothing was sent.');

            return self::FAILURE;
        }

        $rows = [];
        $passed = 0;
        $cases = MarketAiEvalCases::all();
        foreach ($cases as $case) {
            $snapshot = MarketAiEvalCases::snapshot($case['snapshot'] ?? 'normal');
            $client = $live ? $gemini : new ScriptedMarketClient($case['model'] ?? null, ($case['mode'] ?? 'on') !== 'off');
            $result = (new MarketAiService($tool, $guard, $client))->answer($case['q'], null, $snapshot);

            $problems = [];
            if (!$live && $result['mode'] !== $case['expect']) {
                $problems[] = "mode {$result['mode']}, expected {$case['expect']}";
            }
            foreach ($live ? [] : ($case['contains'] ?? []) as $needle) {
                if (!str_contains($result['text'], $needle)) {
                    $problems[] = "missing \"{$needle}\"";
                }
            }
            foreach ($case['absent'] ?? [] as $needle) {
                if (str_contains($result['text'], $needle)) {
                    $problems[] = "contains \"{$needle}\"";
                }
            }
            if ($live && $result['mode'] === 'model' && !$guard->check($result['text'], $snapshot)['passed']) {
                $problems[] = 'shown answer fails the guard';
            }

            $ok = $problems === [];
            $passed += $ok ? 1 : 0;
            $rows[] = [$case['id'], $result['mode'], $ok ? 'pass' : 'FAIL', implode('; ', $problems)];
        }

        $this->table(['Case', 'Mode', 'Result', 'Problems'], $rows);
        $rate = round($passed / count($cases) * 100, 1);
        $this->line(sprintf('%d of %d passed (%s%%)%s', $passed, count($cases), $rate, $live ? ' — live model' : ' — scripted model'));

        return $passed === count($cases) ? self::SUCCESS : self::FAILURE;
    }
}
