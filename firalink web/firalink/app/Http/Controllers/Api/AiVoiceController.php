<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiVoiceQuota;
use App\Models\AiVoiceSession;
use App\Services\FiraAiVoiceService;
use App\Services\VoiceTools\VoiceToolDispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AiVoiceController extends Controller
{
    public function __construct(protected FiraAiVoiceService $voiceService) {}

    /**
     * Start a new Fira AI Voice session and obtain a locked ephemeral token.
     */
    public function session(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language' => ['nullable', 'string', 'max:10'],
            'consent' => ['nullable', 'boolean'],
            'store_transcripts' => ['nullable', 'boolean'],
        ]);

        $user = Auth::user() ?: auth('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated. Sign in to access Fira AI voice.',
            ], 401);
        }

        $language = $validated['language'] ?? $user->preferredLocale();
        $consent = $validated['consent'] ?? true;
        $storeTranscripts = $validated['store_transcripts'] ?? false;
        $ip = $request->ip() ?: '127.0.0.1';

        $result = $this->voiceService->startSession($user, $language, $ip, $consent, $storeTranscripts);

        if (!$result['success']) {
            $status = match ($result['error_code'] ?? null) {
                'quota_exceeded' => 403,
                'cost_ceiling_reached' => 503,
                'language_not_supported_for_voice' => 422,
                'consent_required' => 422,
                default => 500,
            };

            return response()->json($result, $status);
        }

        return response()->json($result, 201);
    }

    /**
     * Conclude an active voice session and record usage.
     */
    public function end(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:64'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:7200'],
            'tools_used' => ['nullable', 'array'],
            'tools_used.*' => ['string', 'max:64'],
            'error_code' => ['nullable', 'string', 'max:64'],
        ]);

        $user = Auth::user() ?: auth('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $result = $this->voiceService->endSession(
            sessionId: $validated['session_id'],
            user: $user,
            durationSeconds: $validated['duration_seconds'],
            toolsUsed: $validated['tools_used'] ?? [],
            errorCode: $validated['error_code'] ?? null
        );

        if (!$result['success']) {
            return response()->json($result, 404);
        }

        return response()->json($result, 200);
    }

    /**
     * Get current user voice quota status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = Auth::user() ?: auth('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $today = Carbon::today();
        $defaultLimit = (int) config('services.gemini.voice.daily_user_seconds', 300);

        $quota = AiVoiceQuota::firstOrCreate(
            ['user_id' => $user->id, 'usage_date' => $today],
            [
                'ip_address' => $request->ip() ?: '127.0.0.1',
                'seconds_used' => 0,
                'seconds_limit' => $defaultLimit,
                'sessions_count' => 0,
                'cost_estimated_usd' => 0.0000,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'user_id' => $user->id,
                'date' => $today->toDateString(),
                'seconds_used' => $quota->seconds_used,
                'seconds_limit' => $quota->seconds_limit,
                'remaining_today_seconds' => $quota->remainingSeconds(),
                'sessions_count' => $quota->sessions_count,
                'allowed_languages' => config('services.gemini.voice.allowed_languages', ['en', 'am']),
            ],
        ]);
    }

    /**
     * Execute a server-authorized tool call requested by Fira AI Voice.
     */
    public function tool(Request $request, VoiceToolDispatcher $dispatcher): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:64'],
            'tool_name' => ['required', 'string', 'max:64'],
            'args' => ['nullable', 'array'],
        ]);

        $user = Auth::user() ?: auth('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $session = AiVoiceSession::where('session_id', $validated['session_id'])->first();
        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Voice session not found.',
            ], 404);
        }

        if ($session->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized voice session access.',
            ], 403);
        }

        if ($session->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Voice session is no longer active.',
            ], 410);
        }

        $toolName = $validated['tool_name'];
        if (!in_array($toolName, VoiceToolDispatcher::ALLOWED_TOOLS, true)) {
            return response()->json([
                'success' => false,
                'message' => "Tool '{$toolName}' is not allowed for voice execution.",
            ], 422);
        }

        $args = $validated['args'] ?? [];

        $validator = Validator::make($args, match ($toolName) {
            'get_market_snapshot' => [
                'symbols' => ['nullable', 'array', 'max:20'],
                'symbols.*' => ['string', 'max:32'],
            ],
            'get_ecta_schedule' => [
                'origin' => ['nullable', 'string', 'max:50'],
                'grade' => ['nullable'],
                'processing' => ['nullable', 'string', 'max:50'],
            ],
            'run_profit_calculator' => [
                'bags' => ['nullable', 'integer', 'min:1', 'max:50000'],
                'fob_price_usd' => ['nullable', 'numeric', 'min:0.01', 'max:100.0'],
                'purchase_price_etb' => ['nullable', 'numeric', 'min:0'],
            ],
            'search_lots' => [
                'origin' => ['nullable', 'string', 'max:50'],
                'grade' => ['nullable'],
            ],
            'get_my_verification_status' => [],
            'search_help' => [
                'query' => ['nullable', 'string', 'max:100'],
            ],
            'search_academy' => [
                'query' => ['nullable', 'string', 'max:100'],
            ],
            default => [],
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid tool arguments.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $toolResult = $dispatcher->execute($toolName, $args, $user, $session->session_id);

            // Update session tool metrics
            $toolsUsed = $session->tools_used ?? [];
            if (!in_array($toolName, $toolsUsed, true)) {
                $toolsUsed[] = $toolName;
            }
            $session->increment('tool_calls_count');
            $session->update(['tools_used' => $toolsUsed]);

            return response()->json([
                'success' => true,
                'tool_name' => $toolResult['tool_name'],
                'result' => $toolResult['result'],
                'display_card' => $toolResult['display_card'],
                'speak_text' => $toolResult['speak_text'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error executing tool: ' . $e->getMessage(),
            ], 500);
        }
    }
}
