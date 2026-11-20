<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    public function test_arbitrary_storage_route_blocks_private_storage_files(): void
    {
        // The private disk is not served at all (auth gating 2026-11-20, S-15); /storage serves the public disk only.
        $response = $this->get('/storage/app/settings.json');
        $this->assertContains($response->status(), [403, 404]);
        $this->assertFalse((bool) config('filesystems.disks.local.serve'));
    }

    public function test_file_controller_preview_blocks_path_traversal(): void
    {
        $response = $this->get('/file/preview?path=../../../../windows/win.ini');
        $response->assertStatus(404);
    }

    public function test_cron_endpoint_requires_configured_and_valid_key(): void
    {
        $response = $this->get('/cron/run?key=wrong-key');
        $response->assertStatus(403);
    }

    public function test_chapa_webhook_rejects_unsigned_payload(): void
    {
        $response = $this->postJson('/payment/chapa/webhook', [
            'status' => 'success',
            'trx_ref' => 'TEST-12345',
        ]);

        $response->assertStatus(401);
    }

    public function test_instructor_api_analytics_requires_authentication_and_role(): void
    {
        $response = $this->getJson('/api/instructor/analytics');
        $response->assertStatus(401);
    }
}
