<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Organization;
use App\Models\OrganizationEntitlementOverride;
use App\Models\OrganizationMembership;
use App\Models\Plan;
use App\Models\PlanEntitlement;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaasPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Feature Registry
        $features = [
            [
                'key' => 'seats',
                'label' => 'Team Seats',
                'type' => Feature::TYPE_LIMIT,
                'unit' => 'seats',
                'description' => 'Maximum active team members and collaborators in the organization.',
                'is_available' => true,
            ],
            [
                'key' => 'active_lots',
                'label' => 'Active Coffee Lots',
                'type' => Feature::TYPE_LIMIT,
                'unit' => 'lots',
                'description' => 'Maximum active green coffee lots published on the global marketplace.',
                'is_available' => true,
            ],
            [
                'key' => 'rfq_responses_per_month',
                'label' => 'Monthly RFQ Responses',
                'type' => Feature::TYPE_LIMIT,
                'unit' => 'quotes/mo',
                'description' => 'Monthly tender quotations and bids submitted to buyer RFQs.',
                'is_available' => true,
            ],
            [
                'key' => 'active_deal_rooms',
                'label' => 'Active Deal Rooms',
                'type' => Feature::TYPE_LIMIT,
                'unit' => 'deal rooms',
                'description' => 'Simultaneous active contract negotiation and execution deal rooms.',
                'is_available' => true,
            ],
            [
                'key' => 'storage_gb',
                'label' => 'Document & Evidence Storage',
                'type' => Feature::TYPE_LIMIT,
                'unit' => 'GB',
                'description' => 'Storage quota for cupping lab reports, export licenses, and lot media.',
                'is_available' => true,
            ],
            [
                'key' => 'toolkit_level',
                'label' => 'Coffee Export Toolkit',
                'type' => Feature::TYPE_TIER,
                'unit' => 'tier',
                'description' => 'FOB price calculator, readiness scoring, and contract generation (basic, full, custom).',
                'is_available' => true,
            ],
            [
                'key' => 'contract_templates',
                'label' => 'Export Contract Templates',
                'type' => Feature::TYPE_BOOLEAN,
                'unit' => null,
                'description' => 'Official ECTA & ICO standard export contract templates and formal offer sheets.',
                'is_available' => true,
            ],
            [
                'key' => 'esignature',
                'label' => 'Digital E-Signature',
                'type' => Feature::TYPE_BOOLEAN,
                'unit' => null,
                'description' => 'Legally binding cryptographic electronic contract signatures.',
                'is_available' => false, // Feature-flagged as Coming Soon
            ],
            [
                'key' => 'alerts_channels',
                'label' => 'Market & Trade Alerts',
                'type' => Feature::TYPE_TIER,
                'unit' => 'channels',
                'description' => 'Channels for daily minimum export price alerts and tender notifications.',
                'is_available' => true,
            ],
            [
                'key' => 'public_profile_style',
                'label' => 'Public Profile Customization',
                'type' => Feature::TYPE_TIER,
                'unit' => 'style',
                'description' => 'Standard institutional directory listing vs. custom branded exporter microsite.',
                'is_available' => true,
            ],
            [
                'key' => 'analytics_level',
                'label' => 'Trade & Pricing Analytics',
                'type' => Feature::TYPE_TIER,
                'unit' => 'tier',
                'description' => 'Market price trends, RFQ conversion rates, and team export performance analytics.',
                'is_available' => true,
            ],
            [
                'key' => 'bulk_csv_lot_upload',
                'label' => 'Bulk CSV Lot Upload',
                'type' => Feature::TYPE_BOOLEAN,
                'unit' => null,
                'description' => 'Batch spreadsheet upload of hundreds of washing station lots.',
                'is_available' => true,
            ],
            [
                'key' => 'audit_log_export',
                'label' => 'Audit Trail CSV Export',
                'type' => Feature::TYPE_BOOLEAN,
                'unit' => null,
                'description' => 'Export organization security, contract, and verification event history for compliance.',
                'is_available' => true,
            ],
            [
                'key' => 'api_access',
                'label' => 'Developer API & Webhooks',
                'type' => Feature::TYPE_BOOLEAN,
                'unit' => null,
                'description' => 'Programmatic REST API tokens and webhook events for ERP/logistics integration.',
                'is_available' => true,
            ],
            [
                'key' => 'support_level',
                'label' => 'Customer Support SLA',
                'type' => Feature::TYPE_TIER,
                'unit' => 'sla',
                'description' => 'Help Center articles, email support, or dedicated priority trade specialist.',
                'is_available' => true,
            ],
            [
                'key' => 'academy_access',
                'label' => 'Academy LMS Trade Training',
                'type' => Feature::TYPE_TIER,
                'unit' => 'access',
                'description' => 'Access to export masterclasses, Q-Grader prep, and team learning seats.',
                'is_available' => true,
            ],
        ];

        foreach ($features as $f) {
            Feature::updateOrCreate(['key' => $f['key']], $f);
        }

        // 2. Seed Plans
        $plans = [
            [
                'key' => Plan::KEY_STARTER,
                'name' => 'Exporter Starter',
                'description' => 'Essential export presence for individual washing stations and boutique specialty growers.',
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 1,
                'trial_days' => 0,
                'prices' => [
                    ['currency' => 'USD', 'billing_period' => 'monthly', 'amount' => 0.00],
                    ['currency' => 'USD', 'billing_period' => 'annual', 'amount' => 0.00],
                    ['currency' => 'ETB', 'billing_period' => 'monthly', 'amount' => 0.00],
                    ['currency' => 'ETB', 'billing_period' => 'annual', 'amount' => 0.00],
                ],
                'entitlements' => [
                    'seats' => '2',
                    'active_lots' => '3',
                    'rfq_responses_per_month' => '5',
                    'active_deal_rooms' => '2',
                    'storage_gb' => '1',
                    'toolkit_level' => 'basic',
                    'contract_templates' => '0',
                    'esignature' => '0',
                    'alerts_channels' => 'in_app+sms',
                    'public_profile_style' => 'standard',
                    'analytics_level' => 'none',
                    'bulk_csv_lot_upload' => '0',
                    'audit_log_export' => '0',
                    'api_access' => '0',
                    'support_level' => 'help_center',
                    'academy_access' => 'free_courses',
                ],
            ],
            [
                'key' => Plan::KEY_GROWTH,
                'name' => 'Pro Exporter (Growth)',
                'description' => 'Ideal for active commercial exporters, washing station networks, and regional cooperatives.',
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 2,
                'trial_days' => 14,
                'prices' => [
                    ['currency' => 'USD', 'billing_period' => 'monthly', 'amount' => 29.00],
                    ['currency' => 'USD', 'billing_period' => 'annual', 'amount' => 290.00], // 2 months free
                    ['currency' => 'ETB', 'billing_period' => 'monthly', 'amount' => 3500.00],
                    ['currency' => 'ETB', 'billing_period' => 'annual', 'amount' => 35000.00], // 2 months free
                ],
                'entitlements' => [
                    'seats' => '5',
                    'active_lots' => '15',
                    'rfq_responses_per_month' => '30',
                    'active_deal_rooms' => '10',
                    'storage_gb' => '10',
                    'toolkit_level' => 'full',
                    'contract_templates' => '1',
                    'esignature' => '1',
                    'alerts_channels' => 'in_app+sms+telegram+whatsapp',
                    'public_profile_style' => 'standard',
                    'analytics_level' => 'basic',
                    'bulk_csv_lot_upload' => '0',
                    'audit_log_export' => '0',
                    'api_access' => '0',
                    'support_level' => 'email',
                    'academy_access' => 'export101_path',
                ],
            ],
            [
                'key' => Plan::KEY_BUSINESS,
                'name' => 'Business',
                'description' => 'Uncapped volume, API automation, and dedicated trade desk for high-volume enterprise exporters.',
                'is_public' => true,
                'is_active' => true,
                'sort_order' => 3,
                'trial_days' => 0,
                'prices' => [
                    ['currency' => 'USD', 'billing_period' => 'monthly', 'amount' => 99.00],
                    ['currency' => 'USD', 'billing_period' => 'annual', 'amount' => 990.00], // 2 months free
                    ['currency' => 'ETB', 'billing_period' => 'monthly', 'amount' => 12000.00],
                    ['currency' => 'ETB', 'billing_period' => 'annual', 'amount' => 120000.00], // 2 months free
                ],
                'entitlements' => [
                    'seats' => '15',
                    'active_lots' => '-1', // unlimited
                    'rfq_responses_per_month' => '-1', // unlimited
                    'active_deal_rooms' => '-1', // unlimited
                    'storage_gb' => '100',
                    'toolkit_level' => 'custom',
                    'contract_templates' => '1',
                    'esignature' => '1',
                    'alerts_channels' => 'team_digest',
                    'public_profile_style' => 'branded',
                    'analytics_level' => 'team',
                    'bulk_csv_lot_upload' => '1',
                    'audit_log_export' => '1',
                    'api_access' => '1',
                    'support_level' => 'priority',
                    'academy_access' => 'team_seats',
                ],
            ],
        ];

        foreach ($plans as $pData) {
            $prices = $pData['prices'];
            $entitlements = $pData['entitlements'];
            unset($pData['prices'], $pData['entitlements']);

            $plan = Plan::updateOrCreate(['key' => $pData['key']], $pData);

            // Prices
            foreach ($prices as $pr) {
                PlanPrice::updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'currency' => $pr['currency'],
                        'billing_period' => $pr['billing_period'],
                    ],
                    [
                        'amount' => $pr['amount'],
                        'is_active' => true,
                        'effective_from' => now(),
                    ]
                );
            }

            // Entitlements
            foreach ($entitlements as $featureKey => $val) {
                PlanEntitlement::updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'feature_key' => $featureKey,
                    ],
                    [
                        'value' => (string) $val,
                    ]
                );
            }
        }

        // 3. Demo Organizations on each plan + Founding Member Override
        $this->seedDemoOrganizations();
    }

    protected function seedDemoOrganizations(): void
    {
        $starterPlan = Plan::where('key', Plan::KEY_STARTER)->first();
        $growthPlan = Plan::where('key', Plan::KEY_GROWTH)->first();
        $businessPlan = Plan::where('key', Plan::KEY_BUSINESS)->first();

        // 1. Starter Demo Org
        $starterOrg = Organization::firstOrCreate(
            ['slug' => 'bensa-coop-demo'],
            [
                'name' => 'Bensa Highland Coffee Cooperative',
                'business_type' => 'cooperative',
                'status' => 'active',
                'country' => 'Ethiopia',
                'billing_email' => 'bensa@firalink-demo.et',
            ]
        );
        Subscription::updateOrCreate(
            ['organization_id' => $starterOrg->id],
            [
                'plan_id' => $starterPlan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'billing_period' => 'monthly',
                'current_period_start' => now()->startOfMonth(),
                'current_period_end' => now()->addMonth(),
                'provider' => 'system',
            ]
        );

        // 2. Growth Demo Org
        $growthOrg = Organization::firstOrCreate(
            ['slug' => 'sidama-estate-demo'],
            [
                'name' => 'Sidama Crown Coffee Exporters PLC',
                'business_type' => 'exporter',
                'status' => 'active',
                'country' => 'Ethiopia',
                'billing_email' => 'sidama@firalink-demo.et',
            ]
        );
        Subscription::updateOrCreate(
            ['organization_id' => $growthOrg->id],
            [
                'plan_id' => $growthPlan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'billing_period' => 'monthly',
                'current_period_start' => now()->startOfMonth(),
                'current_period_end' => now()->addMonth(),
                'provider' => 'chapa',
                'provider_ref' => 'CHAPA-GROWTH-DEMO-001',
            ]
        );

        // 3. Business Demo Org
        $businessOrg = Organization::firstOrCreate(
            ['slug' => 'oromia-union-demo'],
            [
                'name' => 'Oromia Coffee Farmers Union Enterprise',
                'business_type' => 'union',
                'status' => 'active',
                'country' => 'Ethiopia',
                'billing_email' => 'oromia-union@firalink-demo.et',
            ]
        );
        Subscription::updateOrCreate(
            ['organization_id' => $businessOrg->id],
            [
                'plan_id' => $businessPlan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'billing_period' => 'annual',
                'current_period_start' => now()->startOfYear(),
                'current_period_end' => now()->addYear(),
                'provider' => 'chapa',
                'provider_ref' => 'CHAPA-BIZ-DEMO-002',
            ]
        );

        // 4. Founding Member Offer Demo (Growth Plan with 6-month Free Override + 50 lots bonus)
        $foundingOrg = Organization::firstOrCreate(
            ['slug' => 'yirgacheffe-founding-demo'],
            [
                'name' => 'Yirgacheffe Heritage Roasters & Exporters',
                'business_type' => 'exporter',
                'status' => 'active',
                'country' => 'Ethiopia',
                'billing_email' => 'yirgacheffe@firalink-demo.et',
            ]
        );
        $foundingSub = Subscription::updateOrCreate(
            ['organization_id' => $foundingOrg->id],
            [
                'plan_id' => $growthPlan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'billing_period' => 'annual',
                'current_period_start' => now(),
                'current_period_end' => now()->addMonths(6),
                'provider' => 'founding_offer',
                'provider_ref' => 'FOUNDING-2026-ETHIOPIA',
            ]
        );

        // Apply an audited override: extra active lots (50 lots instead of 15) expiring in 6 months
        OrganizationEntitlementOverride::updateOrCreate(
            [
                'organization_id' => $foundingOrg->id,
                'feature_key' => 'active_lots',
            ],
            [
                'value' => '50',
                'reason' => 'Founding Exporter Cohort 2026: 6 months complimentary tier upgrade + 50 lots',
                'expires_at' => now()->addMonths(6),
            ]
        );

        SubscriptionEvent::create([
            'subscription_id' => $foundingSub->id,
            'organization_id' => $foundingOrg->id,
            'event_type' => SubscriptionEvent::EVENT_OVERRIDE_APPLIED,
            'to_plan_id' => $growthPlan->id,
            'metadata' => [
                'promo' => 'Founding Exporter Cohort 2026',
                'duration_months' => 6,
                'bonus_lots' => 50,
            ],
            'created_at' => now(),
        ]);
    }
}
