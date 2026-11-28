<?php

namespace Tests\Feature;

use App\Http\Controllers\Student\DashboardController;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The shared header (partials.header) is styled by header-premium.css and driven by header-premium.js.
 * The student and instructor layouts once included the header without them, so signed-in dashboards
 * showed the menu as an unstyled, always-open list.
 */
class SiteShellAssetsTest extends TestCase
{
    public function test_every_layout_with_the_site_header_loads_its_styles_and_script(): void
    {
        $checked = 0;
        foreach (File::allFiles(resource_path('views/layouts')) as $file) {
            $source = $file->getContents();
            if (! str_contains($source, "@include('partials.header')")) {
                continue;
            }
            $checked++;
            $name = $file->getFilename();
            $this->assertStringContainsString("@include('partials.site-shell-styles')", $source, "{$name} includes the header without its styles");
            $this->assertStringContainsString("@include('partials.site-shell-scripts')", $source, "{$name} includes the header without its script");
        }

        $this->assertGreaterThanOrEqual(3, $checked, 'app, student and instructor layouts include the header');
    }

    public function test_student_dashboard_loads_the_header_styles_and_script(): void
    {
        $user = User::factory()->create(['role' => 'student', 'phone' => '+251911223355', 'phone_verified_at' => now()]);

        $this->actingAs($user)->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('data-site-header', false)
            ->assertSee('assets/front/css/header-premium.css', false)
            ->assertSee('assets/front/js/header-premium.js', false);
    }

    public function test_dashboard_banner_uses_a_solid_background_and_greets_by_first_name(): void
    {
        $user = User::factory()->create(['role' => 'student', 'name' => 'Mr. Donato Mosciski', 'phone' => '+251911223377', 'phone_verified_at' => now()]);

        $html = $this->actingAs($user)->get(route('student.dashboard'))->assertOk()->getContent();

        // fira-design-system.css strips inline gradients, so a gradient banner shows white text on nothing.
        $this->assertStringContainsString('background-color: var(--fira-primary, #6D1A95)', $html);
        $this->assertStringNotContainsString('linear-gradient(135deg, #6D1A95 0%, #4B1268 100%)', $html);
        $this->assertStringContainsString('Donato', $html);
        $this->assertStringNotContainsString('Mr.!', $html);
        // Font Awesome 6.0 (bundled) has no fa-shield-halved; the icon rendered as an empty box.
        $this->assertStringNotContainsString('fa-shield-halved', $html);
    }

    public function test_greeting_name_skips_titles(): void
    {
        $this->assertSame('Donato', DashboardController::greetingName('Mr. Donato Mosciski'));
        $this->assertSame('Abebe', DashboardController::greetingName('Ato Abebe Kebede'));
        $this->assertSame('Tigist', DashboardController::greetingName('W/ro Tigist Alemu'));
        $this->assertSame('ሶረሳ', DashboardController::greetingName('አቶ ሶረሳ ጌታቸው'));
        $this->assertSame('Soresa', DashboardController::greetingName('Soresa Getachew'));
        $this->assertSame('Dr.', DashboardController::greetingName('Dr.'));
    }

    public function test_marketplace_dashboard_loads_the_header_styles_and_script(): void
    {
        $user = User::factory()->create(['role' => 'student', 'phone' => '+251911223366', 'phone_verified_at' => now()]);

        $this->actingAs($user)->get(route('marketplace.dashboard'))
            ->assertOk()
            ->assertSee('assets/front/css/header-premium.css', false)
            ->assertSee('assets/front/js/header-premium.js', false);
    }
}
