<?php

namespace Database\Seeders;

use App\Models\CoffeeListing;
use App\Models\MarketplaceSellerProfile;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\OrganizationVerification;
use App\Models\User;
use App\Services\Verification\VerificationReviewService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * LOCAL ONLY: one approved demo seller business with a few lots, so the marketplace is not
 * empty on a developer machine (lots stay hidden until the seller is approved).
 *
 *   php artisan db:seed --class=LocalDemoSellerSeeder
 *
 * Refuses to run unless APP_ENV=local. It is not called from DatabaseSeeder.
 * Safe to run twice: it updates the same records.
 */
class LocalDemoSellerSeeder extends Seeder
{
    public const SELLER_EMAIL = 'demo-seller@firalink.local';
    public const ORG_SLUG = 'demo-sidama-highland-exporters';

    public function run(): void
    {
        if (!app()->environment('local')) {
            $this->command?->error('LocalDemoSellerSeeder runs only when APP_ENV=local. Nothing was changed.');

            return;
        }

        $password = Str::password(16);
        $seller = User::where('email', self::SELLER_EMAIL)->first();
        if (!$seller) {
            $seller = new User();
            $seller->forceFill([
                'name' => 'Demo Seller',
                'email' => self::SELLER_EMAIL,
                'password' => Hash::make($password),
                'role' => 'instructor',
                'is_approved' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ])->save();
            $this->command?->info('Demo seller created: ' . self::SELLER_EMAIL . ' / ' . $password . ' (local only)');
        }

        $org = Organization::withTrashed()->where('slug', self::ORG_SLUG)->first()
            ?? Organization::create([
                'name' => 'Demo Sidama Highland Exporters',
                'slug' => self::ORG_SLUG,
                'business_role' => 'exporter',
                'business_type' => 'exporter',
                'entity_type' => 'private_limited_company',
                'country' => 'Ethiopia',
                'created_by' => $seller->id,
            ]);

        OrganizationMembership::updateOrCreate(
            ['organization_id' => $org->id, 'user_id' => $seller->id],
            ['role' => OrganizationMembership::ROLE_OWNER, 'is_active' => true, 'joined_at' => now()]
        );

        $profile = MarketplaceSellerProfile::updateOrCreate(
            ['user_id' => $seller->id],
            [
                'organization_id' => $org->id,
                'company_name' => $org->name,
                'company_slug' => self::ORG_SLUG,
                'business_type' => 'exporter',
                'city' => 'Hawassa',
                'country' => 'Ethiopia',
                'company_bio' => 'Demo exporter for local development. Not a real business.',
            ]
        );
        $profile->forceFill(['verification_status' => 'verified', 'verified_at' => now()])->save();

        // Approve through the review service (audited), the same path an admin uses.
        $reviewer = User::where('role', 'admin')->first() ?? $seller;
        $verification = OrganizationVerification::firstOrCreate([
            'organization_id' => $org->id,
            'target_role' => 'exporter',
        ]);
        app(VerificationReviewService::class)->approve($verification, $reviewer, 'Local demo data', now()->addYear());

        $lots = [
            ['Demo Sidama Bensa Washed G1', 'Sidama', 'washed', 'grade_1', 120, 4.85],
            ['Demo Guji Hambela Natural G1', 'Guji', 'natural', 'grade_1', 80, 5.20],
            ['Demo Yirgacheffe Kochere Washed G2', 'Yirgacheffe', 'washed', 'grade_2', 200, 4.10],
        ];
        foreach ($lots as [$title, $region, $process, $grade, $bags, $price]) {
            CoffeeListing::updateOrCreate(
                ['seller_id' => $seller->id, 'title' => $title],
                [
                    'organization_id' => $org->id,
                    'origin_region' => $region,
                    'processing_method' => $process,
                    'grade' => $grade,
                    'total_quantity_bags' => $bags,
                    'indicative_price_usd_per_lb' => $price,
                    'status' => 'active',
                ]
            );
        }

        $this->command?->info('Local demo seller ready: 1 approved business, ' . count($lots) . ' lots.');
    }
}
