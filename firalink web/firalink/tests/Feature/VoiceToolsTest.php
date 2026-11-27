<?php

namespace Tests\Feature;

use App\Models\AiVoiceSession;
use App\Models\CoffeeListing;
use App\Models\Course;
use App\Models\MarketplaceSellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoiceToolsTest extends TestCase
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
            'services.gemini.voice.allowed_languages' => ['en', 'am'],
        ]);
    }

    protected function createActiveSession(User $user): AiVoiceSession
    {
        return AiVoiceSession::create([
            'session_id' => 'vses_' . Str::random(24),
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'language' => 'en',
            'model' => 'models/gemini-2.0-flash',
            'status' => 'active',
            'token_expires_at' => now()->addMinutes(2),
            'started_at' => now(),
            'tool_calls_count' => 0,
            'tools_used' => [],
        ]);
    }

    public function test_voice_tool_requires_authentication(): void
    {
        $response = $this->postJson('/api/ai/voice/tool', [
            'session_id' => 'vses_sample',
            'tool_name' => 'get_market_snapshot',
        ]);

        $response->assertStatus(401);
    }

    public function test_voice_tool_rejects_missing_session(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/voice/tool', [
            'session_id' => 'nonexistent_session',
            'tool_name' => 'get_market_snapshot',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Voice session not found.',
            ]);
    }

    public function test_voice_tool_rejects_cross_user_session_access(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $session = $this->createActiveSession($owner);

        Sanctum::actingAs($attacker);

        $response = $this->postJson('/api/ai/voice/tool', [
            'session_id' => $session->session_id,
            'tool_name' => 'get_market_snapshot',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized voice session access.',
            ]);
    }

    public function test_voice_tool_rejects_terminated_session(): void
    {
        $user = User::factory()->create();
        $session = $this->createActiveSession($user);
        $session->update(['status' => 'ended']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/voice/tool', [
            'session_id' => $session->session_id,
            'tool_name' => 'get_market_snapshot',
        ]);

        $response->assertStatus(410)
            ->assertJson([
                'success' => false,
                'message' => 'Voice session is no longer active.',
            ]);
    }

    public function test_voice_tool_rejects_unallowed_tool(): void
    {
        $user = User::factory()->create();
        $session = $this->createActiveSession($user);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/voice/tool', [
            'session_id' => $session->session_id,
            'tool_name' => 'unauthorized_write_action',
            'args' => [],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_voice_tool_validates_argument_schemas(): void
    {
        $user = User::factory()->create();
        $session = $this->createActiveSession($user);

        Sanctum::actingAs($user);

        // Invalid bags (must be min 1, integer)
        $response = $this->postJson('/api/ai/voice/tool', [
            'session_id' => $session->session_id,
            'tool_name' => 'run_profit_calculator',
            'args' => [
                'bags' => -10,
                'fob_price_usd' => 3.50,
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['errors' => ['bags']]);
    }

    public function test_tool_get_market_snapshot(): void
    {
        $user = User::factory()->create();
        $session = $this->createActiveSession($user);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/voice/tool', [
            'session_id' => $session->session_id,
            'tool_name' => 'get_market_snapshot',
            'args' => [
                'symbols' => ['ICO_COMPOSITE', 'USD_ETB_NBE'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'tool_name' => 'get_market_snapshot',
            ])
            ->assertJsonStructure([
                'success',
                'tool_name',
                'result' => ['as_of', 'rows'],
                'display_card' => ['type', 'title', 'data' => ['as_of', 'rows']],
                'speak_text',
            ]);

        $this->assertEquals('market_snapshot', $response->json('display_card.type'));
        $this->assertNotEmpty($response->json('speak_text'));

        // Verify session metric update
        $session->refresh();
        $this->assertEquals(1, $session->tool_calls_count);
        $this->assertContains('get_market_snapshot', $session->tools_used);
    }

    public function test_tool_get_ecta_schedule(): void
    {
        $user = User::factory()->create();
        $session = $this->createActiveSession($user);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/voice/tool', [
            'session_id' => $session->session_id,
            'tool_name' => 'get_ecta_schedule',
            'args' => [
                'origin' => 'Sidama',
                'grade' => '2',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'tool_name' => 'get_ecta_schedule',
            ])
            ->assertJsonStructure([
                'result' => ['count', 'schedule'],
                'display_card' => ['type', 'title', 'data' => ['schedule']],
                'speak_text',
            ]);

        $this->assertEquals('ecta_schedule', $response->json('display_card.type'));
    }

    public function test_tool_run_profit_calculator(): void
    {
        $user = User::factory()->create();
        $session = $this->createActiveSession($user);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/voice/tool', [
            'session_id' => $session->session_id,
            'tool_name' => 'run_profit_calculator',
            'args' => [
                'bags' => 320,
                'fob_price_usd' => 4.25,
                'purchase_price_etb' => 4500, // per feresula (17kg)
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'tool_name' => 'run_profit_calculator',
            ])
            ->assertJsonStructure([
                'result' => [
                    'gross_revenue_usd',
                    'net_profit_etb',
                    'margin_percentage',
                    'break_even_price_usd_per_lb',
                ],
                'display_card' => ['type', 'title', 'data'],
                'speak_text',
            ]);

        $this->assertEquals('calculator_result', $response->json('display_card.type'));
        $this->assertStringContainsString('gross revenue', strtolower($response->json('speak_text')));
    }

    public function test_tool_search_lots(): void
    {
        $user = User::factory()->create();
        $session = $this->createActiveSession($user);

        $seller = User::factory()->create();
        $profile = MarketplaceSellerProfile::create([
            'user_id' => $seller->id,
            'company_name' => 'Oromia Coffee Co',
            'company_slug' => 'oromia-coffee-co',
            'business_type' => 'exporter',
        ]);
        $profile->forceFill(['verification_status' => 'verified'])->save();

        $lot = CoffeeListing::create([
            'seller_id' => $seller->id,
            'title' => 'Grade 1 Washed Yirgacheffe Gedeo Lot',
            'slug' => 'grade-1-washed-yirgacheffe-gedeo-lot',
            'origin_region' => 'Yirgacheffe',
            'sub_region' => 'Gedeo',
            'processing_method' => 'washed',
            'grade' => 'grade_1',
            'total_quantity_bags' => 300,
            'available_quantity_bags' => 300,
            'indicative_price_usd_per_lb' => 4.80,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('coffee_listings', ['id' => $lot->id, 'status' => 'active']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/voice/tool', [
            'session_id' => $session->session_id,
            'tool_name' => 'search_lots',
            'args' => [
                'origin' => 'Yirgacheffe',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'tool_name' => 'search_lots',
            ])
            ->assertJsonStructure([
                'result' => ['count', 'lots'],
                'display_card' => ['type', 'title', 'data' => ['lots']],
                'speak_text',
            ]);

        $this->assertEquals('lot_list', $response->json('display_card.type'));
        $this->assertGreaterThanOrEqual(1, $response->json('result.count'));
    }

    public function test_tool_get_my_verification_status_is_scoped_to_auth_user(): void
    {
        $user = User::factory()->create();
        $session = $this->createActiveSession($user);

        $profile = MarketplaceSellerProfile::create([
            'user_id' => $user->id,
            'company_name' => 'Bensa Specialty Exporters',
            'company_slug' => 'bensa-specialty-exporters',
            'business_type' => 'exporter',
            'export_license_no' => 'ECTA/EXP/2026/884',
            'tin_number' => '0098765432',
        ]);
        $profile->forceFill(['verification_status' => 'verified'])->save();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/voice/tool', [
            'session_id' => $session->session_id,
            'tool_name' => 'get_my_verification_status',
            'args' => [],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'tool_name' => 'get_my_verification_status',
                'result' => [
                    'registered' => true,
                    'company_name' => 'Bensa Specialty Exporters',
                    'status' => 'verified',
                    'export_license_no' => 'ECTA/EXP/2026/884',
                ],
            ]);

        $this->assertEquals('verification_status', $response->json('display_card.type'));
        $this->assertStringContainsString('Bensa Specialty Exporters', $response->json('speak_text'));
    }

    public function test_tool_search_help(): void
    {
        $user = User::factory()->create();
        $session = $this->createActiveSession($user);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/voice/tool', [
            'session_id' => $session->session_id,
            'tool_name' => 'search_help',
            'args' => [
                'query' => 'verification',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'tool_name' => 'search_help',
            ])
            ->assertJsonStructure([
                'result' => ['matches'],
                'display_card' => ['type', 'title', 'data'],
                'speak_text',
            ]);

        $this->assertEquals('help_card', $response->json('display_card.type'));
    }

    public function test_tool_search_academy(): void
    {
        $user = User::factory()->create();
        $session = $this->createActiveSession($user);

        Course::create([
            'instructor_id' => $user->id,
            'title' => 'Ethiopian Green Coffee Export Licensing Masterclass',
            'slug' => 'ethiopian-green-coffee-export-licensing-masterclass',
            'description' => 'Comprehensive guidance on ECTA licensing, cupping standards, and trade documentation.',
            'regular_price' => 1500.00,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/ai/voice/tool', [
            'session_id' => $session->session_id,
            'tool_name' => 'search_academy',
            'args' => [
                'query' => 'Licensing',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'tool_name' => 'search_academy',
            ])
            ->assertJsonStructure([
                'result' => ['courses'],
                'display_card' => ['type', 'title', 'data' => ['courses']],
                'speak_text',
            ]);

        $this->assertEquals('academy_courses', $response->json('display_card.type'));
    }
}
