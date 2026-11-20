<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\DealRoom;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\OrganizationVerification;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Models\VerificationTier3Grant;
use App\Services\Auth\FreshAuth;
use App\Services\Verification\FiraId;
use App\Services\Verification\TierService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

class VerificationTiersTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('verification');
        Notification::fake();
        config(['verification.id_hash_key' => 'test-only-id-hash-key', 'verification.admin_email' => 'queue@example.test']);
    }

    private function exporter(): array
    {
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $org->forceFill(['business_role' => 'exporter', 'created_by' => $owner->id])->save();

        return [$org, $owner];
    }

    private function inReview(Organization $org, string $type, array $extra = []): VerificationDocument
    {
        $config = TierService::documentTypeConfig($type);
        $container = OrganizationVerification::firstOrCreate(['organization_id' => $org->id, 'target_role' => $org->roleKey()]);
        $doc = VerificationDocument::create([
            'organization_verification_id' => $container->id,
            'organization_id' => $org->id,
            'document_type' => $type,
            'file_path' => 'test/' . $type . '.pdf',
            'file_disk' => 'verification',
            'original_filename' => $type . '.pdf',
            'file_size_kb' => 40,
            'mime_type' => 'application/pdf',
            'expires_at' => ($config['expires'] ?? false) ? now()->addYear() : null,
            'submitted_at' => now(),
        ]);
        $doc->forceFill(['tier' => (int) $config['tier'], 'status' => VerificationDocument::STATUS_IN_REVIEW] + $extra)->save();

        return $doc;
    }

    private function totp(string $secret): string
    {
        return app(Google2FA::class)->getCurrentOtp($secret);
    }

    // ---- Tier computation ----------------------------------------------

    public function test_tier_comes_only_from_approved_unexpired_documents(): void
    {
        [$org] = $this->exporter();
        $this->assertSame(0, app(TierService::class)->compute($org)['tier']);

        // A role-level approval no longer verifies anyone.
        $v = OrganizationVerification::firstOrCreate(['organization_id' => $org->id, 'target_role' => 'exporter']);
        $v->forceFill(['status' => OrganizationVerification::STATUS_APPROVED])->save();
        app(TierService::class)->recompute($org);
        $this->assertSame(0, (int) $org->fresh()->verification_tier);

        $org = $this->approveBusiness($org, 'exporter');
        $this->assertSame(1, (int) $org->verification_tier);
        $this->assertTrue($org->hasActiveTier(1));
        $this->assertMatchesRegularExpression('/^FL-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{4}-[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{4}$/', $org->fira_id);
        $this->assertNotNull($org->tier1_first_granted_at);

        // One required document missing: back to Tier 0.
        $org->verificationDocuments()->where('document_type', 'bank_letter')->update(['status' => VerificationDocument::STATUS_REJECTED]);
        app(TierService::class)->recompute($org);
        $this->assertSame(0, (int) $org->fresh()->verification_tier);
    }

    public function test_tier_2_needs_every_tier_2_document_for_the_role(): void
    {
        [$org] = $this->exporter();
        $org = $this->approveBusiness($org, 'exporter', null, 2);
        $this->assertSame(2, (int) $org->verification_tier);

        // Buyers have Tier 1 only.
        $buyerOrg = $this->makeOrganization($this->makeUser());
        $this->assertSame(1, (int) $this->approveBusiness($buyerOrg, 'buyer', null, 2)->verification_tier);
        $this->assertSame(1, TierService::maxTier($buyerOrg->fresh()));
    }

    public function test_clients_cannot_set_tiers_or_document_statuses(): void
    {
        [$org, $owner] = $this->exporter();

        $org->update(['verification_tier' => 3, 'is_verified' => true, 'fira_id' => 'FL-AAAA-AAAA', 'verification_valid_until' => now()->addYear()]);
        $org->refresh();
        $this->assertSame(0, (int) $org->verification_tier);
        $this->assertNull($org->fira_id);

        FreshAuth::markUser($owner);
        Sanctum::actingAs($owner);
        $res = $this->post('/api/marketplace/verification/documents', [
            'document_type' => 'trade_license',
            'document_number' => 'TL-100',
            'expires_at' => now()->addYear()->toDateString(),
            'pages' => [$this->realPdf()],
            'status' => 'approved',
            'tier' => 3,
            'reviewed_by' => $owner->id,
        ], ['Accept' => 'application/json'])->assertStatus(201);

        $doc = VerificationDocument::findOrFail($res->json('data.document_id'));
        $this->assertSame(VerificationDocument::STATUS_IN_REVIEW, $doc->status);
        $this->assertSame(1, (int) $doc->tier);
        $this->assertNull($doc->reviewed_by);
        $this->assertSame(0, (int) $org->fresh()->verification_tier);

        // The number is stored encrypted, never in plain text.
        $raw = \DB::table('verification_documents')->where('id', $doc->id)->value('document_number');
        $this->assertNotSame('TL-100', $raw);
        $this->assertSame('TL-100', $doc->document_number);

        $this->getJson('/api/marketplace/verification/status')->assertOk()
            ->assertJsonPath('data.tier', 0)
            ->assertJsonPath('data.fira_id', null)
            ->assertJsonPath('data.expected_review_business_days', 3);
    }

    public function test_owner_id_upload_is_refused_without_the_id_hash_key(): void
    {
        config(['verification.id_hash_key' => null]);
        [, $owner] = $this->exporter();
        FreshAuth::markUser($owner);
        Sanctum::actingAs($owner);

        $this->post('/api/marketplace/verification/documents', [
            'document_type' => 'owner_id',
            'document_number' => 'ID-1',
            'expires_at' => now()->addYear()->toDateString(),
            'pages' => [$this->realPdf()],
        ], ['Accept' => 'application/json'])->assertStatus(503)->assertJsonPath('code', 'ID_HASH_NOT_CONFIGURED');
        $this->assertSame([], Storage::disk('verification')->allFiles());
    }

    public function test_expiry_job_expires_documents_drops_the_tier_and_reminds_before(): void
    {
        [$org, $owner] = $this->exporter();
        $org = $this->approveBusiness($org, 'exporter', now()->addDays(7));
        $this->assertSame(1, (int) $org->verification_tier);

        $this->artisan('verification:expire-and-remind')->assertSuccessful();
        $doc = $org->verificationDocuments()->where('document_type', 'trade_license')->first();
        $this->assertContains(7, $doc->reminders_sent);
        $this->assertContains(30, $doc->reminders_sent);
        $this->assertSame(1, (int) $org->fresh()->verification_tier);

        // Reminders are sent once.
        $this->artisan('verification:expire-and-remind')->assertSuccessful();
        $this->assertCount(2, $doc->fresh()->reminders_sent);

        $this->travel(8)->days();
        $this->artisan('verification:expire-and-remind')->assertSuccessful();
        $this->assertSame(VerificationDocument::STATUS_EXPIRED, $doc->fresh()->status);
        $org->refresh();
        $this->assertSame(0, (int) $org->verification_tier);
        $this->assertSame('expired', $org->workspaceStatus());
        $this->assertNotNull($org->fira_id);

        $this->getJson('/api/sellers/' . $org->fira_id)->assertOk()->assertJsonPath('data.status', 'expired')->assertJsonPath('data.tier', null);
        $this->travelBack();
    }

    // ---- Two-factor ------------------------------------------------------

    public function test_staff_must_enrol_and_pass_two_factor_before_any_admin_page(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/admin/verification-review')->assertRedirect(route('staff.two-factor.setup'));
        $this->actingAs($admin)->get('/admin/dashboard')->assertRedirect(route('staff.two-factor.setup'));
        $this->actingAs($admin)->getJson('/admin/verification-review')->assertStatus(403)->assertJsonPath('code', 'TWO_FACTOR_REQUIRED');

        // Setup shows a QR; a wrong code does not enrol.
        $this->actingAs($admin)->get(route('staff.two-factor.setup'))->assertOk()->assertSee('<svg', false);
        $this->actingAs($admin)->post(route('staff.two-factor.confirm'), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertNull($admin->fresh()->two_factor_confirmed_at);

        $secret = \Laravel\Fortify\Fortify::currentEncrypter()->decrypt($admin->fresh()->two_factor_secret);
        $this->actingAs($admin)->post(route('staff.two-factor.confirm'), ['code' => $this->totp($secret)])
            ->assertRedirect(route('staff.two-factor.recovery'));
        $this->assertNotNull($admin->fresh()->two_factor_confirmed_at);
        $this->assertCount(8, $admin->fresh()->recoveryCodes());
        $this->assertTrue(AuditLog::where('action', 'staff_two_factor_enabled')->where('auditable_id', $admin->id)->exists());
    }

    public function test_enrolled_staff_pass_the_challenge_with_a_code_or_a_single_use_recovery_code(): void
    {
        $admin = $this->makeUser('admin');
        $secret = $this->enrolStaffTwoFactor($admin);

        $this->actingAs($admin)->get('/admin/verification-review')->assertRedirect(route('staff.two-factor.challenge'));
        $this->actingAs($admin)->post(route('staff.two-factor.verify'), ['code' => '123456'])->assertSessionHasErrors('code');

        $this->actingAs($admin)->post(route('staff.two-factor.verify'), ['code' => $this->totp($secret)])->assertRedirect();
        $this->actingAs($admin)->get('/admin/verification-review')->assertOk();

        // Recovery code works once.
        $this->flushSession();
        $this->actingAs($admin)->post(route('staff.two-factor.verify'), ['code' => 'recovery-one-1111'])->assertRedirect();
        $this->actingAs($admin)->get('/admin/verification-review')->assertOk();
        $this->flushSession();
        $this->actingAs($admin)->post(route('staff.two-factor.verify'), ['code' => 'recovery-one-1111'])->assertSessionHasErrors('code');
    }

    public function test_non_staff_cannot_open_the_review_queue_or_two_factor_setup(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user)->get('/admin/verification-review')->assertStatus(403);
        $this->actingAs($user)->get(route('staff.two-factor.setup'))->assertStatus(403);
    }

    public function test_reviewer_role_can_review_after_two_factor(): void
    {
        \Spatie\Permission\Models\Role::findOrCreate('reviewer', 'web');
        $reviewer = $this->makeUser();
        $reviewer->assignRole('reviewer');

        $this->actingAs($reviewer)->get('/admin/verification-review')->assertRedirect(route('staff.two-factor.setup'));
        $this->actingAsStaff($reviewer)->get('/admin/verification-review')->assertOk();
    }

    public function test_reset_2fa_command_clears_enrolment(): void
    {
        $admin = $this->makeUser('admin');
        $admin->forceFill(['email' => 'reset_' . uniqid() . '@example.test'])->save();
        $this->enrolStaffTwoFactor($admin);

        $this->artisan('admin:reset-2fa', ['email' => $admin->email, '--force' => true])->assertSuccessful();
        $this->assertNull($admin->fresh()->two_factor_secret);
        $this->assertNull($admin->fresh()->two_factor_confirmed_at);
    }

    // ---- Review actions --------------------------------------------------

    public function test_reviewer_approves_tier_1_documents_and_the_owner_gets_a_fira_id(): void
    {
        [$org] = $this->exporter();
        $admin = $this->makeUser('admin');
        $docs = array_map(fn ($t) => $this->inReview($org, $t), TierService::requiredDocuments($org, 1));

        $this->actingAsStaff($admin)->get('/admin/verification-review')->assertOk()->assertSee($org->name);
        $this->actingAsStaff($admin)->get(route('admin.verification-review.business', $org->id))->assertOk()
            ->assertSee(__('verification.admin.approve'))->assertSee(__('verification.checks.account_name_matches'));

        // Document pages open only through a signed link.
        $page = \App\Models\VerificationDocumentPage::create([
            'verification_document_id' => $docs[0]->id, 'page_number' => 1, 'file_path' => 'test/p1.pdf',
            'file_disk' => 'verification', 'mime_type' => 'application/pdf', 'file_size_kb' => 1,
        ]);
        Storage::disk('verification')->put('test/p1.pdf', '%PDF-1.4 test');
        $this->actingAsStaff($admin)->get(route('admin.verification-review.page', $page))->assertStatus(403);
        $this->actingAsStaff($admin)->get(\URL::temporarySignedRoute('admin.verification-review.page', now()->addMinutes(10), ['page' => $page->id]))->assertOk();

        foreach ($docs as $doc) {
            $checks = $doc->document_type === 'bank_letter' ? ['account_name_matches' => 1] : [];
            if ($doc->document_type === 'bank_letter') {
                // The bank check must be confirmed.
                $this->actingAsStaff($admin)->postJson(route('admin.verification-review.approve', $doc))->assertStatus(422)->assertJsonPath('code', 'CHECK_REQUIRED');
            }
            $this->actingAsStaff($admin)->post(route('admin.verification-review.approve', $doc), ['checks' => $checks])->assertRedirect();
            $this->assertSame(VerificationDocument::STATUS_APPROVED, $doc->fresh()->status);
            $this->assertSame($admin->id, (int) $doc->fresh()->reviewed_by);
        }

        $org->refresh();
        $this->assertSame(1, (int) $org->verification_tier);
        $this->assertNotNull($org->fira_id);
        $this->assertTrue(AuditLog::where('action', 'verification_document_approved')->where('organization_id', $org->id)->count() === count($docs));
    }

    public function test_ask_for_changes_and_reject_need_a_reason(): void
    {
        [$org] = $this->exporter();
        $admin = $this->makeUser('admin');
        $doc = $this->inReview($org, 'trade_license');

        $this->actingAsStaff($admin)->postJson(route('admin.verification-review.changes', $doc), [])->assertStatus(422)->assertJsonPath('code', 'REASON_REQUIRED');
        $this->actingAsStaff($admin)->post(route('admin.verification-review.changes', $doc), ['reason_code' => 'blurry_photo'])->assertRedirect();
        $this->assertSame(VerificationDocument::STATUS_NEEDS_CHANGES, $doc->fresh()->status);
        $this->assertSame('needs_changes', $org->fresh()->workspaceStatus());

        $doc2 = $this->inReview($org, 'tin_certificate');
        $this->actingAsStaff($admin)->post(route('admin.verification-review.reject', $doc2), ['reason' => 'Not a TIN certificate'])->assertRedirect();
        $this->assertSame(VerificationDocument::STATUS_REJECTED, $doc2->fresh()->status);
        $this->assertSame('Not a TIN certificate', $doc2->fresh()->review_reason);
    }

    public function test_reviewer_cannot_review_their_own_business(): void
    {
        [$org, $owner] = $this->exporter();
        $admin = $this->makeUser('admin');

        // Member of the business.
        OrganizationMembership::create(['organization_id' => $org->id, 'user_id' => $admin->id, 'role' => 'manager', 'is_active' => true, 'joined_at' => now()]);
        $doc = $this->inReview($org, 'trade_license');
        $this->actingAsStaff($admin)->postJson(route('admin.verification-review.approve', $doc))->assertStatus(403)->assertJsonPath('code', 'SELF_REVIEW');

        // Owner uses the reviewer's phone.
        [$org2, $owner2] = $this->exporter();
        $phone = '2519' . random_int(10000000, 99999999);
        $owner2->forceFill(['phone' => $phone])->save();
        $admin2 = $this->makeUser('admin');
        $admin2->forceFill(['phone' => $phone])->save();
        $doc2 = $this->inReview($org2, 'trade_license');
        $this->actingAsStaff($admin2)->postJson(route('admin.verification-review.reject', $doc2), ['reason' => 'x'])->assertStatus(403)->assertJsonPath('code', 'SELF_REVIEW');

        // Same owner ID as a business the reviewer belongs to.
        [$org3] = $this->exporter();
        $hash = \App\Services\Verification\DocumentReviewService::ownerIdHash('ET-123456');
        $mine = $this->makeOrganization($admin);
        $this->inReview($mine, 'owner_id', ['document_number_hash' => $hash]);
        $doc3 = $this->inReview($org3, 'owner_id', ['document_number_hash' => $hash]);
        $this->actingAsStaff($admin)->postJson(route('admin.verification-review.approve', $doc3))->assertStatus(403)->assertJsonPath('code', 'SELF_REVIEW');

        $this->assertSame(VerificationDocument::STATUS_IN_REVIEW, $doc->fresh()->status);
        $this->assertSame(VerificationDocument::STATUS_IN_REVIEW, $doc3->fresh()->status);
    }

    public function test_tier_2_approval_needs_a_fresh_step_up(): void
    {
        [$org] = $this->exporter();
        $this->approveBusiness($org, 'exporter');
        $admin = $this->makeUser('admin');
        $admin->forceFill(['password' => \Hash::make('Reviewer1A')])->save();
        $doc = $this->inReview($org, 'ecta_exporter_certificate');

        $this->actingAsStaff($admin)->postJson(route('admin.verification-review.approve', $doc))->assertStatus(403)->assertJsonPath('code', 'STEP_UP_REQUIRED');
        $this->actingAsStaff($admin)->postJson(route('admin.verification-review.approve', $doc), ['step_up_code' => '000000'])->assertStatus(403);
        $this->actingAsStaff($admin)->postJson(route('admin.verification-review.approve', $doc), ['current_password' => 'wrong'])->assertStatus(403);
        $this->assertSame(VerificationDocument::STATUS_IN_REVIEW, $doc->fresh()->status);

        $this->actingAsStaff($admin)->postJson(route('admin.verification-review.approve', $doc), ['current_password' => 'Reviewer1A'])->assertOk();
        $this->assertSame(VerificationDocument::STATUS_APPROVED, $doc->fresh()->status);

        // Within 10 minutes the next Tier 2 approval needs no new code; after that it does.
        $doc2 = $this->inReview($org, 'certified_lab');
        $this->postJson(route('admin.verification-review.approve', $doc2))->assertOk();
        $this->travel(11)->minutes();
        $doc3 = $this->inReview($org, 'taster_certificate');
        $this->postJson(route('admin.verification-review.approve', $doc3))->assertStatus(403)->assertJsonPath('code', 'STEP_UP_REQUIRED');
        $this->travelBack();
    }

    public function test_tier_3_needs_tier_2_step_up_and_a_complete_checklist_saved_to_the_audit_log(): void
    {
        [$org] = $this->exporter();
        $admin = $this->makeUser('admin');
        $secret = $this->enrolStaffTwoFactor($admin);
        $all = collect(config('verification.tiers.3.checklist'))->mapWithKeys(fn ($k) => [$k => 1])->all();
        $url = route('admin.verification-review.tier3.grant', $org);

        $this->approveBusiness($org, 'exporter');
        $this->actingAsStaff($admin)->postJson($url, ['checklist' => $all, 'step_up_code' => $this->totp($secret)])->assertStatus(422)->assertJsonPath('code', 'TIER2_REQUIRED');

        $this->approveBusiness($org, 'exporter', null, 2);
        $this->travel(11)->minutes(); // the code above counted as a step-up for 10 minutes
        $this->actingAsStaff($admin)->postJson($url, ['checklist' => $all])->assertStatus(403)->assertJsonPath('code', 'STEP_UP_REQUIRED');

        $missing = $all;
        unset($missing['call_made']);
        $admin->forceFill(['password' => \Hash::make('Reviewer1A')])->save();
        $this->actingAsStaff($admin)->postJson($url, ['checklist' => $missing, 'current_password' => 'Reviewer1A'])
            ->assertStatus(422)->assertJsonPath('code', 'CHECKLIST_INCOMPLETE');
        $this->assertSame(0, VerificationTier3Grant::where('organization_id', $org->id)->count());

        $this->postJson($url, ['checklist' => $all + ['call_made_note' => 'Called owner 14 Nov'], 'notes' => 'Visited warehouse'])->assertOk();
        $this->assertSame(3, (int) $org->fresh()->verification_tier);

        $log = AuditLog::where('action', 'verification_tier3_granted')->where('organization_id', $org->id)->firstOrFail();
        $this->assertTrue($log->after_state['checklist']['call_made']['done']);
        $this->assertSame('Called owner 14 Nov', $log->after_state['checklist']['call_made']['note']);

        $this->getJson('/api/sellers/' . $org->fresh()->fira_id)->assertJsonPath('data.vetted', true);
        $this->travelBack();
    }

    public function test_suspension_hides_the_business_and_the_public_page_says_so(): void
    {
        [$org] = $this->exporter();
        $org = $this->approveBusiness($org, 'exporter');
        $admin = $this->makeUser('admin');

        $this->actingAsStaff($admin, fresh: true)->postJson(route('admin.verification-review.suspend', $org), [])->assertStatus(422);
        $this->actingAsStaff($admin, fresh: true)->post(route('admin.verification-review.suspend', $org), ['reason' => 'Scam reports'])->assertRedirect();

        $org->refresh();
        $this->assertSame('suspended', $org->workspaceStatus());
        $this->assertFalse(TierService::isVisibleToBuyers($org));
        $this->getJson('/api/sellers/' . $org->fira_id)->assertOk()->assertJsonPath('data.status', 'suspended')->assertJsonPath('data.checked', []);
        $this->get('/verify/' . $org->fira_id)->assertOk()->assertSee(__('verification.public.suspended'));
    }

    // ---- Public seller page ----------------------------------------------

    public function test_public_page_never_leaks_private_fields(): void
    {
        [$org, $owner] = $this->exporter();
        $owner->forceFill(['phone' => '251911234567'])->save();
        $org->forceFill(['tin_number' => '0012345678'])->save();
        $org = $this->approveBusiness($org, 'exporter');
        $org->verificationDocuments()->where('document_type', 'owner_id')->first()->forceFill(['document_number' => 'ET-SECRET-ID'])->save();

        $json = $this->getJson('/api/sellers/' . $org->fira_id)->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertJsonPath('data.status', 'verified')
            ->assertJsonPath('data.tier', 1)
            ->assertJsonPath('data.business_role', 'exporter')
            ->assertJsonPath('data.confirmed_deals', 0)
            ->getContent();

        foreach (['0012345678', '251911234567', 'ET-SECRET-ID', 'tin_number', 'phone', 'email', 'trust_score', 'dispute_rate', 'escrow_supported', 'file_path'] as $needle) {
            $this->assertStringNotContainsString($needle, $json);
        }

        $html = $this->get('/verify/' . $org->fira_id)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('noindex', false)
            ->assertSee(__('verification.public.no_deals'))
            ->getContent();
        foreach (['0012345678', '251911234567', 'ET-SECRET-ID'] as $needle) {
            $this->assertStringNotContainsString($needle, $html);
        }
        $this->get('/verify/' . $org->fira_id . '/qr.svg')->assertOk()->assertHeader('Content-Type', 'image/svg+xml');

        // Lots embed the business: its TIN and billing email never go out.
        $lot = \App\Models\CoffeeListing::create([
            'seller_id' => $owner->id, 'organization_id' => $org->id, 'title' => 'Leak probe lot',
            'origin_region' => 'Guji', 'processing_method' => 'natural', 'grade' => 'grade_1',
            'total_quantity_bags' => 10, 'indicative_price_usd_per_lb' => 4, 'status' => 'active',
        ]);
        $org->forceFill(['billing_email' => 'billing-secret@example.test'])->save();
        foreach (['/api/marketplace/listings', '/api/marketplace/listings/' . $lot->id] as $url) {
            $body = $this->getJson($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('0012345678', $body);
            $this->assertStringNotContainsString('billing-secret@example.test', $body);
        }
    }

    public function test_unknown_malformed_and_never_verified_ids_get_the_same_not_found(): void
    {
        [$org] = $this->exporter();
        $org->forceFill(['fira_id' => FiraId::generateUnique()])->save(); // has an ID but never reached Tier 1

        foreach (['FL-2222-2222', 'not-an-id', $org->fira_id, '12345'] as $id) {
            $this->getJson('/api/sellers/' . $id)->assertStatus(404)->assertJsonPath('code', 'NOT_FOUND');
        }
        $this->get('/verify/FL-2222-2222')->assertStatus(404);
    }

    public function test_old_lookup_and_list_endpoints_are_gone(): void
    {
        [$org] = $this->exporter();
        $org->forceFill(['tin_number' => '0099887766'])->save();
        $org = $this->approveBusiness($org, 'exporter');

        $this->getJson('/api/verify-seller/' . $org->name)->assertStatus(404);
        $this->getJson('/api/verify-seller/0099887766')->assertStatus(404);
        $this->getJson('/api/sellers')->assertStatus(404);
        $this->getJson('/api/sellers/0099887766')->assertStatus(404);
    }

    public function test_fira_id_lookup_accepts_lowercase_and_no_dashes(): void
    {
        [$org] = $this->exporter();
        $org = $this->approveBusiness($org, 'exporter');
        $compact = strtolower(str_replace('-', '', $org->fira_id));

        $this->getJson('/api/sellers/' . $compact)->assertOk()->assertJsonPath('data.fira_id', $org->fira_id);
        $this->get('/verify?id=' . $compact)->assertRedirect(route('verify.show', $org->fira_id));
    }

    public function test_fira_ids_use_no_look_alike_characters_and_are_random(): void
    {
        $ids = collect(range(1, 300))->map(fn () => FiraId::generate());
        $this->assertSame(300, $ids->unique()->count());
        foreach ($ids as $id) {
            $this->assertMatchesRegularExpression('/^FL-[2-9A-HJKMNP-Z]{4}-[2-9A-HJKMNP-Z]{4}$/', $id);
            $this->assertDoesNotMatchRegularExpression('/[01OIL]/', substr($id, 3));
        }
        $this->assertNull(FiraId::normalize('FL-0000-OOOO'));
    }

    public function test_public_lookup_is_rate_limited(): void
    {
        config(['verification.public_lookup_per_minute' => 3]);
        RateLimiter::clear('public-seller|127.0.0.1');

        for ($i = 0; $i < 3; $i++) {
            $this->getJson('/api/sellers/FL-2222-2222')->assertStatus(404);
        }
        $this->getJson('/api/sellers/FL-2222-2223')->assertStatus(429);
    }

    public function test_confirmed_deals_count_only_completed_deals_with_a_different_owner(): void
    {
        [$deal, $buyer, $seller] = $this->makeDealRoom();
        $org = $deal->organization;
        $org->forceFill(['business_role' => 'exporter'])->save();
        $org = $this->approveBusiness($org, 'exporter');

        $this->assertSame(0, \App\Services\Verification\PublicSellerProfile::confirmedDeals($org));
        $deal->forceFill(['status' => DealRoom::STATUS_COMPLETED])->save();
        $this->assertSame(1, \App\Services\Verification\PublicSellerProfile::confirmedDeals($org));

        // A "buyer" using the seller's phone does not count.
        $phone = '2519' . random_int(10000000, 99999999);
        $seller->forceFill(['phone' => $phone])->save();
        $buyer->forceFill(['phone' => $phone])->save();
        $this->assertSame(0, \App\Services\Verification\PublicSellerProfile::confirmedDeals($org));
    }

    // ---- One-time command ------------------------------------------------

    public function test_drop_legacy_approvals_dry_run_changes_nothing(): void
    {
        [$org] = $this->exporter();
        $org->forceFill(['is_verified' => true, 'verified_roles' => ['exporter']])->save();

        $this->artisan('verification:drop-legacy-approvals')->assertSuccessful();
        $this->assertTrue((bool) $org->fresh()->is_verified);

        $this->artisan('verification:drop-legacy-approvals', ['--force' => true])->assertSuccessful();
        $this->assertFalse((bool) $org->fresh()->is_verified);
        $this->assertSame(0, (int) $org->fresh()->verification_tier);
    }
}
