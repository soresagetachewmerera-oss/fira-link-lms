<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\CoffeeListingMedia;
use App\Models\LotEvidence;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

class LotsTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('verification');
        Storage::fake('public');
        Storage::fake('local');
        Notification::fake();
    }

    /** A seller business (exporter) with its owner signed in to it. */
    private function seller(bool $approved = true, string $role = 'exporter'): array
    {
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $org->forceFill(['business_role' => $role, 'created_by' => $owner->id])->save();
        if ($approved) {
            $org = $this->approveBusiness($org, $role);
        }
        $owner->forceFill(['active_organization_id' => $org->id])->save();

        return [$org->fresh(), $owner];
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'title' => 'Bensa Washed G1 ' . Str::random(4),
            'origin_region' => 'Sidama',
            'crop_year' => '2025/2026',
            'processing_method' => 'washed',
            'grade' => 'grade_1',
            'total_quantity_bags' => 120,
            'minimum_order_bags' => 10,
            'indicative_price_usd_per_lb' => 4.85,
        ], $extra);
    }

    private function draft(User $owner, array $extra = []): int
    {
        Sanctum::actingAs($owner);

        return $this->postJson('/api/marketplace/seller/lots', $this->payload($extra))->assertStatus(201)->json('data.id');
    }

    /** A real JPEG with an EXIF capture time, like a photo taken in the app. */
    private function inAppJpeg(?\DateTimeInterface $taken = null, string $name = 'photo.jpg'): UploadedFile
    {
        $image = imagecreatetruecolor(400, 400);
        for ($x = 0; $x < 400; $x += 4) {
            for ($y = 0; $y < 400; $y += 4) {
                imagefilledrectangle($image, $x, $y, $x + 3, $y + 3, imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
            }
        }
        ob_start();
        imagejpeg($image, null, 90);
        $jpeg = ob_get_clean();
        imagedestroy($image);

        if ($taken) {
            $when = \Illuminate\Support\Carbon::instance($taken)->timezone(config('verification.timezone'))->format('Y:m:d H:i:s') . "\0";
            // TIFF (little endian): IFD0 -> Exif IFD pointer -> DateTimeOriginal (ASCII, 20 bytes).
            $tiff = 'II' . pack('v', 42) . pack('V', 8)
                . pack('v', 1) . pack('vvVV', 0x8769, 4, 1, 26) . pack('V', 0)
                . pack('v', 1) . pack('vvVV', 0x9003, 2, 20, 44) . pack('V', 0)
                . $when;
            $app1 = "\xFF\xE1" . pack('n', strlen($tiff) + 8) . "Exif\0\0" . $tiff;
            $jpeg = substr($jpeg, 0, 2) . $app1 . substr($jpeg, 2);
        }

        $path = tempnam(sys_get_temp_dir(), 'jpg');
        file_put_contents($path, $jpeg);

        return new UploadedFile($path, $name, 'image/jpeg', null, true);
    }

    private function uploadPhoto(int $lotId, string $kind, ?UploadedFile $file = null, array $extra = [])
    {
        return $this->post("/api/marketplace/seller/lots/{$lotId}/evidence/photos", array_merge([
            'kind' => $kind,
            'photo' => $file ?? $this->inAppJpeg(now()->subMinutes(5)),
            'captured_at' => now()->subMinutes(5)->toIso8601String(),
            'latitude' => 6.7612,
            'longitude' => 38.4091,
            'accuracy_m' => 12,
        ], $extra), ['Accept' => 'application/json']);
    }

    /** Three approved photos: the lot becomes Documented. */
    private function documented(CoffeeListing $lot, User $owner, User $admin): void
    {
        Sanctum::actingAs($owner);
        foreach (config('verification.lots.photo_kinds') as $kind) {
            $this->uploadPhoto($lot->id, $kind)->assertStatus(201);
        }
        foreach (LotEvidence::where('listing_id', $lot->id)->where('status', 'in_review')->get() as $ev) {
            $checks = $ev->kind === 'photo_bags' ? ['lot_code_visible' => 1] : [];
            $this->actingAsStaff($admin)->post(route('admin.verification-review.evidence.approve', $ev), ['checks' => $checks])->assertRedirect();
        }
    }

    // ---- Ownership ---------------------------------------------------------

    public function test_lots_belong_to_the_active_business_never_to_a_person(): void
    {
        [$org, $owner] = $this->seller();
        $other = $this->makeOrganization($this->makeUser());

        $id = $this->draft($owner, ['organization_id' => $other->id, 'seller_id' => 999999]);
        $lot = CoffeeListing::findOrFail($id);
        $this->assertSame($org->id, (int) $lot->organization_id);
        $this->assertSame($owner->id, (int) $lot->seller_id);
        $this->assertMatchesRegularExpression('/^L-[2-9A-HJKMNP-Z]{6}$/', $lot->lot_code);

        // Personal workspace: no lot can be created.
        $owner->forceFill(['active_organization_id' => null])->save();
        $this->postJson('/api/marketplace/seller/lots', $this->payload())->assertStatus(404)->assertJsonPath('code', 'ORGANIZATION_REQUIRED');
    }

    public function test_only_owners_and_managers_of_a_seller_business_manage_lots(): void
    {
        [$org, $owner] = $this->seller();
        $id = $this->draft($owner);

        $staff = $this->makeUser();
        OrganizationMembership::create(['organization_id' => $org->id, 'user_id' => $staff->id, 'role' => 'staff', 'is_active' => true, 'joined_at' => now()]);
        $staff->forceFill(['active_organization_id' => $org->id])->save();
        Sanctum::actingAs($staff);
        $this->getJson("/api/marketplace/seller/lots/{$id}")->assertOk();
        $this->postJson('/api/marketplace/seller/lots', $this->payload())->assertStatus(403)->assertJsonPath('code', 'NOT_ALLOWED');
        $this->putJson("/api/marketplace/seller/lots/{$id}", ['title' => 'x', 'origin_region' => 'Guji'])->assertStatus(403);
        $this->uploadPhoto($id, 'photo_bags')->assertStatus(403);

        // Someone from another business cannot see or change it.
        [, $stranger] = $this->seller();
        Sanctum::actingAs($stranger);
        $this->getJson("/api/marketplace/seller/lots/{$id}")->assertStatus(404);
        $this->putJson("/api/marketplace/seller/lots/{$id}", ['title' => 'x', 'origin_region' => 'Guji'])->assertStatus(403);
        $this->postJson("/api/marketplace/seller/lots/{$id}/publish")->assertStatus(403);

        // Buyer businesses do not sell lots; suppliers do.
        [, $buyer] = $this->seller(true, 'buyer');
        Sanctum::actingAs($buyer);
        $this->postJson('/api/marketplace/seller/lots', $this->payload())->assertStatus(403)->assertJsonPath('code', 'SELLER_ROLE_REQUIRED');
        [, $supplier] = $this->seller(true, 'supplier');
        $this->draft($supplier);
    }

    // ---- Hidden until Tier 1 -------------------------------------------------

    public function test_a_business_below_tier_1_can_draft_but_not_publish(): void
    {
        [$org, $owner] = $this->seller(false);
        $id = $this->draft($owner);

        $this->postJson("/api/marketplace/seller/lots/{$id}/publish")->assertStatus(403)->assertJsonPath('code', 'VERIFICATION_REQUIRED');
        $this->getJson('/api/marketplace/seller/lots')->assertOk()
            ->assertJsonPath('data.business.visible_to_buyers', false)
            ->assertJsonPath('data.lots.0.status', 'draft');

        $this->approveBusiness($org, 'exporter');
        $this->postJson("/api/marketplace/seller/lots/{$id}/publish")->assertOk()->assertJsonPath('data.status', 'active');
        $this->assertTrue(CoffeeListing::visibleToBuyers()->whereKey($id)->exists());
    }

    public function test_drafts_and_paused_lots_are_hidden_even_for_a_verified_business(): void
    {
        [, $owner] = $this->seller();
        $id = $this->draft($owner);
        $lot = CoffeeListing::findOrFail($id);
        $buyer = $this->makeUser();

        $this->assertFalse(CoffeeListing::visibleToBuyers()->whereKey($id)->exists());
        Sanctum::actingAs($buyer);
        $this->getJson("/api/marketplace/listings/{$lot->slug}")->assertStatus(404);

        Sanctum::actingAs($owner);
        $this->postJson("/api/marketplace/seller/lots/{$id}/publish")->assertOk();
        Sanctum::actingAs($buyer);
        $this->getJson("/api/marketplace/listings/{$lot->slug}")->assertOk();

        Sanctum::actingAs($owner);
        $this->postJson("/api/marketplace/seller/lots/{$id}/pause")->assertOk();
        Sanctum::actingAs($buyer);
        $this->getJson("/api/marketplace/listings/{$lot->slug}")->assertStatus(404);
    }

    public function test_publish_needs_a_complete_lot(): void
    {
        [, $owner] = $this->seller();
        Sanctum::actingAs($owner);
        $id = $this->postJson('/api/marketplace/seller/lots', ['title' => 'Half done', 'origin_region' => 'Guji'])->assertStatus(201)->json('data.id');
        CoffeeListing::whereKey($id)->update(['indicative_price_usd_per_lb' => null]);

        $this->postJson("/api/marketplace/seller/lots/{$id}/publish")->assertStatus(422)->assertJsonPath('code', 'LOT_INCOMPLETE');
        $this->assertSame('draft', CoffeeListing::findOrFail($id)->status);
    }

    public function test_web_lot_publishing_is_gone(): void
    {
        $this->assertFalse(Route::has('marketplace.listings.create'));
        $this->assertFalse(Route::has('marketplace.listings.store'));
        [, $owner] = $this->seller();
        $this->actingAs($owner)->post('/marketplace/publish-coffee', $this->payload())->assertStatus(404);
    }

    // ---- Evidence uploads ------------------------------------------------------

    public function test_photos_must_be_real_recent_in_app_photos_on_the_private_disk(): void
    {
        [, $owner] = $this->seller();
        $id = $this->draft($owner);

        // Gallery-style photo without EXIF, an old photo, a PNG, a dummy file: all refused.
        $this->uploadPhoto($id, 'photo_bags', $this->inAppJpeg(null))->assertStatus(422)->assertJsonPath('code', 'NOT_TAKEN_IN_APP');
        $this->uploadPhoto($id, 'photo_bags', $this->inAppJpeg(now()->subDays(3)))->assertStatus(422)->assertJsonPath('code', 'NOT_TAKEN_IN_APP');
        $this->uploadPhoto($id, 'photo_bags', $this->realPng('bags.png'))->assertStatus(422);
        $this->uploadPhoto($id, 'photo_bags', $this->dummyBytes('bags.jpg', 20480, "\xFF\xD8\xFF"))->assertStatus(422)->assertJsonPath('code', 'FILE_REJECTED');
        $this->uploadPhoto($id, 'photo_selfie')->assertStatus(422)->assertJsonPath('code', 'EVIDENCE_KIND_UNKNOWN');
        $this->assertSame([], Storage::disk('verification')->allFiles());

        $res = $this->uploadPhoto($id, 'photo_bags')->assertStatus(201)->assertJsonPath('data.state', 'in_review');
        $ev = LotEvidence::findOrFail($res->json('data.evidence_id'));
        $this->assertEqualsWithDelta(6.7612, $ev->latitude, 0.0001);
        $page = $ev->pages()->firstOrFail();
        Storage::disk('verification')->assertExists($page->file_path);
        Storage::disk('public')->assertMissing($page->file_path);

        // In review never changes the level, and the seller's view shows the state.
        $this->assertSame('claimed', CoffeeListing::findOrFail($id)->evidence_level);
        $this->getJson("/api/marketplace/seller/lots/{$id}")->assertOk()
            ->assertJsonPath('data.evidence.0.kind', 'photo_bags')
            ->assertJsonPath('data.evidence.0.state', 'in_review');
    }

    public function test_withdrawing_evidence_in_review_deletes_its_files(): void
    {
        [, $owner] = $this->seller();
        $id = $this->draft($owner);
        $evId = $this->uploadPhoto($id, 'photo_beans')->assertStatus(201)->json('data.evidence_id');
        $this->assertNotEmpty(Storage::disk('verification')->allFiles());

        $this->deleteJson("/api/marketplace/seller/lots/{$id}/evidence/{$evId}")->assertOk();
        $this->assertNull(LotEvidence::find($evId));
        $this->assertSame([], Storage::disk('verification')->allFiles());
        $this->assertTrue(AuditLog::where('action', 'lot_evidence_withdrawn')->where('auditable_id', $evId)->exists());
    }

    // ---- Levels ------------------------------------------------------------------

    public function test_level_is_computed_from_reviewed_evidence_only(): void
    {
        [$org, $owner] = $this->seller();
        $lot = CoffeeListing::findOrFail($this->draft($owner));
        $admin = $this->makeUser('admin');

        // The bags photo needs the lot-code check.
        Sanctum::actingAs($owner);
        $bags = LotEvidence::findOrFail($this->uploadPhoto($lot->id, 'photo_bags')->json('data.evidence_id'));

        // The queue's lot tab and the lot review page render, with location for the reviewer
        // and a signed link to the photo.
        $this->actingAsStaff($admin)->get('/admin/verification-review?tab=lots')->assertOk()->assertSee($lot->title)->assertSee($lot->lot_code);
        $this->actingAsStaff($admin)->get(route('admin.verification-review.lot', $lot->id))->assertOk()
            ->assertSee(__('lots.checks.lot_code_visible'))->assertSee('6.76120');
        $page = $bags->pages()->firstOrFail();
        $this->actingAsStaff($admin)->get(route('admin.verification-review.lot-page', $page))->assertStatus(403);
        $this->actingAsStaff($admin)->get(\URL::temporarySignedRoute('admin.verification-review.lot-page', now()->addMinutes(10), ['lotPage' => $page->id]))->assertOk();
        $this->actingAsStaff($admin)->postJson(route('admin.verification-review.evidence.approve', $bags))->assertStatus(422)->assertJsonPath('code', 'CHECK_REQUIRED');

        // Two of three photos approved: still Claimed.
        $this->actingAsStaff($admin)->post(route('admin.verification-review.evidence.approve', $bags), ['checks' => ['lot_code_visible' => 1]])->assertRedirect();
        Sanctum::actingAs($owner);
        $beans = LotEvidence::findOrFail($this->uploadPhoto($lot->id, 'photo_beans')->json('data.evidence_id'));
        $this->actingAsStaff($admin)->post(route('admin.verification-review.evidence.approve', $beans))->assertRedirect();
        $this->assertSame('claimed', $lot->fresh()->evidence_level);

        Sanctum::actingAs($owner);
        $ware = LotEvidence::findOrFail($this->uploadPhoto($lot->id, 'photo_warehouse')->json('data.evidence_id'));
        $this->actingAsStaff($admin)->post(route('admin.verification-review.evidence.approve', $ware))->assertRedirect();
        $lot->refresh();
        $this->assertSame('documented', $lot->evidence_level);
        $this->assertSame(now()->addDays(30)->toDateString(), $lot->evidence_valid_until->toDateString());

        // Lab report: needs the "confirmed with the lab" check.
        Sanctum::actingAs($owner);
        $labId = $this->post("/api/marketplace/seller/lots/{$lot->id}/evidence/lab-report", [
            'pages' => [$this->realPdf('cupping.pdf')], 'lab_name' => 'Addis Cupping Lab', 'report_number' => 'R-77',
        ], ['Accept' => 'application/json'])->assertStatus(201)->json('data.evidence_id');
        $lab = LotEvidence::findOrFail($labId);
        $this->actingAsStaff($admin)->postJson(route('admin.verification-review.evidence.approve', $lab))->assertStatus(422);
        $this->actingAsStaff($admin)->post(route('admin.verification-review.evidence.approve', $lab), ['checks' => ['lab_contacted' => 1]])->assertRedirect();
        $this->assertSame('lab_backed', $lot->fresh()->evidence_level);

        $this->assertTrue(AuditLog::where('action', 'listing_evidence_level_recomputed')->where('auditable_id', $lot->id)->exists());
        $this->assertFalse($org->fresh()->verification_tier === null);
    }

    public function test_reviewers_cannot_review_their_own_lots_and_need_two_factor(): void
    {
        [$org, $owner] = $this->seller();
        $lot = CoffeeListing::findOrFail($this->draft($owner));
        $evId = $this->uploadPhoto($lot->id, 'photo_beans')->json('data.evidence_id');
        $ev = LotEvidence::findOrFail($evId);

        $admin = $this->makeUser('admin');
        // Without two-factor: refused.
        $this->actingAs($admin)->postJson(route('admin.verification-review.evidence.approve', $ev))->assertStatus(403)->assertJsonPath('code', 'TWO_FACTOR_REQUIRED');

        // A member of the seller business cannot review its evidence.
        OrganizationMembership::create(['organization_id' => $org->id, 'user_id' => $admin->id, 'role' => 'manager', 'is_active' => true, 'joined_at' => now()]);
        $this->actingAsStaff($admin)->postJson(route('admin.verification-review.evidence.approve', $ev))->assertStatus(403)->assertJsonPath('code', 'SELF_REVIEW');

        // Sellers have no access to the review queue at all.
        $this->actingAs($owner)->post(route('admin.verification-review.evidence.approve', $ev))->assertStatus(403);
        $this->assertSame('in_review', $ev->fresh()->status);
    }

    public function test_ask_for_changes_needs_a_reason_and_keeps_the_level(): void
    {
        [, $owner] = $this->seller();
        $lot = CoffeeListing::findOrFail($this->draft($owner));
        $ev = LotEvidence::findOrFail($this->uploadPhoto($lot->id, 'photo_bags')->json('data.evidence_id'));
        $admin = $this->makeUser('admin');

        $this->actingAsStaff($admin)->postJson(route('admin.verification-review.evidence.changes', $ev), [])->assertStatus(422)->assertJsonPath('code', 'REASON_REQUIRED');
        $this->actingAsStaff($admin)->post(route('admin.verification-review.evidence.changes', $ev), ['reason_code' => 'lot_code_not_visible'])->assertRedirect();
        $this->assertSame('needs_changes', $ev->fresh()->status);
        $this->assertSame('claimed', $lot->fresh()->evidence_level);

        Sanctum::actingAs($owner);
        $this->getJson("/api/marketplace/seller/lots/{$lot->id}")
            ->assertJsonPath('data.evidence.0.state', 'needs_changes')
            ->assertJsonPath('data.evidence.0.reason_code', 'lot_code_not_visible');
    }

    public function test_buyers_see_a_location_free_copy_of_approved_photos_and_never_lab_files(): void
    {
        [, $owner] = $this->seller();
        $lot = CoffeeListing::findOrFail($this->draft($owner));
        $admin = $this->makeUser('admin');
        $this->documented($lot, $owner, $admin);
        Sanctum::actingAs($owner);
        $this->postJson("/api/marketplace/seller/lots/{$lot->id}/publish")->assertOk();

        $approved = LotEvidence::where('listing_id', $lot->id)->where('status', 'approved')->get();
        $this->assertCount(3, $approved);
        foreach ($approved as $ev) {
            Storage::disk('public')->assertExists($ev->public_path);
            $copy = Storage::disk('public')->path($ev->public_path);
            $exif = @exif_read_data($copy);
            $this->assertEmpty($exif['DateTimeOriginal'] ?? null, 'The public copy must not keep EXIF.');
        }
        $this->assertSame(1, CoffeeListingMedia::where('listing_id', $lot->id)->where('is_primary', true)->count());

        $buyer = $this->makeUser();
        Sanctum::actingAs($buyer);
        $res = $this->getJson("/api/marketplace/listings/{$lot->slug}")->assertOk()
            ->assertJsonPath('data.evidence.level', 'documented')
            ->assertJsonPath('data.evidence.lot_code', $lot->lot_code)
            ->assertJsonCount(3, 'data.evidence.photos');
        $body = $res->getContent();
        foreach (['latitude', 'longitude', '6.7612', 'organizations/', 'evidence_document_path'] as $needle) {
            $this->assertStringNotContainsString($needle, $body);
        }
    }

    public function test_expired_photos_drop_the_lot_to_claimed_and_it_stays_visible(): void
    {
        [, $owner] = $this->seller();
        $lot = CoffeeListing::findOrFail($this->draft($owner));
        $admin = $this->makeUser('admin');
        $this->documented($lot, $owner, $admin);
        Sanctum::actingAs($owner);
        $this->postJson("/api/marketplace/seller/lots/{$lot->id}/publish")->assertOk();
        $this->assertSame('documented', $lot->fresh()->evidence_level);

        $this->travel(24)->days();
        $this->artisan('verification:expire-and-remind')->assertSuccessful();
        $this->assertContains(7, LotEvidence::where('listing_id', $lot->id)->where('status', 'approved')->first()->reminders_sent);

        $this->travel(8)->days();
        $this->artisan('verification:expire-and-remind')->assertSuccessful();
        $lot->refresh();
        $this->assertSame('claimed', $lot->evidence_level);
        $this->assertNull($lot->evidence_valid_until);
        $this->assertSame(0, LotEvidence::where('listing_id', $lot->id)->where('status', 'approved')->count());
        $this->assertSame([], Storage::disk('public')->allFiles('marketplace/lots/' . $lot->id . '/evidence'));
        $this->assertTrue(CoffeeListing::visibleToBuyers()->whereKey($lot->id)->exists());
        $this->travelBack();
    }

    // ---- Lot card ---------------------------------------------------------------

    public function test_lot_card_shows_level_and_business_tier_separately_and_plans_change_neither(): void
    {
        [$org, $owner] = $this->seller();
        $lot = CoffeeListing::findOrFail($this->draft($owner));
        Sanctum::actingAs($owner);
        $this->postJson("/api/marketplace/seller/lots/{$lot->id}/publish")->assertOk();

        $find = function () use ($lot) {
            $items = collect($this->getJson('/api/marketplace/listings?per_page=100')->assertOk()->json('data.data') ?? $this->getJson('/api/marketplace/listings?per_page=100')->json('data'));

            return $items->firstWhere('id', $lot->id);
        };
        $card = $find();
        $this->assertNotNull($card);
        $this->assertSame('claimed', $card['evidence_level']);
        $this->assertSame(1, (int) $card['organization']['verification_tier']);

        // A paid plan changes neither the level nor the tier.
        $plan = \App\Models\Plan::where('slug', '!=', 'free')->first();
        if ($plan) {
            \App\Models\Subscription::create(['organization_id' => $org->id, 'plan_id' => $plan->id, 'status' => 'active', 'current_period_start' => now(), 'current_period_end' => now()->addMonth()]);
        }
        $card = $find();
        $this->assertSame('claimed', $card['evidence_level']);
        $this->assertSame(1, (int) $card['organization']['verification_tier']);
    }

    public function test_lot_limit_is_counted_per_business(): void
    {
        [$org, $owner] = $this->seller();
        $orgB = $this->approveBusiness($this->makeOrganization($owner), 'exporter');
        CoffeeListing::create(['seller_id' => $owner->id, 'organization_id' => $orgB->id, 'title' => 'Other business lot', 'origin_region' => 'Guji', 'status' => 'active']);

        $this->assertSame(0, app(\App\Services\EntitlementService::class)->used($org, 'active_lots'));
        $this->assertSame(1, app(\App\Services\EntitlementService::class)->used($orgB, 'active_lots'));
    }

    // ---- One-time commands -------------------------------------------------------

    public function test_move_lot_reports_command_is_dry_run_first_then_queues_reports_privately(): void
    {
        [$org, $owner] = $this->seller();
        $lot = CoffeeListing::findOrFail($this->draft($owner));
        Storage::disk('public')->put('marketplace/listings/documents/old.pdf', '%PDF-1.4 old report');
        $media = CoffeeListingMedia::create(['listing_id' => $lot->id, 'file_path' => 'marketplace/listings/documents/old.pdf', 'file_name' => 'old.pdf', 'media_type' => 'cupping_report', 'mime_type' => 'application/pdf']);

        $this->artisan('marketplace:move-lot-reports-private')->assertSuccessful();
        Storage::disk('public')->assertExists('marketplace/listings/documents/old.pdf');

        $this->artisan('marketplace:move-lot-reports-private', ['--force' => true])->assertSuccessful();
        Storage::disk('public')->assertMissing('marketplace/listings/documents/old.pdf');
        $this->assertNull(CoffeeListingMedia::find($media->id));
        $ev = LotEvidence::where('listing_id', $lot->id)->where('kind', 'lab_report')->firstOrFail();
        $this->assertSame('in_review', $ev->status);
        Storage::disk('verification')->assertExists($ev->pages()->first()->file_path);
        $this->assertSame('claimed', $lot->fresh()->evidence_level);

        $this->artisan('marketplace:report-lots-without-business')->assertSuccessful();
    }
}
