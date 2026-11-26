<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BusinessCategory;
use App\Models\CoffeeListing;
use App\Models\Organization;
use App\Models\User;
use App\Services\Trade\CapabilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Release 2a: business categories and activities. Rights come from activities; a business without a
 * category keeps the rights of its role exactly as before.
 */
class BusinessCategoriesTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function create(User $user, array $extra): \Illuminate\Testing\TestResponse
    {
        Sanctum::actingAs($user);

        return $this->postJson('/api/user/organizations', array_merge([
            'name' => 'Biz ' . Str::random(5), 'entity_type' => 'private_limited_company', 'country' => 'Ethiopia',
        ], $extra));
    }

    public function test_setup_options_list_the_categories_in_both_languages(): void
    {
        Sanctum::actingAs($this->makeUser());
        $res = $this->getJson('/api/workspaces/options')->assertOk();
        $codes = collect($res->json('data.business_categories'))->pluck('code');
        foreach (['farmer', 'farmer_exporter', 'supplier', 'supplier_exporter', 'cooperative_union', 'processing_exporter',
            'specialty_exporter', 'general_exporter', 'international_buyer', 'roaster'] as $code) {
            $this->assertContains($code, $codes);
        }
        $farmer = collect($res->json('data.business_categories'))->firstWhere('code', 'farmer');
        $this->assertNotEmpty($farmer['label_am']);
        $this->assertSame(['produce', 'sell_domestic'], $farmer['capabilities']);
        $this->assertFalse($farmer['legally_confirmed']);
        $this->assertSame(['ecta_exporter_certificate'], collect($res->json('data.capabilities'))->firstWhere('code', 'sell_export')['required_documents']);
    }

    public function test_a_category_sets_the_role_and_declares_its_activities(): void
    {
        $user = $this->makeUser();
        $id = $this->create($user, ['business_category' => 'supplier_exporter'])->assertStatus(201)
            ->assertJsonPath('data.business_role', 'exporter')
            ->assertJsonPath('data.business_category.code', 'supplier_exporter')
            ->json('data.id');

        $org = Organization::findOrFail($id);
        $this->assertSame(['aggregate', 'sell_domestic', 'sell_export', 'buy_domestic'], CapabilityService::declared($org));

        $this->getJson("/api/organizations/{$id}/capabilities")->assertOk()
            ->assertJsonPath('data.can_edit', true)
            ->assertJsonPath('data.can_change_category', true)
            ->assertJsonFragment(['code' => 'sell_export', 'declared' => true, 'status' => 'needs_verification']);

        // Older apps still send only a role.
        $this->create($user, ['business_role' => 'buyer'])->assertStatus(201)->assertJsonPath('data.business_category', null);
        $this->create($user, [])->assertStatus(422);
    }

    public function test_legacy_businesses_keep_their_role_rights(): void
    {
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $org->forceFill(['business_role' => 'broker'])->save();
        $this->assertSame(['sell_export', 'buy_export'], CapabilityService::declared($org->fresh()));
        $this->assertSame('needs_verification', CapabilityService::status($org->fresh(), 'buy_export'));
    }

    public function test_a_farmer_cannot_list_export_lots_or_post_import_requests(): void
    {
        $user = $this->makeUser();
        $id = $this->create($user, ['business_category' => 'farmer'])->json('data.id');
        $org = $this->approveBusiness(Organization::findOrFail($id), 'supplier');

        Sanctum::actingAs($user);
        $this->postJson('/api/marketplace/seller/lots', ['title' => 'Cherry', 'origin_region' => 'Sidama'])
            ->assertStatus(403)->assertJsonPath('code', 'SELLER_ROLE_REQUIRED');
        $this->postJson('/api/marketplace/rfqs', ['title' => 'x', 'volume_value' => 1, 'volume_unit' => 'mt', 'destination' => 'Hamburg', 'incoterm' => 'FOB'])
            ->assertStatus(403)->assertJsonPath('code', 'BUYER_ROLE_REQUIRED');
        $this->assertSame('active', CapabilityService::status($org, 'sell_domestic'));
    }

    public function test_a_roaster_can_post_requests_and_switching_an_activity_off_takes_the_right_away(): void
    {
        $user = $this->makeUser();
        $id = $this->create($user, ['business_category' => 'roaster'])->json('data.id');
        $this->approveBusiness(Organization::findOrFail($id), 'buyer');

        Sanctum::actingAs($user);
        $rfq = ['title' => 'x', 'volume_value' => 1, 'volume_unit' => 'mt', 'destination' => 'Hamburg', 'incoterm' => 'FOB'];
        $this->postJson('/api/marketplace/rfqs', $rfq)->assertStatus(201);

        $this->putJson("/api/organizations/{$id}/capabilities", ['capabilities' => ['buy_domestic']])->assertOk()
            ->assertJsonFragment(['code' => 'buy_export', 'declared' => false, 'status' => 'off']);
        $this->postJson('/api/marketplace/rfqs', $rfq)->assertStatus(403)->assertJsonPath('code', 'BUYER_ROLE_REQUIRED');
        $this->assertTrue(AuditLog::where('auditable_id', $id)->where('action', 'business_capabilities_changed')->exists());

        // Not allowed for the category, or not the owner.
        $this->putJson("/api/organizations/{$id}/capabilities", ['capabilities' => ['sell_export']])->assertStatus(422)->assertJsonPath('code', 'CAPABILITY_NOT_ALLOWED');
        $manager = $this->makeUser();
        \App\Models\OrganizationMembership::create(['organization_id' => $id, 'user_id' => $manager->id, 'role' => 'manager', 'is_active' => true, 'joined_at' => now()]);
        Sanctum::actingAs($manager);
        $this->getJson("/api/organizations/{$id}/capabilities")->assertOk()->assertJsonPath('data.can_edit', false);
        $this->putJson("/api/organizations/{$id}/capabilities", ['capabilities' => []])->assertStatus(403)->assertJsonPath('code', 'OWNER_ONLY');
        Sanctum::actingAs($this->makeUser());
        $this->getJson("/api/organizations/{$id}/capabilities")->assertStatus(403);
    }

    public function test_switching_export_selling_off_hides_the_lots(): void
    {
        $user = $this->makeUser();
        $id = $this->create($user, ['business_category' => 'general_exporter'])->json('data.id');
        $org = $this->approveBusiness(Organization::findOrFail($id), 'exporter');
        $lot = CoffeeListing::create([
            'seller_id' => $user->id, 'organization_id' => $org->id, 'title' => 'Guji', 'origin_region' => 'Guji', 'processing_method' => 'natural',
            'grade' => 'grade_1', 'total_quantity_bags' => 10, 'available_quantity_bags' => 10, 'indicative_price_usd_per_lb' => 5, 'status' => 'active',
        ]);
        $this->assertTrue($lot->fresh()->isVisibleToBuyers());
        $this->assertSame(1, CoffeeListing::whereKey($lot->id)->visibleToBuyers()->count());

        Sanctum::actingAs($user);
        $this->putJson("/api/organizations/{$id}/capabilities", ['capabilities' => ['buy_domestic']])->assertOk();
        $this->assertFalse($lot->fresh()->isVisibleToBuyers());
        $this->assertSame(0, CoffeeListing::whereKey($lot->id)->visibleToBuyers()->count());
    }

    public function test_a_verified_business_keeps_its_role_when_choosing_a_category(): void
    {
        [$owner, $org] = [$this->makeUser(), null];
        $org = $this->makeOrganization($owner);
        $org->forceFill(['business_role' => 'exporter', 'entity_type' => 'private_limited_company'])->save();
        $org = $this->approveBusiness($org, 'exporter');

        Sanctum::actingAs($owner);
        $this->putJson("/api/organizations/{$org->id}/category", ['business_category' => 'international_buyer'])
            ->assertStatus(422)->assertJsonPath('code', 'CATEGORY_ROLE_LOCKED');
        $this->putJson("/api/organizations/{$org->id}/category", ['business_category' => 'specialty_exporter'])->assertOk()
            ->assertJsonPath('data.business_category.code', 'specialty_exporter')
            ->assertJsonPath('data.can_change_category', false)
            ->assertJsonFragment(['code' => 'sell_export', 'declared' => true, 'status' => 'active']);
    }

    public function test_admin_edits_a_category_and_removed_activities_leave_its_businesses(): void
    {
        $user = $this->makeUser();
        $id = $this->create($user, ['business_category' => 'roaster'])->json('data.id');
        $roaster = BusinessCategory::where('code', 'roaster')->firstOrFail();
        $admin = $this->makeUser('admin');

        $this->actingAsStaff($admin)->get(route('admin.trade.categories.index'))->assertOk()->assertSee($roaster->label_am);
        $this->actingAsStaff($admin)->put(route('admin.trade.categories.update', $roaster->id), [
            'label_en' => 'Roaster', 'label_am' => $roaster->label_am, 'business_role' => 'exporter', 'capabilities' => ['buy_export'], 'is_active' => 1,
        ])->assertSessionHas('error'); // in use: role cannot change
        $this->actingAsStaff($admin)->put(route('admin.trade.categories.update', $roaster->id), [
            'label_en' => 'Roaster', 'label_am' => $roaster->label_am, 'business_role' => 'buyer', 'capabilities' => ['buy_export'], 'is_active' => 1,
        ])->assertRedirect(route('admin.trade.categories.index'));

        $this->assertSame(['buy_export'], CapabilityService::declared(Organization::findOrFail($id)));
        $this->assertSame('Roaster', $roaster->fresh()->label_en);

        $this->actingAsStaff($admin)->post(route('admin.trade.categories.store'), [
            'code' => 'washing_station', 'label_en' => 'Washing station', 'label_am' => 'የቡና ማጠቢያ ጣቢያ', 'business_role' => 'supplier',
            'capabilities' => ['process', 'sell_domestic'], 'is_active' => 1,
        ])->assertRedirect();
        $this->assertDatabaseHas('business_categories', ['code' => 'washing_station', 'business_role' => 'supplier']);

        $this->actingAs($user)->get(route('admin.trade.categories.index'))->assertStatus(403);
    }
}
