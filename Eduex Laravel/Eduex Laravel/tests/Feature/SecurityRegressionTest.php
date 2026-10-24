<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    public function test_arbitrary_storage_route_blocks_private_storage_files(): void
    {
        $response = $this->get('/storage/app/settings.json');
        $response->assertStatus(403);
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
