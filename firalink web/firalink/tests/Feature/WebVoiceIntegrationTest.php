<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebVoiceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_chat_page_renders_voice_orb_and_complies_with_branding(): void
    {
        $response = $this->get('/ai-chat');

        $response->assertStatus(200);

        // Assets
        $response->assertSee('assets/front/css/fira-voice.css');
        $response->assertSee('assets/front/js/fira-voice.js');

        // Voice UI Elements
        $response->assertSee('id="page-voice-overlay"', false);
        $response->assertSee('id="page-voice-orb"', false);
        $response->assertSee('id="page-voice-status-pill"', false);
        $response->assertSee('id="page-voice-captions"', false);
        $response->assertSee('id="page-voice-cards"', false);
        $response->assertSee('id="page-voice-oromo-fallback"', false);
        $response->assertSee('id="page-voice-controls-bar"', false);

        // Language Selector
        $response->assertSee('value="en"', false);
        $response->assertSee('value="am"', false);
        $response->assertSee('value="om"', false);

        // Product Rule 1: No screen, copy, or footer says "Google Gemini AI"
        $response->assertDontSee('Google Gemini AI');
    }

    public function test_floating_widget_renders_voice_view_and_complies_with_branding(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Assets
        $response->assertSee('assets/front/css/fira-voice.css');
        $response->assertSee('assets/front/js/fira-voice.js');

        // Floating Voice Orb elements
        $response->assertSee('id="fira-ai-voice-toggle-btn"', false);
        $response->assertSee('id="fira-ai-mic-btn"', false);
        $response->assertSee('id="fira-voice-panel-view"', false);
        $response->assertSee('id="fira-voice-orb"', false);
        $response->assertSee('id="fira-voice-status-pill"', false);
        $response->assertSee('id="fira-voice-oromo-fallback"', false);

        // Product Rule 1: No screen, copy, or footer says "Google Gemini AI"
        $response->assertDontSee('Google Gemini AI');
    }
}
