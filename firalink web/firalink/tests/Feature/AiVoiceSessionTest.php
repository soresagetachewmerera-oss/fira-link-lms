<?php

namespace Tests\Feature;

use App\Models\AiVoiceQuota;
use App\Models\AiVoiceSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AiVoiceSessionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.gemini.enabled' => true,
            'services.gemini.api_key' => 'test-key',
            'services.gemini.live_model' => 'models/gemini-2.0-flash',
            'services.gemini.voice.daily_user_seconds' => 300,
            'services.gemini.voice.daily_cost_ceiling_usd' => 20.0,
            'services.gemini.voice.allowed_languages' => ['en', 'am'],
        ]);
    }

    public function test_voice_session_requires_authentication(): void
    {
        $response = $this->postJson('/api/ai/voice/session', [
            'language' => 'en',
        ]);

        $response->assertStatus(401);
    }

    public function test_voice_session_mints_ephemeral_token_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/voice/session', [
            'language' => 'en',
            'consent' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'session_id',
                'token',
                'model',
                'language',
                'expires_at',
                'limits' => [
                    'max_session_seconds',
                    'remaining_today_seconds',
                ],
            ],
        ]);

        $data = $response->json('data');
        $this->assertEquals('en', $data['language']);
        $this->assertEquals('models/gemini-2.0-flash', $data['model']);
        $this->assertNotEmpty($data['token']);
        $this->assertNotEmpty($data['session_id']);

        // Security check: master API key must never be leaked
        $this->assertStringNotContainsString('test-key', $response->getContent());

        $this->assertDatabaseHas('ai_voice_sessions', [
            'session_id' => $data['session_id'],
            'user_id' => $user->id,
            'status' => 'active',
            'language' => 'en',
        ]);

        $this->assertDatabaseHas('ai_voice_consents', [
            'user_id' => $user->id,
            'consented' => true,
        ]);
    }

    public function test_voice_session_allows_amharic(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/voice/session', [
            'language' => 'am',
            'consent' => true,
        ]);

        $response->assertStatus(201);
        $this->assertEquals('am', $response->json('data.language'));
    }

    public function test_voice_session_blocks_afan_oromo_with_fallback_guidance(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/voice/session', [
            'language' => 'om',
            'consent' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'error_code' => 'language_not_supported_for_voice',
        ]);
        $this->assertStringContainsString('Afan Oromo', $response->json('message'));
    }

    public function test_voice_session_enforces_user_daily_quota_limit(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Pre-fill user quota to limit
        AiVoiceQuota::create([
            'user_id' => $user->id,
            'usage_date' => now()->toDateString(),
            'seconds_used' => 300,
            'seconds_limit' => 300,
            'sessions_count' => 3,
            'cost_estimated_usd' => 0.0750,
        ]);

        $response = $this->postJson('/api/ai/voice/session', [
            'language' => 'en',
            'consent' => true,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'error_code' => 'quota_exceeded',
        ]);
    }

    public function test_voice_session_end_updates_duration_and_quota(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create an active session
        $sessionResponse = $this->postJson('/api/ai/voice/session', [
            'language' => 'en',
            'consent' => true,
        ]);
        $sessionId = $sessionResponse->json('data.session_id');

        // End the session
        $endResponse = $this->postJson('/api/ai/voice/end', [
            'session_id' => $sessionId,
            'duration_seconds' => 75,
            'tools_used' => ['get_market_snapshot'],
        ]);

        $endResponse->assertStatus(200);
        $endResponse->assertJson([
            'success' => true,
            'data' => [
                'session_id' => $sessionId,
                'duration_seconds' => 75,
                'remaining_today_seconds' => 225,
                'status' => 'completed',
            ],
        ]);

        $this->assertDatabaseHas('ai_voice_sessions', [
            'session_id' => $sessionId,
            'status' => 'completed',
            'duration_seconds' => 75,
            'tool_calls_count' => 1,
        ]);

        $this->assertDatabaseHas('ai_voice_quotas', [
            'user_id' => $user->id,
            'seconds_used' => 75,
            'sessions_count' => 1,
        ]);
    }

    public function test_voice_status_returns_current_quota_and_allowed_languages(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/ai/voice/status');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'user_id',
                'date',
                'seconds_used',
                'seconds_limit',
                'remaining_today_seconds',
                'sessions_count',
                'allowed_languages',
            ],
        ]);

        $this->assertEquals(300, $response->json('data.remaining_today_seconds'));
        $this->assertEquals(['en', 'am'], $response->json('data.allowed_languages'));
    }
}
