<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardUnifiedSidebarTest extends TestCase
{
    public function test_student_dashboard_renders_unified_sidebar_with_all_sections(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'phone' => '+251911223344',
            'phone_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('dashboard-sidebar');
        $response->assertSee('dashboard-menu-toggle');
        // Sections
        $response->assertSee('Overview');
        $response->assertSee('Coffee Trade &amp; Tools', false);
        $response->assertSee('Academy &amp; Training', false);
        $response->assertSee('Network &amp; Events', false);
        $response->assertSee('Account &amp; Settings', false);
        // Links
        $response->assertSee(route('student.dashboard'));
        $response->assertSee(route('marketplace.dashboard'));
        $response->assertSee(route('market-prices.index'));
        $response->assertSee(route('student.coffee-toolkit.index'));
        $response->assertSee(route('marketplace.verification.center'));
        $response->assertSee(route('student.ai-chat.index'));
        $response->assertSee(route('student.my-courses'));
        $response->assertSee(route('student.settings'));
    }

    public function test_marketplace_dashboard_renders_unified_sidebar_and_active_trade_desk(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'phone' => '+251911223344',
            'phone_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('marketplace.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('dashboard-sidebar');
        $response->assertSee('dashboard-menu-toggle');
        $response->assertSee('Trade Desk');
        $response->assertSee('Coffee Trade &amp; Tools', false);
        $response->assertSee(route('student.dashboard'));
        $response->assertSee(route('marketplace.dashboard'));
        $response->assertSee(route('marketplace.verification.center'));
    }

    public function test_marketplace_verification_center_renders_unified_sidebar(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'phone' => '+251911223344',
            'phone_verified_at' => now(),
        ]);

        $org = Organization::create([
            'name' => 'Sidama Highland Coffee Union',
            'slug' => 'sidama-highland-coffee-union',
            'business_type' => 'exporter',
            'business_role' => 'exporter',
            'created_by' => $user->id,
        ]);

        $user->organizations()->attach($org->id, [
            'role' => 'owner',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('marketplace.verification.center'));

        $response->assertStatus(200);
        $response->assertSee('dashboard-sidebar');
        $response->assertSee('dashboard-menu-toggle');
        $response->assertSee('Verification Vault');
        $response->assertSee('Organization Verification');
        $response->assertSee(route('student.dashboard'));
        $response->assertSee(route('marketplace.dashboard'));
    }

    public function test_all_major_dashboard_pages_have_unified_sidebar(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
            'phone' => '+251911223344',
            'phone_verified_at' => now(),
        ]);

        $routes = [
            'student.my-courses',
            'student.coffee-toolkit.index',
            'student.ai-chat.index',
            'student.community.index',
            'student.notifications.index',
            'student.messages.index',
            'student.certificates',
            'student.assignments',
            'student.events.bookings',
            'student.live-classes.index',
            'student.payments',
            'student.settings',
        ];

        foreach ($routes as $routeName) {
            $response = $this->actingAs($user)->get(route($routeName));
            $response->assertStatus(200, "Route {$routeName} failed with status {$response->status()}");
            $response->assertSee('dashboard-sidebar', false, "Route {$routeName} missing sidebar");
            $response->assertSee('dashboard-menu-toggle', false, "Route {$routeName} missing mobile toggle");
            $response->assertSee('Coffee Trade &amp; Tools', false, "Route {$routeName} missing Coffee Trade section");
        }
    }
}
