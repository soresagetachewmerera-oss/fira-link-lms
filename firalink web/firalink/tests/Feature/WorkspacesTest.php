<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\MarketplaceSellerProfile;
use App\Models\MarketplaceSubscriptionPlan;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\OrganizationVerification;
use App\Models\User;
use App\Models\UserMarketplaceSubscription;
use App\Support\BusinessEntityType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Slice 2: workspaces, intent and business setup.
 */
class WorkspacesTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    private function businessPayload(array $extra = []): array
    {
        return array_merge([
            'name' => 'Abebe Coffee ' . Str::random(5),
            'business_role' => 'exporter',
            'entity_type' => 'private_limited_company',
            'country' => 'Ethiopia',
        ], $extra);
    }

    private function addMember(Organization $org, User $user, string $role = 'staff'): OrganizationMembership
    {
        return OrganizationMembership::create([
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'role' => $role,
            'is_active' => true,
            'joined_at' => now(),
        ]);
    }

    private function makeLot(User $seller, Organization $org): CoffeeListing
    {
        return CoffeeListing::create([
            'seller_id' => $seller->id,
            'organization_id' => $org->id,
            'title' => 'Hidden probe ' . Str::random(6),
            'origin_region' => 'Guji',
            'processing_method' => 'natural',
            'grade' => 'grade_1',
            'total_quantity_bags' => 10,
            'indicative_price_usd_per_lb' => 5,
            'status' => 'active',
        ])->refresh();
    }

    // ---- Intent ---------------------------------------------------------

    public function test_intent_is_multi_select_and_selling_or_buying_needs_business_setup(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/user/onboarding-intent', ['intents' => ['learn', 'export']])
            ->assertOk()
            ->assertJsonPath('data.intents', ['learn', 'export'])
            ->assertJsonPath('data.needs_business_setup', true);
        $this->assertSame(['learn', 'export'], $user->fresh()->onboarding_intents);

        $this->postJson('/api/user/onboarding-intent', ['intents' => ['learn', 'events']])
            ->assertOk()
            ->assertJsonPath('data.needs_business_setup', false);
    }

    public function test_intent_can_be_skipped_and_rejects_unknown_values(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $this->postJson('/api/user/onboarding-intent', ['intents' => []])
            ->assertOk()
            ->assertJsonPath('data.intents', [])
            ->assertJsonPath('data.needs_business_setup', false);

        $this->postJson('/api/user/onboarding-intent', ['intents' => ['admin']])->assertStatus(422);
    }

    // ---- No automatic businesses ---------------------------------------

    public function test_no_business_is_created_automatically(): void
    {
        $user = $this->makeUser();
        $before = Organization::withTrashed()->count();

        $this->assertNull($user->primaryOrganization());
        $this->assertNull($user->currentOrganization());

        Sanctum::actingAs($user);
        $this->getJson('/api/user/organizations')
            ->assertOk()
            ->assertJsonPath('data.current_organization', null)
            ->assertJsonPath('data.personal.is_active_workspace', true)
            ->assertJsonCount(0, 'data.organizations');
        $this->getJson('/api/export-docs/shipments')->assertOk()->assertJsonCount(0, 'data.data');
        $this->getJson('/api/export-docs/documents')->assertOk()->assertJsonCount(0, 'data.data');

        $this->assertSame($before, Organization::withTrashed()->count());
    }

    public function test_export_docs_web_needs_sign_in_and_a_business(): void
    {
        $this->get('/export-docs')->assertRedirect(route('login'));

        $user = $this->makeUser();
        $before = Organization::withTrashed()->count();
        $this->actingAs($user)->get('/export-docs')->assertRedirect(route('marketplace.dashboard'));
        $this->assertSame($before, Organization::withTrashed()->count());
    }

    // ---- Business setup -------------------------------------------------

    public function test_options_list_every_entity_type_with_other_flagged(): void
    {
        $res = $this->getJson('/api/workspaces/options')->assertOk();

        $this->assertSame(BusinessEntityType::values(), array_column($res->json('data.entity_types'), 'value'));
        $this->assertContains('association', BusinessEntityType::values());
        $other = collect($res->json('data.entity_types'))->firstWhere('value', 'other');
        $this->assertTrue($other['needs_manual_review']);
        $this->assertSame('Ethiopia', $res->json('data.countries.0'));
        $this->assertSame(3, $res->json('data.max_owned_businesses'));
    }

    public function test_owner_creates_a_business_and_becomes_its_owner(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/user/organizations', $this->businessPayload(['tin_number' => '0012345678']))
            ->assertStatus(201)
            ->assertJsonPath('data.membership_role', 'owner')
            ->assertJsonPath('data.status', 'not_started')
            ->assertJsonPath('data.visible_to_buyers', false)
            ->assertJsonPath('data.needs_manual_review', false);

        $org = Organization::findOrFail($res->json('data.id'));
        $this->assertSame('private_limited_company', $org->entity_type);
        $this->assertSame($org->id, $user->fresh()->currentOrganization()?->id);
        $this->assertTrue(AuditLog::where('action', 'organization_created')->where('auditable_id', $org->id)->exists());
    }

    public function test_entity_type_other_is_flagged_for_manual_review(): void
    {
        Sanctum::actingAs($this->makeUser());

        $res = $this->postJson('/api/user/organizations', $this->businessPayload(['entity_type' => 'other']))
            ->assertStatus(201)
            ->assertJsonPath('data.needs_manual_review', true);
        $this->assertTrue((bool) Organization::find($res->json('data.id'))->needs_manual_review);
    }

    public function test_tin_is_validated_per_country(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/user/organizations', $this->businessPayload(['tin_number' => '12345']))
            ->assertStatus(422)->assertJsonValidationErrors('tin_number');
        $this->postJson('/api/user/organizations', $this->businessPayload(['tin_number' => 'ABC1234567']))
            ->assertStatus(422)->assertJsonValidationErrors('tin_number');
        $this->postJson('/api/user/organizations', $this->businessPayload(['country' => 'Kenya', 'tin_number' => 'P051234567X']))
            ->assertStatus(201);
        $this->postJson('/api/user/organizations', $this->businessPayload(['country' => 'Atlantis']))
            ->assertStatus(422)->assertJsonValidationErrors('country');
        $this->postJson('/api/user/organizations', $this->businessPayload(['entity_type' => 'llc']))
            ->assertStatus(422)->assertJsonValidationErrors('entity_type');
    }

    public function test_limit_of_three_businesses_and_multiple_business_audit(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $first = $this->postJson('/api/user/organizations', $this->businessPayload())->assertStatus(201)->json('data.id');
        $this->assertFalse(AuditLog::where('action', 'owner_multiple_businesses')->where('user_id', $user->id)->exists());

        $second = $this->postJson('/api/user/organizations', $this->businessPayload())->assertStatus(201)->json('data.id');
        $log = AuditLog::where('action', 'owner_multiple_businesses')->where('auditable_id', $second)->first();
        $this->assertNotNull($log);
        $this->assertEqualsCanonicalizing([$first, $second], $log->after_state['same_owner_account']);

        $this->postJson('/api/user/organizations', $this->businessPayload())->assertStatus(201);
        $this->postJson('/api/user/organizations', $this->businessPayload())
            ->assertStatus(422)
            ->assertJsonPath('code', 'WORKSPACE_LIMIT');

        // The limit is not a plan feature: buying a plan does not raise it.
        $plan = MarketplaceSubscriptionPlan::first();
        if ($plan) {
            UserMarketplaceSubscription::create([
                'user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active',
                'starts_at' => now(), 'expires_at' => now()->addMonth(),
            ]);
        }
        $this->postJson('/api/user/organizations', $this->businessPayload())->assertJsonPath('code', 'WORKSPACE_LIMIT');
    }

    public function test_same_tin_on_another_business_is_logged_for_review(): void
    {
        Sanctum::actingAs($this->makeUser());
        $tin = (string) random_int(1000000000, 9999999999);
        $this->postJson('/api/user/organizations', $this->businessPayload(['tin_number' => $tin]))->assertStatus(201);

        Sanctum::actingAs($this->makeUser());
        $id = $this->postJson('/api/user/organizations', $this->businessPayload(['tin_number' => $tin]))->assertStatus(201)->json('data.id');

        $log = AuditLog::where('action', 'owner_multiple_businesses')->where('auditable_id', $id)->first();
        $this->assertNotNull($log);
        $this->assertArrayHasKey('same_tin', $log->after_state);
    }

    // ---- Verification fields are server-only -----------------------------

    public function test_a_request_cannot_set_verification_or_status_fields(): void
    {
        $user = $this->makeUser();
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/user/organizations', $this->businessPayload([
            'is_verified' => true,
            'verified_roles' => ['exporter', 'buyer'],
            'status' => 'active',
            'needs_manual_review' => false,
            'entity_type' => 'other',
        ]))->assertStatus(201)->json('data.id');

        $org = Organization::findOrFail($id);
        $this->assertFalse((bool) $org->is_verified);
        $this->assertEmpty($org->verified_roles ?? []);
        $this->assertTrue((bool) $org->needs_manual_review);
        $this->assertSame('not_started', $org->workspaceStatus());
        $this->assertFalse(\App\Services\Verification\VerificationReviewService::isApprovedSeller($org));

        // Seller profile: verification_status stays where the server put it.
        $this->postJson('/api/marketplace/seller/profile', [
            'company_name' => 'Probe Exporter',
            'business_type' => 'exporter',
            'verification_status' => 'verified',
            'verified_at' => now()->toDateTimeString(),
        ])->assertSuccessful();
        $this->assertNotSame('verified', $user->fresh()->sellerProfile->verification_status);
    }

    public function test_verification_fields_are_not_mass_assignable(): void
    {
        $org = Organization::create([
            'name' => 'Mass ' . Str::random(6),
            'slug' => 'mass-' . Str::lower(Str::random(8)),
            'is_verified' => true,
            'verified_roles' => ['exporter'],
            'status' => 'suspended',
        ])->fresh();
        $this->assertFalse((bool) $org->is_verified);
        $this->assertEmpty($org->verified_roles ?? []);
        $this->assertNotSame('suspended', $org->status);

        $org->update(['is_verified' => true, 'verified_roles' => ['exporter'], 'status' => 'suspended']);
        $org->refresh();
        $this->assertFalse((bool) $org->is_verified);
        $this->assertNotSame('suspended', $org->status);

        $v = OrganizationVerification::create([
            'organization_id' => $org->id,
            'target_role' => 'exporter',
            'status' => OrganizationVerification::STATUS_APPROVED,
            'reviewed_by' => 1,
            'admin_notes' => 'self-approved',
        ])->fresh();
        $this->assertSame(OrganizationVerification::STATUS_DRAFT, $v->status);
        $this->assertNull($v->reviewed_by);

        $profile = MarketplaceSellerProfile::create([
            'user_id' => $this->makeUser()->id,
            'company_name' => 'Mass Profile',
            'business_type' => 'exporter',
            'verification_status' => 'verified',
        ])->fresh();
        $this->assertNotSame('verified', $profile->verification_status);
    }

    public function test_only_the_review_service_changes_verification_and_it_is_audited(): void
    {
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);

        $this->approveBusiness($org, 'exporter');

        $org->refresh();
        $this->assertTrue((bool) $org->is_verified);
        $this->assertContains('exporter', $org->verified_roles);
        $this->assertSame('verified', $org->workspaceStatus());
        $this->assertTrue(AuditLog::where('action', 'verification_tier_recomputed')->where('auditable_id', $org->id)->exists());
    }

    public function test_workspace_status_comes_from_verification_not_from_a_plan(): void
    {
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $plan = MarketplaceSubscriptionPlan::first();
        if ($plan) {
            UserMarketplaceSubscription::create([
                'user_id' => $owner->id, 'plan_id' => $plan->id, 'status' => 'active',
                'starts_at' => now(), 'expires_at' => now()->addMonth(),
            ]);
        }

        Sanctum::actingAs($owner);
        $this->getJson('/api/user/organizations')
            ->assertOk()
            ->assertJsonPath('data.organizations.0.status', 'not_started')
            ->assertJsonPath('data.organizations.0.visible_to_buyers', false);

        $v = OrganizationVerification::firstOrCreate(['organization_id' => $org->id, 'target_role' => 'exporter']);
        $doc = \App\Models\VerificationDocument::create([
            'organization_verification_id' => $v->id, 'organization_id' => $org->id, 'document_type' => 'tin_certificate',
            'file_path' => 'test/tin.pdf', 'file_disk' => 'verification', 'original_filename' => 'tin.pdf',
            'file_size_kb' => 40, 'mime_type' => 'application/pdf', 'submitted_at' => now(),
        ]);
        $this->assertSame('in_review', $org->fresh()->workspaceStatus());
        $doc->forceFill(['status' => \App\Models\VerificationDocument::STATUS_SUPERSEDED])->save();

        $this->approveBusiness($org, 'exporter', now()->subDay());
        $this->assertSame('expired', $org->fresh()->workspaceStatus());
        $this->assertFalse(\App\Services\Verification\VerificationReviewService::isApprovedSeller($org->fresh()));
    }

    // ---- Hidden until approved ------------------------------------------

    public function test_lots_of_unapproved_sellers_are_hidden_from_buyers(): void
    {
        $seller = $this->makeUser();
        $org = $this->makeOrganization($seller);
        $lot = $this->makeLot($seller, $org);
        $buyer = $this->makeUser();

        $this->assertFalse(CoffeeListing::visibleToBuyers()->whereKey($lot->id)->exists());
        $this->getJson("/api/marketplace/listings/{$lot->slug}")->assertStatus(404);
        $this->get("/marketplace/listings/{$lot->slug}")->assertStatus(404);

        Sanctum::actingAs($buyer);
        $this->getJson("/api/marketplace/listings/{$lot->id}")->assertStatus(404);
        $this->postJson("/api/marketplace/listings/{$lot->id}/save")->assertStatus(404);

        // The seller still sees their own lot.
        Sanctum::actingAs($seller);
        $this->getJson("/api/marketplace/listings/{$lot->id}")->assertOk();

        // Approval makes it visible.
        $this->approveBusiness($org, 'exporter');
        $this->assertTrue(CoffeeListing::visibleToBuyers()->whereKey($lot->id)->exists());
        Sanctum::actingAs($buyer);
        $this->getJson("/api/marketplace/listings/{$lot->id}")->assertOk();
    }

    public function test_expired_or_buyer_only_verification_keeps_lots_hidden(): void
    {
        $seller = $this->makeUser();
        $org = $this->makeOrganization($seller);
        $lot = $this->makeLot($seller, $org);

        $this->approveBusiness($org, 'buyer');
        $this->assertFalse(CoffeeListing::visibleToBuyers()->whereKey($lot->id)->exists());

        $seller = $this->makeUser();
        $org = $this->makeOrganization($seller);
        $lot = $this->makeLot($seller, $org);
        $this->approveBusiness($org, 'exporter', now()->subDay());
        $this->assertFalse(CoffeeListing::visibleToBuyers()->whereKey($lot->id)->exists());
        $this->getJson("/api/marketplace/listings/{$lot->id}")->assertStatus(404);
    }

    public function test_lot_without_a_business_is_hidden(): void
    {
        $seller = $this->makeUser();
        $lot = CoffeeListing::create([
            'seller_id' => $seller->id,
            'title' => 'No business ' . Str::random(6),
            'origin_region' => 'Limu',
            'processing_method' => 'washed',
            'grade' => 'grade_2',
            'total_quantity_bags' => 5,
            'indicative_price_usd_per_lb' => 4,
            'status' => 'active',
        ]);

        $this->assertFalse(CoffeeListing::visibleToBuyers()->whereKey($lot->id)->exists());
    }

    public function test_supplier_pages_hide_unapproved_sellers(): void
    {
        $seller = $this->makeUser();
        $org = $this->makeOrganization($seller);
        $slug = 'hidden-supplier-' . Str::lower(Str::random(8));
        MarketplaceSellerProfile::create([
            'user_id' => $seller->id,
            'organization_id' => $org->id,
            'company_name' => 'Hidden Supplier ' . $slug,
            'company_slug' => $slug,
            'business_type' => 'exporter',
        ]);

        $this->get("/marketplace/suppliers/{$slug}")->assertStatus(404);
        $this->get('/marketplace/suppliers')->assertOk()->assertDontSee($slug);

        $this->approveBusiness($org, 'exporter');
        $this->get("/marketplace/suppliers/{$slug}")->assertOk();
    }

    // ---- Switching and members ------------------------------------------

    public function test_admin_cannot_switch_into_another_business(): void
    {
        $org = $this->makeOrganization($this->makeUser());
        $admin = $this->makeUser('admin');
        Sanctum::actingAs($admin);

        $this->postJson('/api/user/organizations/switch', ['organization_id' => $org->id])
            ->assertStatus(403)
            ->assertJsonPath('code', 'NOT_A_MEMBER');
        $this->assertNull($admin->fresh()->active_organization_id);
        $this->getJson("/api/organizations/{$org->id}/members")->assertStatus(403);
    }

    public function test_member_switches_between_personal_and_business(): void
    {
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        Sanctum::actingAs($owner);

        $this->postJson('/api/user/organizations/switch', ['organization_id' => $org->id])
            ->assertOk()->assertJsonPath('data.organization.id', $org->id);
        $this->assertSame($org->id, $owner->fresh()->currentOrganization()?->id);

        $this->postJson('/api/user/organizations/switch', ['organization_id' => null])
            ->assertOk()->assertJsonPath('data.personal', true);
        $this->assertNull($owner->fresh()->currentOrganization());
    }

    public function test_members_list_is_read_only_for_members_and_owner_can_remove(): void
    {
        $owner = $this->makeUser();
        $member = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $this->addMember($org, $member);

        Sanctum::actingAs($member);
        $res = $this->getJson("/api/organizations/{$org->id}/members")
            ->assertOk()
            ->assertJsonPath('data.viewer_is_owner', false)
            ->assertJsonPath('data.can_add_members', false);
        $this->assertFalse(collect($res->json('data.members'))->contains('can_remove', true));
        $this->deleteJson("/api/organizations/{$org->id}/members/{$owner->id}")->assertStatus(403);
        $this->postJson("/api/organizations/{$org->id}/members", ['email' => $owner->email, 'role' => 'manager'])->assertStatus(403);

        Sanctum::actingAs($owner);
        $res = $this->getJson("/api/organizations/{$org->id}/members")
            ->assertOk()
            ->assertJsonPath('data.viewer_is_owner', true)
            ->assertJsonPath('data.can_add_members', false);
        $this->assertTrue(collect($res->json('data.members'))->firstWhere('user_id', $member->id)['can_remove']);
        $this->assertFalse(collect($res->json('data.members'))->firstWhere('user_id', $owner->id)['can_remove']);
    }

    public function test_removed_member_loses_access_at_once(): void
    {
        $owner = $this->makeUser();
        $member = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $this->addMember($org, $member);
        $member->switchOrganization($org->id);
        $this->assertSame($org->id, $member->fresh()->currentOrganization()?->id);

        Sanctum::actingAs($owner);
        $this->deleteJson("/api/organizations/{$org->id}/members/{$member->id}")->assertOk();
        $this->assertTrue(AuditLog::where('action', 'member_removed')->where('organization_id', $org->id)->exists());

        $member = $member->fresh();
        $this->assertNull($member->active_organization_id);
        $this->assertNull($member->currentOrganization());
        $this->assertNull($member->primaryOrganization());

        Sanctum::actingAs($member);
        $this->getJson("/api/organizations/{$org->id}/members")->assertStatus(403);
        $this->postJson('/api/user/organizations/switch', ['organization_id' => $org->id])->assertStatus(403);
        $this->getJson('/api/user/organizations')->assertOk()->assertJsonCount(0, 'data.organizations');
    }

    public function test_stale_active_workspace_is_cleared_on_read(): void
    {
        $owner = $this->makeUser();
        $member = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $membership = $this->addMember($org, $member);
        $member->switchOrganization($org->id);

        // Deactivated some other way (not through removeMember): reads still refuse it.
        $membership->update(['is_active' => false]);

        $member = $member->fresh();
        $this->assertNull($member->currentOrganization());
        $this->assertNull($member->fresh()->active_organization_id);
    }

    // ---- One-time report ------------------------------------------------

    public function test_auto_created_business_report_deletes_nothing(): void
    {
        $owner = $this->makeUser();
        $org = Organization::create([
            'name' => $owner->name,
            'slug' => 'auto-' . Str::lower(Str::random(10)),
        ]);
        $this->addMember($org, $owner, 'owner');
        $before = Organization::withTrashed()->count();

        $this->artisan('businesses:report-auto-created')
            ->expectsOutputToContain('Nothing was changed.')
            ->assertSuccessful();

        $this->assertSame($before, Organization::withTrashed()->count());
        $this->assertNotNull($org->fresh());
    }
}
