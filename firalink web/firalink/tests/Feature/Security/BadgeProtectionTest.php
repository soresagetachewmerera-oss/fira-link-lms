<?php

namespace Tests\Feature\Security;

use App\Models\CoffeeListing;
use App\Models\MarketplaceSellerProfile;
use App\Models\MarketplaceSubscriptionPlan;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserMarketplaceSubscription;
use App\Models\VerificationDocument;
use App\Services\MarketplaceSubscriptionService;
use Database\Seeders\MarketplaceSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Auth gating 2026-11-20, Phase 3: a badge or tier exists only when TierService computed it from
 * approved, unexpired documents, and the business is not suspended. Never from a client field, a
 * legacy flag, a plan, a payment, a seeder or a demo record.
 */
class BadgeProtectionTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    /** Columns that carry trust. Only the files in WRITERS may write them. */
    private const TRUST_COLUMNS = ['verification_tier', 'verification_valid_until', 'verification_reviewed_at', 'tier1_first_granted_at',
        'is_verified', 'verified_roles', 'evidence_level', 'verification_status'];

    /** The only code paths allowed to write trust columns (review, expiry, one-time lowering commands, guarded seeders). */
    private const WRITERS = [
        'app/Services/Verification/TierService.php',            // tier: computed from reviewed documents
        'app/Services/Verification/VerificationReviewService.php', // seller profile status: admin review action
        'app/Services/Lots/LotLevelService.php',                // lot level: computed from reviewed evidence
        'app/Console/Commands/DropLegacyVerifications.php',     // lowers only
        'app/Console/Commands/ResetListingEvidenceLevels.php',  // lowers only
        'app/Http/Controllers/Api/SellerLotController.php',     // new lot starts at "claimed"
        'database/seeders/LocalDemoSellerSeeder.php',           // APP_ENV=local only (tested)
        'database/seeders/MarketplaceSeeder.php',               // demo part APP_ENV=local only (tested)
    ];

    private function lot(Organization $org, User $owner, array $extra = []): CoffeeListing
    {
        $lot = new CoffeeListing();
        $lot->forceFill(array_merge([
            'organization_id' => $org->id, 'seller_id' => $owner->id, 'title' => 'Badge lot', 'slug' => 'badge-lot-' . Str::lower(Str::random(10)),
            'origin_region' => 'Guji', 'processing_method' => 'washed', 'grade' => 'grade_1', 'status' => 'active', 'published_at' => now(),
        ], $extra))->save();

        return $lot;
    }

    public function test_public_lot_json_sends_the_business_summary_never_raw_trust_columns(): void
    {
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $this->approveBusiness($org);
        $lot = $this->lot($org, $owner);

        foreach (["/api/marketplace/listings/{$lot->id}", '/api/marketplace/listings?per_page=50'] as $uri) {
            $json = json_encode($this->getJson($uri)->assertOk()->json());
            foreach (['is_verified', 'verified_roles', 'verification_valid_until', 'verification_reviewed_at', 'tier1_first_granted_at', 'seller_id', 'tin_number'] as $key) {
                $this->assertStringNotContainsString('"' . $key . '"', $json, "{$uri} sends {$key}");
            }
        }
        $this->getJson("/api/marketplace/listings/{$lot->id}")->assertJsonPath('data.listing.organization.verification_tier', 1);
    }

    public function test_the_business_summary_drops_the_tier_when_expired_or_suspended(): void
    {
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $this->approveBusiness($org);
        $lot = $this->lot($org, $owner, ['status' => 'draft']);
        Sanctum::actingAs($owner); // the owner may open a hidden lot of their own business

        $org->forceFill(['verification_valid_until' => now()->subDay()])->save();
        $this->getJson("/api/marketplace/listings/{$lot->id}")->assertOk()->assertJsonPath('data.listing.organization.verification_tier', 0);

        $org->forceFill(['verification_valid_until' => now()->addYear(), 'status' => 'suspended'])->save();
        $this->getJson("/api/marketplace/listings/{$lot->id}")->assertOk()->assertJsonPath('data.listing.organization.verification_tier', 0);
    }

    public function test_legacy_verified_fields_follow_the_active_tier(): void
    {
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $owner->forceFill(['active_organization_id' => $org->id])->save();
        $this->approveBusiness($org);
        Sanctum::actingAs($owner);

        $row = fn () => collect($this->getJson('/api/user/organizations')->assertOk()->json('data.organizations'))->firstWhere('id', $org->id);
        $this->assertTrue($row()['is_verified']);

        // Stored flags still say verified, but the business is suspended: the API must not.
        $org->forceFill(['status' => 'suspended'])->save();
        $this->assertTrue((bool) $org->fresh()->is_verified);
        $this->assertFalse($row()['is_verified']);
        $this->assertSame([], $row()['verified_roles']);
    }

    public function test_no_endpoint_lets_a_client_set_a_tier_flag_level_or_document_status(): void
    {
        Storage::fake('verification');
        Storage::fake('public');
        $owner = $this->makeUser();
        $owner->forceFill(['password' => bcrypt('Owner123A')])->save();
        Sanctum::actingAs($owner);
        $forged = [
            'verification_tier' => 3, 'is_verified' => true, 'verified_roles' => ['exporter'], 'verification_valid_until' => now()->addYears(5)->toDateString(),
            'verification_status' => 'verified', 'evidence_level' => 'inspected', 'status' => 'approved', 'tier' => 3, 'needs_manual_review' => false,
        ];

        // Business setup
        $created = $this->postJson('/api/user/organizations', $forged + ['name' => 'Forged Trading', 'business_role' => 'exporter', 'entity_type' => 'private_limited_company', 'country' => 'Ethiopia', 'tin_number' => '0012345678'])->assertSuccessful();
        $org = Organization::findOrFail($created->json('data.organization.id') ?? $created->json('data.id'));
        $this->assertSame(0, (int) $org->verification_tier);
        $this->assertFalse((bool) $org->is_verified);
        $this->assertEmpty($org->verified_roles);
        $owner->forceFill(['active_organization_id' => $org->id])->save();

        // Profile, seller profile, lot create and update
        $this->postJson('/api/user/profile', $forged + ['name' => 'Owner'])->assertOk();
        $this->postJson('/api/marketplace/seller/profile', $forged + ['company_name' => 'Forged Trading', 'business_type' => 'exporter']);
        $lotRes = $this->postJson('/api/marketplace/seller/lots', $forged + ['title' => 'Forged lot', 'origin_region' => 'Guji', 'processing_method' => 'washed', 'grade' => 'grade_1']);
        if ($id = $lotRes->json('data.id')) {
            $this->putJson("/api/marketplace/seller/lots/{$id}", $forged + ['title' => 'Forged lot 2']);
            $this->assertSame('claimed', CoffeeListing::findOrFail($id)->evidence_level);
            $this->assertNotSame('approved', CoffeeListing::findOrFail($id)->status);
        }

        // Document upload: always waits for review.
        $this->post('/api/marketplace/verification/documents', $forged + [
            'document_type' => 'trade_license', 'document_number' => 'TL-1', 'expires_at' => now()->addYear()->toDateString(),
            'pages' => [$this->realPdf()], 'current_password' => 'Owner123A',
        ], ['Accept' => 'application/json']);
        foreach (VerificationDocument::where('organization_id', $org->id)->get() as $doc) {
            $this->assertNotSame(VerificationDocument::STATUS_APPROVED, $doc->status);
        }

        $org->refresh();
        $this->assertSame(0, (int) $org->verification_tier);
        $this->assertFalse((bool) $org->is_verified);
        $this->assertNull($org->verification_valid_until);
        $profile = MarketplaceSellerProfile::where('user_id', $owner->id)->first();
        $this->assertNotSame('verified', $profile?->verification_status);
    }

    public function test_only_the_review_and_expiry_code_writes_trust_columns(): void
    {
        $offenders = [];
        $roots = [app_path(), database_path('seeders')];
        foreach ($roots as $root) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $rel = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
                if (in_array($rel, self::WRITERS, true)) {
                    continue;
                }
                foreach ($this->trustWrites(file_get_contents($file->getPathname())) as $hit) {
                    $offenders[] = "{$rel}: {$hit}";
                }
            }
        }

        $this->assertSame([], $offenders, "Trust columns written outside the review and expiry code:\n" . implode("\n", $offenders));

        // The scanner itself works: the real writers are found when not exempted.
        $this->assertNotEmpty($this->trustWrites(file_get_contents(app_path('Services/Verification/TierService.php'))));
        $this->assertNotEmpty($this->trustWrites(file_get_contents(app_path('Services/Lots/LotLevelService.php'))));
    }

    /** Writes: ->col = ..., and col keys inside create/update/fill/forceFill/insert/updateOrCreate([...]). */
    private function trustWrites(string $src): array
    {
        $cols = implode('|', self::TRUST_COLUMNS);
        $hits = [];
        if (preg_match_all('/->(' . $cols . ')\s*=(?!=)/', $src, $m)) {
            $hits = array_merge($hits, $m[0]);
        }
        $writeCall = '/(?:->|::)(?:create|update|fill|forceFill|insert|updateOrCreate|firstOrCreate|updateOrInsert|forceCreate)\s*\(/';
        $writeArgs = [];
        if (preg_match_all($writeCall, $src, $calls, PREG_OFFSET_CAPTURE)) {
            foreach ($calls[0] as [$text, $pos]) {
                $args = $this->balanced($src, $pos + strlen($text) - 1, '(', ')');
                $writeArgs[] = $args;
                if (preg_match('/[\'"](' . $cols . ')[\'"]\s*=>/', $args, $k)) {
                    $hits[] = trim($text) . " sets {$k[1]}";
                }
            }
        }
        // Indirect: $changes = ['verification_tier' => ...]; ...->forceFill($changes) or array_merge($changes, ...).
        if (preg_match_all('/\$(\w+)\s*=\s*\[/', $src, $arrays, PREG_OFFSET_CAPTURE)) {
            foreach ($arrays[1] as $i => [$var]) {
                $literal = $this->balanced($src, $arrays[0][$i][1] + strlen($arrays[0][$i][0]) - 1, '[', ']');
                if (!preg_match('/[\'"](' . $cols . ')[\'"]\s*=>/', $literal, $k)) {
                    continue;
                }
                foreach ($writeArgs as $args) {
                    if (preg_match('/\$' . preg_quote($var, '/') . '\b/', $args)) {
                        $hits[] = "\${$var} (written later) sets {$k[1]}";
                        break;
                    }
                }
            }
        }

        return $hits;
    }

    private function balanced(string $src, int $open, string $in, string $out): string
    {
        $depth = 0;
        for ($i = $open, $n = strlen($src); $i < $n; $i++) {
            if ($src[$i] === $in) {
                $depth++;
            } elseif ($src[$i] === $out && --$depth === 0) {
                return substr($src, $open, $i - $open + 1);
            }
        }

        return substr($src, $open);
    }

    public function test_the_marketplace_seeder_never_marks_sellers_verified_outside_local(): void
    {
        $before = MarketplaceSellerProfile::where('verification_status', 'verified')->count();
        $users = User::count();
        foreach (['production', 'testing', 'staging'] as $env) {
            $this->app['env'] = $env;
            app(MarketplaceSeeder::class)->run();
            $this->assertSame($before, MarketplaceSellerProfile::where('verification_status', 'verified')->count(), $env);
            $this->assertSame($users, User::count(), "{$env}: demo people created");
        }
    }

    public function test_the_production_seed_path_writes_no_trust_columns(): void
    {
        $src = file_get_contents(database_path('seeders/DatabaseSeeder.php'));
        preg_match_all('/(\w+Seeder)::class/', $src, $m);
        $this->assertNotEmpty($m[1]);
        foreach ($m[1] as $seeder) {
            $this->assertNotContains($seeder, ['MarketplaceSeeder', 'LocalDemoSellerSeeder'], 'DatabaseSeeder must not call demo seeders');
            $file = database_path("seeders/{$seeder}.php");
            if (is_file($file)) {
                $this->assertSame([], $this->trustWrites(file_get_contents($file)), "{$seeder} writes trust columns");
            }
        }
    }

    public function test_plans_and_payments_never_change_verification(): void
    {
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $plan = MarketplaceSubscriptionPlan::query()->first() ?? MarketplaceSubscriptionPlan::create([
            'name' => 'Exporter Pro', 'slug' => 'exporter-pro-' . Str::lower(Str::random(6)), 'price' => 1000, 'billing_cycle' => 'monthly', 'is_active' => true,
        ]);
        $sub = UserMarketplaceSubscription::create(['user_id' => $owner->id, 'plan_id' => $plan->id, 'status' => 'pending', 'starts_at' => now(), 'expires_at' => now()->addMonth()]);

        app(MarketplaceSubscriptionService::class)->activateSubscription($sub);

        $org->refresh();
        $this->assertSame(0, (int) $org->verification_tier);
        $this->assertFalse((bool) $org->is_verified);
        $this->assertFalse($org->hasActiveBadge());
    }

    public function test_audit_badges_reports_unbacked_tiers_and_flags_and_changes_nothing(): void
    {
        $owner = $this->makeUser();
        $forged = $this->makeOrganization($owner);
        // Simulates old data: a tier and legacy flags written without any reviewed documents.
        $forged->forceFill(['verification_tier' => 2, 'is_verified' => true, 'verified_roles' => ['exporter'], 'verification_valid_until' => now()->addYear()])->save();

        $real = $this->makeOrganization($this->makeUser());
        $this->approveBusiness($real);

        Artisan::call('verification:audit-badges', ['--dry-run' => true, '--limit' => 500]);
        $out = Artisan::output();

        $this->assertStringContainsString('Read-only report', $out);
        $this->assertMatchesRegularExpression('/tier_without_documents: [1-9]/', $out);
        $this->assertStringContainsString($forged->name, $out);
        $this->assertStringNotContainsString($real->name, $out);
        $this->assertSame(2, (int) $forged->fresh()->verification_tier, 'The report changed data.');
    }
}
