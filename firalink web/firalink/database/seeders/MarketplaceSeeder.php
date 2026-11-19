<?php

namespace Database\Seeders;

use App\Models\CoffeeListing;
use App\Models\CoffeeListingMedia;
use App\Models\MarketplaceInquiry;
use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use App\Models\MarketplaceSellerProfile;
use App\Models\MarketplaceSubscriptionPlan;
use App\Models\User;
use App\Models\UserMarketplaceSubscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MarketplaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ==========================================
        // 1. SUBSCRIPTION PLANS & PACKAGES (Real Tiers)
        // ==========================================
        $plans = [
            [
                'name' => 'Exporter Starter',
                'slug' => 'seller_starter',
                'role_target' => 'seller',
                'description' => 'Ideal for newly licensed Ethiopian coffee exporters and specialty washing stations entering direct export trade.',
                'price_etb' => 1500.00,
                'price_usd' => 15.00,
                'billing_cycle' => 'monthly',
                'active_listings_limit' => 3,
                'rfq_response_limit' => 10,
                'featured_slots' => 1,
                'verified_badge_included' => false, // verification is never part of a plan
                'analytics_access' => false,
                'priority_support' => false,
                'features' => [
                    'Up to 3 active green coffee lots',
                    'Respond to 10 buyer RFQs / month',
                    'SCA cupping form & lab certificate uploads',
                    'Direct buyer sample parcel requests',
                    'Standard email & portal support',
                ],
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro Exporter',
                'slug' => 'seller_pro',
                'role_target' => 'seller',
                'description' => 'The premier package for commercial exporters, estate growers, and regional specialty cooperatives.',
                'price_etb' => 4500.00,
                'price_usd' => 45.00,
                'billing_cycle' => 'monthly',
                'active_listings_limit' => 15,
                'rfq_response_limit' => 0, // unlimited
                'featured_slots' => 5,
                'verified_badge_included' => false, // verification is never part of a plan
                'analytics_access' => true,
                'priority_support' => true,
                'features' => [
                    'Up to 15 active green coffee lots',
                    'Unlimited buyer RFQ tender bids',
                    '5 Featured Lot slots on Homepage & App',
                    'Sensory radar chart generator',
                    'Daily ECTA minimum export price benchmark alerts',
                    'Direct buyer contact disclosure',
                    'Priority WhatsApp & direct phone assistance',
                ],
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise Global Trader',
                'slug' => 'seller_enterprise',
                'role_target' => 'seller',
                'description' => 'Built for multi-origin exporter unions, commercial estates, and multinational trading conglomerates.',
                'price_etb' => 12000.00,
                'price_usd' => 120.00,
                'billing_cycle' => 'monthly',
                'active_listings_limit' => 0, // unlimited
                'rfq_response_limit' => 0,
                'featured_slots' => 20,
                'verified_badge_included' => false, // verification is never part of a plan
                'analytics_access' => true,
                'priority_support' => true,
                'features' => [
                    'Unlimited active green coffee lots',
                    'Unlimited RFQ submissions & instant bidding',
                    'Top-tier priority search & category ranking',
                    'Dedicated Fira Link trade broker support',
                    'Custom digital showroom & branding',
                    'Export documentation assistant (ICO/ECX/ECTA)',
                    'Multi-agent staff accounts & team permissions',
                    'Direct container booking & Djibouti logistics support',
                ],
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Buyer (Basic)',
                'slug' => 'buyer_verified',
                'role_target' => 'buyer',
                'description' => 'Free permanent access for international coffee roasters, green coffee buyers, and importers worldwide.',
                'price_etb' => 0.00,
                'price_usd' => 0.00,
                'billing_cycle' => 'monthly',
                'active_listings_limit' => 0,
                'rfq_response_limit' => 0,
                'featured_slots' => 0,
                'verified_badge_included' => false, // verification is never part of a plan
                'analytics_access' => true,
                'priority_support' => false,
                'features' => [
                    'Publish unlimited RFQ procurement tenders',
                    'Request green coffee sample parcels',
                    'Direct contact with vetted Ethiopian exporters',
                    'Daily official minimum export price tracker',
                    'Pre-shipment cupping report verification',
                    'Standard buyer trade dashboard',
                ],
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Buyer Concierge & Trade Guarantee',
                'slug' => 'buyer_concierge',
                'role_target' => 'buyer',
                'description' => 'Premium procurement support with third-party cupping inspection and on-the-ground Ethiopian export oversight.',
                'price_etb' => 3500.00,
                'price_usd' => 35.00,
                'billing_cycle' => 'monthly',
                'active_listings_limit' => 0,
                'rfq_response_limit' => 0,
                'featured_slots' => 0,
                'verified_badge_included' => false, // verification is never part of a plan
                'analytics_access' => true,
                'priority_support' => true,
                'features' => [
                    'All Buyer (Basic) features included',
                    'Dedicated on-the-ground Q-Grader pre-shipment cupping',
                    'Physical container stuffing inspection in Mojo/Addis',
                    'Priority express sample dispatch (DHL/FedEx)',
                    'Trade dispute resolution & mediation',
                    'Escrow milestone payment protection',
                    'Early private preview of new harvest micro-lots',
                ],
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($plans as $planData) {
            MarketplaceSubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        // ==========================================
        // 2. VERIFIED ETHIOPIAN EXPORTERS (Multiple Origins)
        // ==========================================
        $sellersData = [
            [
                'email' => 'exporter@fira-link.com',
                'name' => 'Bensa Highland Specialty Exporters',
                'phone' => '+251911223344',
                'company_name' => 'Bensa Highland Specialty Coffee PLC',
                'company_slug' => 'bensa-highland-specialty',
                'business_type' => 'exporter',
                'export_license_no' => 'ECTA-EXP-2024-8842',
                'tin_number' => '0098412847',
                'origin_regions' => ['Sidama', 'Guji', 'Yirgacheffe'],
                'washing_stations' => ['Kokose Washing Station', 'Hamasho Wet Mill', 'Bombe Fermentation Station'],
                'washing_station_count' => 3,
                'annual_export_volume_mt' => 450.00,
                'certifications' => ['Organic EU/NOP', 'Fairtrade FLO-ID 4120', 'Rainforest Alliance'],
                'company_bio' => 'Bensa Highland is a premier Ethiopian specialty coffee producer and exporter rooted in the high-altitude cloud forests of Bensa, Sidama (2,050m - 2,250m). We process award-winning washed, natural, and anaerobic micro-lots.',
                'website_url' => 'https://bensahighland.com',
                'contact_email' => 'trade@bensahighland.com',
                'contact_phone' => '+251911223344',
                'address' => 'Bole Subcity, House 412, Addis Ababa',
                'city' => 'Addis Ababa',
                'country' => 'Ethiopia',
                'plan_slug' => 'seller_pro',
            ],
            [
                'email' => 'yirga.trade@fira-link.com',
                'name' => 'Yirgacheffe Mountain Gate Exporters',
                'phone' => '+251922334455',
                'company_name' => 'Yirgacheffe Mountain Gate Exporters PLC',
                'company_slug' => 'yirgacheffe-mountain-gate',
                'business_type' => 'exporter',
                'export_license_no' => 'ECTA-EXP-2023-7190',
                'tin_number' => '0087654321',
                'origin_regions' => ['Yirgacheffe', 'Gedeo'],
                'washing_stations' => ['Kochere Central Mill', 'Chelchele Eco-Pulper', 'Aricha High Bed Station'],
                'washing_station_count' => 4,
                'annual_export_volume_mt' => 600.00,
                'certifications' => ['Rainforest Alliance', 'Organic EU'],
                'company_bio' => 'Dedicated to authentic floral and citrus Yirgacheffe coffees. Working with over 1,200 Gedeo smallholders across Kochere, Chelchele, and Aricha micro-regions.',
                'website_url' => 'https://yirgamountain.com',
                'contact_email' => 'export@yirgamountain.com',
                'contact_phone' => '+251922334455',
                'address' => 'Kirkos Subcity, Meskel Flower, Addis Ababa',
                'city' => 'Addis Ababa',
                'country' => 'Ethiopia',
                'plan_slug' => 'seller_enterprise',
            ],
            [
                'email' => 'guji.union@fira-link.com',
                'name' => 'Oromia Guji Highlands Specialty Union',
                'phone' => '+251933445566',
                'company_name' => 'Oromia Guji Highlands Coffee Union',
                'company_slug' => 'oromia-guji-highlands',
                'business_type' => 'cooperative_union',
                'export_license_no' => 'ECTA-EXP-2024-9512',
                'tin_number' => '0076543210',
                'origin_regions' => ['Guji', 'Borena'],
                'washing_stations' => ['Dimtu Drying Station', 'Uraga Gomoro Wet Mill', 'Shakiso Agro-Forest Mill'],
                'washing_station_count' => 5,
                'annual_export_volume_mt' => 850.00,
                'certifications' => ['Fairtrade', 'Organic NOP', 'Bird Friendly'],
                'company_bio' => 'Specializing in intense, wild berry and stone fruit natural coffees from Shakiso and Hambela Wamena. Empowering female-led agroforestry smallholder families.',
                'website_url' => 'https://gujicoffeeunion.com',
                'contact_email' => 'trade@gujicoffeeunion.com',
                'contact_phone' => '+251933445566',
                'address' => 'Nifas Silk Lafto, Addis Ababa',
                'city' => 'Addis Ababa',
                'country' => 'Ethiopia',
                'plan_slug' => 'seller_enterprise',
            ],
            [
                'email' => 'kaffa.origin@fira-link.com',
                'name' => 'Kaffa & Limu Biosphere Coffee PLC',
                'phone' => '+251944556677',
                'company_name' => 'Kaffa & Limu Forest Biosphere PLC',
                'company_slug' => 'kaffa-limu-biosphere',
                'business_type' => 'exporter',
                'export_license_no' => 'ECTA-EXP-2022-6304',
                'tin_number' => '0065432109',
                'origin_regions' => ['Kaffa', 'Limu', 'Jimma'],
                'washing_stations' => ['Bonga Wild Station', 'Gomma Wet Mill', 'Agaro Cooperative Station'],
                'washing_station_count' => 3,
                'annual_export_volume_mt' => 380.00,
                'certifications' => ['Rainforest Alliance', 'UNESCO Biosphere Certified'],
                'company_bio' => 'Sourcing wild Arabica from the birthplace of coffee in Kaffa. Canopy shade-grown under ancient indigenous trees with crisp acidity and rich, layered complexity.',
                'website_url' => 'https://kaffabiosphere.com',
                'contact_email' => 'sales@kaffabiosphere.com',
                'contact_phone' => '+251944556677',
                'address' => 'Yeka Subcity, Addis Ababa',
                'city' => 'Addis Ababa',
                'country' => 'Ethiopia',
                'plan_slug' => 'seller_starter',
            ],
            [
                'email' => 'harrar.legacy@fira-link.com',
                'name' => 'Harrar Ancient Sun Coffee Traders',
                'phone' => '+251955667788',
                'company_name' => 'Harrar Ancient Sun Estates & Millers',
                'company_slug' => 'harrar-ancient-sun',
                'business_type' => 'exporter',
                'export_license_no' => 'ECTA-EXP-2024-8119',
                'tin_number' => '0054321098',
                'origin_regions' => ['Harrar'],
                'washing_stations' => ['Mesela Sun-Drying Compound', 'Dire Dawa Modern Milling Facility'],
                'washing_station_count' => 2,
                'annual_export_volume_mt' => 320.00,
                'certifications' => ['Geographical Indication (GI) Certified'],
                'company_bio' => 'Exporting world-renowned Harrar Longberry natural coffees with signature blueberry, dark chocolate, and spicy winey profiles from Eastern Ethiopia.',
                'website_url' => 'https://harrarancientsun.com',
                'contact_email' => 'info@harrarancientsun.com',
                'contact_phone' => '+251955667788',
                'address' => 'Dire Dawa Highway, Harar / Addis Ababa',
                'city' => 'Addis Ababa',
                'country' => 'Ethiopia',
                'plan_slug' => 'seller_starter',
            ],
        ];

        $sellerUsers = [];
        foreach ($sellersData as $sData) {
            $user = User::firstOrCreate(
                ['email' => $sData['email']],
                [
                    'name' => $sData['name'],
                    'password' => Hash::make('Password123!'),
                    'role' => 'instructor',
                    'is_approved' => true,
                    'phone' => $sData['phone'],
                    'bio' => $sData['company_bio'],
                ]
            );

            $profile = MarketplaceSellerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $sData['company_name'],
                    'company_slug' => $sData['company_slug'],
                    'business_type' => $sData['business_type'],
                    'export_license_no' => $sData['export_license_no'],
                    'tin_number' => $sData['tin_number'],
                    'origin_regions' => $sData['origin_regions'],
                    'washing_stations' => $sData['washing_stations'],
                    'washing_station_count' => $sData['washing_station_count'],
                    'annual_export_volume_mt' => $sData['annual_export_volume_mt'],
                    'certifications' => $sData['certifications'],
                    'company_bio' => $sData['company_bio'],
                    'website_url' => $sData['website_url'],
                    'contact_email' => $sData['contact_email'],
                    'contact_phone' => $sData['contact_phone'],
                    'address' => $sData['address'],
                    'city' => $sData['city'],
                    'country' => $sData['country'],
                ]
            );
            // Verification fields are not mass-assignable (slice 2). Demo data only; lots still stay
            // hidden from buyers until the seller's business has an approved seller verification.
            $profile->forceFill([
                'verification_status' => 'verified',
                'verified_at' => now(),
                'verification_notes' => 'ECTA license and Ethiopian commercial registration verified.',
            ])->save();

            $plan = MarketplaceSubscriptionPlan::where('slug', $sData['plan_slug'])->first();
            if ($plan) {
                UserMarketplaceSubscription::updateOrCreate(
                    ['user_id' => $user->id, 'plan_id' => $plan->id],
                    [
                        'status' => 'active',
                        'starts_at' => now(),
                        'expires_at' => now()->addYear(),
                        'auto_renew' => true,
                        'notes' => 'Verified Ethiopian exporter tier.',
                    ]
                );
            }

            $sellerUsers[$sData['company_slug']] = $user;
        }

        // ==========================================
        // 3. GREEN COFFEE LISTINGS (Authentic Regional Lots)
        // ==========================================
        $listings = [
            [
                'seller_slug' => 'bensa-highland-specialty',
                'title' => 'Sidama Bensa Bombe Anaerobic Micro-Lot G1',
                'origin_region' => 'Sidama',
                'sub_region' => 'Bensa - Bombe Village',
                'washing_station' => 'Bombe Fermentation Station',
                'coffee_type' => 'arabica',
                'variety' => '74112 & 74110',
                'processing_method' => 'anaerobic',
                'grade' => 'grade_1',
                'screen_size' => 'Screen 15/16',
                'cupping_score' => 89.75,
                'moisture_content' => 10.40,
                'water_activity' => 0.58,
                'crop_year' => '2025/2026',
                'altitude_min' => 2050,
                'altitude_max' => 2200,
                'total_quantity_bags' => 150,
                'available_quantity_bags' => 120,
                'minimum_order_bags' => 10,
                'bag_type' => 'jute_with_grainpro',
                'bag_weight_kg' => 60.00,
                'indicative_price_usd_per_lb' => 4.85,
                'indicative_price_usd_per_mt' => 10692.00,
                'incoterm' => 'FOB Djibouti',
                'port_of_loading' => 'Djibouti',
                'sample_available' => true,
                'sample_cost_usd' => 0.00,
                'tasting_notes' => ['Wild Strawberry', 'Papaya', 'Jasmine Blossom', 'Dark Cocoa', 'Champagne Acidity'],
                'sensory_attributes' => [
                    'aroma' => 9.2,
                    'acidity' => 9.0,
                    'body' => 8.8,
                    'flavor' => 9.3,
                    'aftertaste' => 8.9,
                    'balance' => 9.1,
                ],
                'description' => 'Flagship 2025/2026 harvest anaerobic natural lot from Bombe village. Ripe cherry harvested at 22-24 Brix, fermented sealed in airtight barrels under controlled water immersion for 96 hours before slow raised African bed drying.',
                'status' => 'active',
                'is_featured' => true,
                'image_url' => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?q=80&w=1200&auto=format&fit=crop',
            ],
            [
                'seller_slug' => 'yirgacheffe-mountain-gate',
                'title' => 'Yirgacheffe Kochere Grade 1 Washed Crown Lot',
                'origin_region' => 'Yirgacheffe',
                'sub_region' => 'Kochere',
                'washing_station' => 'Borena Wet Mill',
                'coffee_type' => 'arabica',
                'variety' => 'Kurume & Dega Heirloom',
                'processing_method' => 'washed',
                'grade' => 'grade_1',
                'screen_size' => 'Screen 14+',
                'cupping_score' => 88.50,
                'moisture_content' => 10.20,
                'water_activity' => 0.55,
                'crop_year' => '2025/2026',
                'altitude_min' => 1950,
                'altitude_max' => 2100,
                'total_quantity_bags' => 300,
                'available_quantity_bags' => 250,
                'minimum_order_bags' => 20,
                'bag_type' => 'jute_with_grainpro',
                'bag_weight_kg' => 60.00,
                'indicative_price_usd_per_lb' => 3.95,
                'indicative_price_usd_per_mt' => 8708.00,
                'incoterm' => 'FOB Djibouti',
                'port_of_loading' => 'Djibouti',
                'sample_available' => true,
                'sample_cost_usd' => 0.00,
                'tasting_notes' => ['Bergamot', 'Earl Grey Tea', 'Lemon Verbena', 'White Peach', 'Silky Honey'],
                'sensory_attributes' => [
                    'aroma' => 9.0,
                    'acidity' => 9.1,
                    'body' => 8.4,
                    'flavor' => 8.9,
                    'aftertaste' => 8.8,
                    'balance' => 9.0,
                ],
                'description' => 'Classic textbook Yirgacheffe washed lot with pristine transparency. Depulped within 6 hours of picking, dry-fermented for 36 hours, spring-washed, and sun-dried on parabolic mesh beds for 14 days.',
                'status' => 'active',
                'is_featured' => true,
                'image_url' => 'https://images.unsplash.com/photo-1498804103079-a6351b050096?q=80&w=1200&auto=format&fit=crop',
            ],
            [
                'seller_slug' => 'oromia-guji-highlands',
                'title' => 'Guji Hambela Wamena Natural Grade 1',
                'origin_region' => 'Guji',
                'sub_region' => 'Hambela Wamena',
                'washing_station' => 'Dimtu Drying Station',
                'coffee_type' => 'arabica',
                'variety' => '74110 Selection',
                'processing_method' => 'natural',
                'grade' => 'grade_1',
                'screen_size' => 'Screen 15+',
                'cupping_score' => 88.75,
                'moisture_content' => 10.50,
                'water_activity' => 0.57,
                'crop_year' => '2025/2026',
                'altitude_min' => 2100,
                'altitude_max' => 2280,
                'total_quantity_bags' => 200,
                'available_quantity_bags' => 180,
                'minimum_order_bags' => 15,
                'bag_type' => 'jute_with_grainpro',
                'bag_weight_kg' => 60.00,
                'indicative_price_usd_per_lb' => 4.20,
                'indicative_price_usd_per_mt' => 9259.00,
                'incoterm' => 'FOB Djibouti',
                'port_of_loading' => 'Djibouti',
                'sample_available' => true,
                'sample_cost_usd' => 0.00,
                'tasting_notes' => ['Blueberry Jam', 'Black Currant', 'Dark Chocolate', 'Lavender', 'Syrupy Mouthfeel'],
                'sensory_attributes' => [
                    'aroma' => 8.9,
                    'acidity' => 8.7,
                    'body' => 9.2,
                    'flavor' => 9.0,
                    'aftertaste' => 8.8,
                    'balance' => 8.9,
                ],
                'description' => 'Guji specialty at its best. Sourced exclusively from smallholder agroforestry garden plots in Hambela Wamena. Intense berry fruit sweetness with deep cocoa body.',
                'status' => 'active',
                'is_featured' => true,
                'image_url' => 'https://images.unsplash.com/photo-1511537190424-bbbab87ac5eb?q=80&w=1200&auto=format&fit=crop',
            ],
            [
                'seller_slug' => 'yirgacheffe-mountain-gate',
                'title' => 'Yirgacheffe Chelchele Honey Process G1',
                'origin_region' => 'Yirgacheffe',
                'sub_region' => 'Chelchele, Gedeo',
                'washing_station' => 'Chelchele Eco-Pulper',
                'coffee_type' => 'arabica',
                'variety' => 'Dega & Wolisho',
                'processing_method' => 'honey',
                'grade' => 'grade_1',
                'screen_size' => 'Screen 15/16',
                'cupping_score' => 88.25,
                'moisture_content' => 10.30,
                'water_activity' => 0.56,
                'crop_year' => '2025/2026',
                'altitude_min' => 1980,
                'altitude_max' => 2120,
                'total_quantity_bags' => 120,
                'available_quantity_bags' => 95,
                'minimum_order_bags' => 10,
                'bag_type' => 'jute_with_grainpro',
                'bag_weight_kg' => 60.00,
                'indicative_price_usd_per_lb' => 4.10,
                'indicative_price_usd_per_mt' => 9038.00,
                'incoterm' => 'FOB Djibouti',
                'port_of_loading' => 'Djibouti',
                'sample_available' => true,
                'sample_cost_usd' => 0.00,
                'tasting_notes' => ['Candied Orange', 'Wild Floral Honey', 'Red Grape', 'Silky Sweet'],
                'sensory_attributes' => [
                    'aroma' => 8.9,
                    'acidity' => 8.8,
                    'body' => 8.9,
                    'flavor' => 9.0,
                    'aftertaste' => 8.7,
                    'balance' => 8.9,
                ],
                'description' => 'Rare yellow honey preparation from Chelchele. Mucilage retained on parchment during slow shade-drying yields remarkable brown sugar sweetness and creamy body.',
                'status' => 'active',
                'is_featured' => false,
                'image_url' => 'https://images.unsplash.com/photo-1447933601403-0c6688de566e?q=80&w=1200&auto=format&fit=crop',
            ],
            [
                'seller_slug' => 'oromia-guji-highlands',
                'title' => 'Guji Uraga Gomoro Extended Washed G1',
                'origin_region' => 'Guji',
                'sub_region' => 'Uraga - Gomoro',
                'washing_station' => 'Uraga Gomoro Wet Mill',
                'coffee_type' => 'arabica',
                'variety' => '74112 Indigenous',
                'processing_method' => 'washed',
                'grade' => 'grade_1',
                'screen_size' => 'Screen 15+',
                'cupping_score' => 89.25,
                'moisture_content' => 10.10,
                'water_activity' => 0.54,
                'crop_year' => '2025/2026',
                'altitude_min' => 2150,
                'altitude_max' => 2300,
                'total_quantity_bags' => 180,
                'available_quantity_bags' => 150,
                'minimum_order_bags' => 15,
                'bag_type' => 'jute_with_grainpro',
                'bag_weight_kg' => 60.00,
                'indicative_price_usd_per_lb' => 4.45,
                'indicative_price_usd_per_mt' => 9810.00,
                'incoterm' => 'FOB Djibouti',
                'port_of_loading' => 'Djibouti',
                'sample_available' => true,
                'sample_cost_usd' => 0.00,
                'tasting_notes' => ['Nectarine', 'Lemongrass', 'Jasmine Perfume', 'Crisp Lime', 'Cane Sugar'],
                'sensory_attributes' => [
                    'aroma' => 9.1,
                    'acidity' => 9.2,
                    'body' => 8.6,
                    'flavor' => 9.2,
                    'aftertaste' => 8.9,
                    'balance' => 9.0,
                ],
                'description' => 'Grown in the dizzying altitudes of Uraga Gomoro (up to 2,300m). Dense beans undergo 48 hours cold mountain water fermentation producing explosive citrus brightness.',
                'status' => 'active',
                'is_featured' => true,
                'image_url' => 'https://images.unsplash.com/photo-1518832553480-cd0e625ed3e6?q=80&w=1200&auto=format&fit=crop',
            ],
            [
                'seller_slug' => 'bensa-highland-specialty',
                'title' => 'Sidama Chire Carbonic Maceration Special G1',
                'origin_region' => 'Sidama',
                'sub_region' => 'Chire Woreda',
                'washing_station' => 'Hamasho Wet Mill',
                'coffee_type' => 'arabica',
                'variety' => '74158 Selection',
                'processing_method' => 'anaerobic',
                'grade' => 'grade_1',
                'screen_size' => 'Screen 15/16',
                'cupping_score' => 90.25,
                'moisture_content' => 10.60,
                'water_activity' => 0.59,
                'crop_year' => '2025/2026',
                'altitude_min' => 2100,
                'altitude_max' => 2250,
                'total_quantity_bags' => 80,
                'available_quantity_bags' => 60,
                'minimum_order_bags' => 5,
                'bag_type' => 'jute_with_grainpro',
                'bag_weight_kg' => 60.00,
                'indicative_price_usd_per_lb' => 5.30,
                'indicative_price_usd_per_mt' => 11684.00,
                'incoterm' => 'FOB Djibouti',
                'port_of_loading' => 'Djibouti',
                'sample_available' => true,
                'sample_cost_usd' => 0.00,
                'tasting_notes' => ['Pomegranate', 'Passionfruit', 'Cocoa Nibs', 'Rose Wine', 'Sparkling Acidity'],
                'sensory_attributes' => [
                    'aroma' => 9.3,
                    'acidity' => 9.2,
                    'body' => 9.0,
                    'flavor' => 9.4,
                    'aftertaste' => 9.1,
                    'balance' => 9.2,
                ],
                'description' => '90+ cupping score competition lot. Whole cherries sealed in stainless steel CO2 injected tanks for 120 hours. Sensational floral and tropical acidity.',
                'status' => 'active',
                'is_featured' => true,
                'image_url' => 'https://images.unsplash.com/photo-1509785307050-d4066910ec1e?q=80&w=1200&auto=format&fit=crop',
            ],
            [
                'seller_slug' => 'kaffa-limu-biosphere',
                'title' => 'Limu Gomma Certified Organic Washed G2',
                'origin_region' => 'Limu',
                'sub_region' => 'Gomma Woreda',
                'washing_station' => 'Gomma Wet Mill',
                'coffee_type' => 'arabica',
                'variety' => 'Limu Heirloom',
                'processing_method' => 'washed',
                'grade' => 'grade_2',
                'screen_size' => 'Screen 14+',
                'cupping_score' => 85.50,
                'moisture_content' => 10.80,
                'water_activity' => 0.58,
                'crop_year' => '2025/2026',
                'altitude_min' => 1800,
                'altitude_max' => 1950,
                'total_quantity_bags' => 500,
                'available_quantity_bags' => 450,
                'minimum_order_bags' => 50,
                'bag_type' => 'jute_with_grainpro',
                'bag_weight_kg' => 60.00,
                'indicative_price_usd_per_lb' => 3.15,
                'indicative_price_usd_per_mt' => 6944.00,
                'incoterm' => 'FOB Djibouti',
                'port_of_loading' => 'Djibouti',
                'sample_available' => true,
                'sample_cost_usd' => 0.00,
                'tasting_notes' => ['Sweet Meyer Lemon', 'Brown Sugar', 'Milk Chocolate', 'Clean Balance'],
                'sensory_attributes' => [
                    'aroma' => 8.5,
                    'acidity' => 8.6,
                    'body' => 8.6,
                    'flavor' => 8.5,
                    'aftertaste' => 8.4,
                    'balance' => 8.7,
                ],
                'description' => 'Excellent specialty volume lot. Sweet, balanced, low defect count with sparkling clean citrus and chocolate undertones. Ideal for single origin and specialty espresso blends.',
                'status' => 'active',
                'is_featured' => false,
                'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?q=80&w=1200&auto=format&fit=crop',
            ],
            [
                'seller_slug' => 'harrar-ancient-sun',
                'title' => 'Harrar Mesela Longberry Winey Moka Natural G1',
                'origin_region' => 'Harrar',
                'sub_region' => 'Mesela Highlands',
                'washing_station' => 'Mesela Sun-Drying Compound',
                'coffee_type' => 'arabica',
                'variety' => 'Harrar Longberry',
                'processing_method' => 'natural',
                'grade' => 'grade_1',
                'screen_size' => 'Screen 16/17',
                'cupping_score' => 86.50,
                'moisture_content' => 10.50,
                'water_activity' => 0.57,
                'crop_year' => '2025/2026',
                'altitude_min' => 1850,
                'altitude_max' => 2050,
                'total_quantity_bags' => 250,
                'available_quantity_bags' => 210,
                'minimum_order_bags' => 25,
                'bag_type' => 'jute_with_grainpro',
                'bag_weight_kg' => 60.00,
                'indicative_price_usd_per_lb' => 3.80,
                'indicative_price_usd_per_mt' => 8377.00,
                'incoterm' => 'FOB Djibouti',
                'port_of_loading' => 'Djibouti',
                'sample_available' => true,
                'sample_cost_usd' => 0.00,
                'tasting_notes' => ['Dried Blueberry', 'Winey Acidity', 'Cardamom', 'Dark Mocha', 'Heavy Body'],
                'sensory_attributes' => [
                    'aroma' => 8.7,
                    'acidity' => 8.5,
                    'body' => 9.0,
                    'flavor' => 8.7,
                    'aftertaste' => 8.6,
                    'balance' => 8.6,
                ],
                'description' => 'Authentic Eastern Ethiopian sun-dried Longberry coffee. Sun-cured on raised mats for 25 days, developing iconic deep fruit wine notes and rich cardamom chocolate aromas.',
                'status' => 'active',
                'is_featured' => false,
                'image_url' => 'https://images.unsplash.com/photo-1507133750040-4a8f57021571?q=80&w=1200&auto=format&fit=crop',
            ],
            [
                'seller_slug' => 'kaffa-limu-biosphere',
                'title' => 'Kaffa Bonga Wild Forest Shade-Grown G1',
                'origin_region' => 'Kaffa',
                'sub_region' => 'Bonga UNESCO Biosphere',
                'washing_station' => 'Bonga Wild Station',
                'coffee_type' => 'arabica',
                'variety' => 'Wild Forest Indigenous Landraces',
                'processing_method' => 'washed',
                'grade' => 'grade_1',
                'screen_size' => 'Screen 15+',
                'cupping_score' => 87.25,
                'moisture_content' => 10.30,
                'water_activity' => 0.55,
                'crop_year' => '2025/2026',
                'altitude_min' => 1800,
                'altitude_max' => 2050,
                'total_quantity_bags' => 160,
                'available_quantity_bags' => 135,
                'minimum_order_bags' => 10,
                'bag_type' => 'jute_with_grainpro',
                'bag_weight_kg' => 60.00,
                'indicative_price_usd_per_lb' => 3.65,
                'indicative_price_usd_per_mt' => 8046.00,
                'incoterm' => 'FOB Djibouti',
                'port_of_loading' => 'Djibouti',
                'sample_available' => true,
                'sample_cost_usd' => 0.00,
                'tasting_notes' => ['Forest Honey', 'Dried Fig', 'Black Tea', 'Tangerine', 'Velvety'],
                'sensory_attributes' => [
                    'aroma' => 8.8,
                    'acidity' => 8.7,
                    'body' => 8.8,
                    'flavor' => 8.8,
                    'aftertaste' => 8.6,
                    'balance' => 8.8,
                ],
                'description' => 'Wild Arabica harvested by local foraging communities deep inside the ancient Bonga rainforest. Incomparable cup complexity with herbal and fig sweetness.',
                'status' => 'active',
                'is_featured' => true,
                'image_url' => 'https://images.unsplash.com/photo-1442512595331-e89e73853f31?q=80&w=1200&auto=format&fit=crop',
            ],
            [
                'seller_slug' => 'kaffa-limu-biosphere',
                'title' => 'Jimma Agaro Nano Challa Cooperative Lot G2',
                'origin_region' => 'Jimma',
                'sub_region' => 'Agaro - Gomma',
                'washing_station' => 'Agaro Cooperative Station',
                'coffee_type' => 'arabica',
                'variety' => 'Agaro Selections',
                'processing_method' => 'washed',
                'grade' => 'grade_2',
                'screen_size' => 'Screen 14+',
                'cupping_score' => 86.75,
                'moisture_content' => 10.40,
                'water_activity' => 0.56,
                'crop_year' => '2025/2026',
                'altitude_min' => 1900,
                'altitude_max' => 2080,
                'total_quantity_bags' => 350,
                'available_quantity_bags' => 320,
                'minimum_order_bags' => 20,
                'bag_type' => 'jute_with_grainpro',
                'bag_weight_kg' => 60.00,
                'indicative_price_usd_per_lb' => 3.35,
                'indicative_price_usd_per_mt' => 7385.00,
                'incoterm' => 'FOB Djibouti',
                'port_of_loading' => 'Djibouti',
                'sample_available' => true,
                'sample_cost_usd' => 0.00,
                'tasting_notes' => ['Sweet Pear', 'Stone Fruit', 'Honeycomb', 'Black Tea', 'Clean Finish'],
                'sensory_attributes' => [
                    'aroma' => 8.7,
                    'acidity' => 8.8,
                    'body' => 8.6,
                    'flavor' => 8.7,
                    'aftertaste' => 8.5,
                    'balance' => 8.8,
                ],
                'description' => 'From the renowned western Ethiopian high country of Agaro. Washed with modern Penagos eco-pulpers and soaked for 24 hours under pure spring water.',
                'status' => 'active',
                'is_featured' => false,
                'image_url' => 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?q=80&w=1200&auto=format&fit=crop',
            ],
        ];

        $createdListings = [];
        foreach ($listings as $lData) {
            $sellerUser = $sellerUsers[$lData['seller_slug']] ?? reset($sellerUsers);
            unset($lData['seller_slug']);
            $imageUrl = $lData['image_url'];
            unset($lData['image_url']);

            $listing = CoffeeListing::updateOrCreate(
                [
                    'seller_id' => $sellerUser->id,
                    'title' => $lData['title'],
                ],
                $lData
            );

            CoffeeListingMedia::updateOrCreate(
                [
                    'listing_id' => $listing->id,
                    'is_primary' => true,
                ],
                [
                    'file_path' => $imageUrl,
                    'file_name' => Str::slug($listing->title) . '.jpg',
                    'mime_type' => 'image/jpeg',
                    'media_type' => 'image',
                    'sort_order' => 1,
                ]
            );

            $createdListings[] = $listing;
        }

        // ==========================================
        // 4. INTERNATIONAL BUYERS & RFQ DEMANDS
        // ==========================================
        $buyersData = [
            [
                'email' => 'buyer@nordicroast.eu',
                'name' => 'Lukas Lindqvist (Nordic Specialty Roasters)',
                'phone' => '+46701234567',
                'country' => 'Sweden',
                'destination_port' => 'Port of Gothenburg',
                'bio' => 'Head of Green Coffee Sourcing at Nordic Specialty Roasters (Gothenburg, Sweden). Purchasing 10-15 containers annually.',
            ],
            [
                'email' => 'kenji@tokyocraftcoffee.jp',
                'name' => 'Kenji Takahashi (Tokyo Craft Roasters Collective)',
                'phone' => '+81355551234',
                'country' => 'Japan',
                'destination_port' => 'Port of Yokohama',
                'bio' => 'Quality buyer for artisanal specialty roasters across Kanto & Kansai regions, Japan.',
            ],
            [
                'email' => 'mateo@milanoroastlab.it',
                'name' => 'Mateo Rossi (Milano Roasting Lab)',
                'phone' => '+39021234567',
                'country' => 'Italy',
                'destination_port' => 'Port of Genoa',
                'bio' => 'Specialty espresso roaster and green coffee importer based in Milan, Italy.',
            ],
            [
                'email' => 'david@pacificgreen.us',
                'name' => 'David Miller (Pacific Green Coffee Co.)',
                'phone' => '+12065550192',
                'country' => 'United States',
                'destination_port' => 'Port of Oakland',
                'bio' => 'Direct trade green coffee importer supplying micro-roasters across the US Pacific Northwest.',
            ],
        ];

        $buyerUsers = [];
        foreach ($buyersData as $bData) {
            $user = User::firstOrCreate(
                ['email' => $bData['email']],
                [
                    'name' => $bData['name'],
                    'password' => Hash::make('Password123!'),
                    'role' => 'student',
                    'phone' => $bData['phone'],
                    'bio' => $bData['bio'],
                ]
            );

            // Give buyer verified plan
            $buyerPlan = MarketplaceSubscriptionPlan::where('slug', 'buyer_verified')->first();
            if ($buyerPlan) {
                UserMarketplaceSubscription::updateOrCreate(
                    ['user_id' => $user->id, 'plan_id' => $buyerPlan->id],
                    [
                        'status' => 'active',
                        'starts_at' => now(),
                        'expires_at' => now()->addYear(),
                        'auto_renew' => true,
                        'notes' => 'Verified international buyer account.',
                    ]
                );
            }

            $buyerUsers[] = [
                'user' => $user,
                'country' => $bData['country'],
                'port' => $bData['destination_port'],
            ];
        }

        // Create 4 Real Buyer RFQs
        $rfqsData = [
            [
                'buyer_idx' => 0, // Nordic
                'rfq_number' => 'RFQ-2026-NORDIC01',
                'title' => 'Seeking 300 Bags Sidama / Guji Natural G1 (SCA 88+)',
                'target_origin_region' => 'Sidama or Guji',
                'target_grade' => 'grade_1',
                'target_processing_method' => 'natural',
                'target_cupping_score_min' => 88.00,
                'volume_bags' => 300,
                'bag_type_preference' => 'jute_with_grainpro',
                'target_price_usd_per_lb' => 4.25,
                'destination_country' => 'Sweden',
                'destination_port' => 'Port of Gothenburg',
                'preferred_incoterm' => 'FOB',
                'preferred_payment_terms' => 'Irrevocable LC at sight',
                'target_delivery_date' => now()->addMonths(3)->toDateString(),
                'closing_date' => now()->addDays(21)->toDateString(),
                'specifications' => 'Strict moisture cutoff at 11.0%. Crop 2025/2026. Pre-shipment PSS sample approval required. Please include tasting notes, washing station GPS/elevation, and valid ECTA exporter license.',
                'status' => 'open',
            ],
            [
                'buyer_idx' => 1, // Tokyo
                'rfq_number' => 'RFQ-2026-TOKYO02',
                'title' => 'Tender: 150 Bags Yirgacheffe Washed G1 Floral / Bergamot (SCA 88.5+)',
                'target_origin_region' => 'Yirgacheffe',
                'target_grade' => 'grade_1',
                'target_processing_method' => 'washed',
                'target_cupping_score_min' => 88.50,
                'volume_bags' => 150,
                'bag_type_preference' => 'jute_with_grainpro',
                'target_price_usd_per_lb' => 4.05,
                'destination_country' => 'Japan',
                'destination_port' => 'Port of Yokohama',
                'preferred_incoterm' => 'FOB',
                'preferred_payment_terms' => 'CAD or LC at sight',
                'target_delivery_date' => now()->addMonths(2)->toDateString(),
                'closing_date' => now()->addDays(14)->toDateString(),
                'specifications' => 'Looking for classic translucent Kochere or Chelchele cup. Must have intense jasmine and bergamot aroma. Vacuum pack or GrainPro mandatory. Screen 15+ preferred.',
                'status' => 'open',
            ],
            [
                'buyer_idx' => 2, // Milan
                'rfq_number' => 'RFQ-2026-MILAN03',
                'title' => 'Volume Order: 500 Bags Limu / Jimma Washed G2 for Espresso Base',
                'target_origin_region' => 'Limu or Jimma',
                'target_grade' => 'grade_2',
                'target_processing_method' => 'washed',
                'target_cupping_score_min' => 85.00,
                'volume_bags' => 500,
                'bag_type_preference' => 'jute_with_grainpro',
                'target_price_usd_per_lb' => 3.10,
                'destination_country' => 'Italy',
                'destination_port' => 'Port of Genoa',
                'preferred_incoterm' => 'FOB',
                'preferred_payment_terms' => '30% Advance, 70% against BL copies',
                'target_delivery_date' => now()->addMonths(4)->toDateString(),
                'closing_date' => now()->addDays(30)->toDateString(),
                'specifications' => 'Commercial specialty grade washed lot. Minimum moisture 10.5%, max 11.5%. Clean cup, free from ferment or phenolic defects. Continuous supply contract possible.',
                'status' => 'open',
            ],
            [
                'buyer_idx' => 3, // Seattle
                'rfq_number' => 'RFQ-2026-SEATTLE04',
                'title' => 'Micro-Lot Request: 200 Bags Guji / Sidama Anaerobic (SCA 89+)',
                'target_origin_region' => 'Guji or Sidama',
                'target_grade' => 'grade_1',
                'target_processing_method' => 'anaerobic',
                'target_cupping_score_min' => 89.00,
                'volume_bags' => 200,
                'bag_type_preference' => 'jute_with_grainpro',
                'target_price_usd_per_lb' => 4.90,
                'destination_country' => 'United States',
                'destination_port' => 'Port of Oakland',
                'preferred_incoterm' => 'FOB',
                'preferred_payment_terms' => 'Direct wire transfer upon PSS sample approval',
                'target_delivery_date' => now()->addMonths(3)->toDateString(),
                'closing_date' => now()->addDays(18)->toDateString(),
                'specifications' => 'Seeking tropical, winey, and strawberry notes for winter specialty lineup. Prefer anaerobic natural or carbonic maceration. Air mail sample required promptly.',
                'status' => 'open',
            ],
        ];

        $createdRfqs = [];
        foreach ($rfqsData as $rData) {
            $buyer = $buyerUsers[$rData['buyer_idx']]['user'];
            unset($rData['buyer_idx']);
            $rData['buyer_id'] = $buyer->id;

            $rfq = MarketplaceRfq::updateOrCreate(
                ['rfq_number' => $rData['rfq_number']],
                $rData
            );
            $createdRfqs[] = $rfq;
        }

        // ==========================================
        // 5. EXPORTER QUOTATIONS ON LIVE RFQs
        // ==========================================
        // Quote 1: Bensa Highland -> Nordic Roasters
        MarketplaceQuotation::updateOrCreate(
            ['quote_number' => 'QUO-2026-BENSA01'],
            [
                'rfq_id' => $createdRfqs[0]->id,
                'seller_id' => $sellerUsers['bensa-highland-specialty']->id,
                'offered_price_usd_per_lb' => 4.15,
                'total_price_usd' => 164673.00, // 300 bags * 60kg * 2.20462 lbs * 4.15
                'quantity_bags' => 300,
                'incoterm' => 'FOB Djibouti',
                'port_of_loading' => 'Djibouti',
                'port_of_destination' => 'Gothenburg',
                'payment_terms' => 'Irrevocable LC at sight via Commercial Bank of Ethiopia',
                'estimated_shipping_date' => now()->addMonths(2)->toDateString(),
                'validity_date' => now()->addDays(14)->toDateString(),
                'terms_and_conditions' => 'Standard ICO green coffee contract. 250g PSS couriered within 5 business days upon bid selection.',
                'sample_available' => true,
                'status' => 'submitted',
            ]
        );

        // Quote 2: Guji Union -> Nordic Roasters
        MarketplaceQuotation::updateOrCreate(
            ['quote_number' => 'QUO-2026-GUJI01'],
            [
                'rfq_id' => $createdRfqs[0]->id,
                'seller_id' => $sellerUsers['oromia-guji-highlands']->id,
                'offered_price_usd_per_lb' => 4.20,
                'total_price_usd' => 166660.00,
                'quantity_bags' => 300,
                'incoterm' => 'FOB Djibouti',
                'port_of_loading' => 'Djibouti',
                'port_of_destination' => 'Gothenburg',
                'payment_terms' => 'CAD through Cooperative Bank of Oromia',
                'estimated_shipping_date' => now()->addMonths(2)->toDateString(),
                'validity_date' => now()->addDays(20)->toDateString(),
                'terms_and_conditions' => 'Certified Organic NOP & Fairtrade. Direct grower lot from Dimtu Station.',
                'sample_available' => true,
                'status' => 'submitted',
            ]
        );

        // Quote 3: Yirgacheffe Mountain Gate -> Tokyo Roasters
        MarketplaceQuotation::updateOrCreate(
            ['quote_number' => 'QUO-2026-YIRGA01'],
            [
                'rfq_id' => $createdRfqs[1]->id,
                'seller_id' => $sellerUsers['yirgacheffe-mountain-gate']->id,
                'offered_price_usd_per_lb' => 3.98,
                'total_price_usd' => 79000.00,
                'quantity_bags' => 150,
                'incoterm' => 'FOB Djibouti',
                'port_of_loading' => 'Djibouti',
                'port_of_destination' => 'Yokohama',
                'payment_terms' => 'CAD via Bank of Tokyo-Mitsubishi / Awash Bank',
                'estimated_shipping_date' => now()->addMonths(2)->toDateString(),
                'validity_date' => now()->addDays(14)->toDateString(),
                'terms_and_conditions' => 'Borena Wet Mill G1 Washed, SCA 88.5 verified. GrainPro packaged.',
                'sample_available' => true,
                'status' => 'submitted',
            ]
        );

        // ==========================================
        // 6. SAMPLE INQUIRIES & DIRECT MESSAGING
        // ==========================================
        if (!empty($createdListings)) {
            // Sample request 1: Nordic Roaster requesting Bombe Anaerobic sample
            MarketplaceInquiry::updateOrCreate(
                [
                    'listing_id' => $createdListings[0]->id,
                    'buyer_id' => $buyerUsers[0]['user']->id,
                ],
                [
                    'seller_id' => $createdListings[0]->seller_id,
                    'inquiry_type' => 'sample_request',
                    'subject' => 'Sample Request: 250g PSS Sidama Bombe Anaerobic G1',
                    'message' => 'Greetings Bensa Highland team. We are extremely impressed with your 89.75 SCA cupping notes. We would like to request a 250g green sample air-couriered to our Gothenburg lab for our evaluation table.',
                    'quantity_interested_bags' => 120,
                    'shipping_address' => 'Nordic Specialty Roasters, Magasinsgatan 18, 411 18 Gothenburg, Sweden',
                    'courier_account' => 'DHL #962817441',
                    'sample_status' => 'dispatched',
                    'tracking_number' => 'DHL-ETH-992184710',
                    'status' => 'replied',
                ]
            );

            // Sample request 2: Tokyo Craft requesting Kochere Washed sample
            MarketplaceInquiry::updateOrCreate(
                [
                    'listing_id' => $createdListings[1]->id,
                    'buyer_id' => $buyerUsers[1]['user']->id,
                ],
                [
                    'seller_id' => $createdListings[1]->seller_id,
                    'inquiry_type' => 'sample_request',
                    'subject' => 'Type Sample Inquiry: Kochere Washed Crown Lot',
                    'message' => 'Konnichiwa. We are looking to book 150 bags for our Spring collection. Please dispatch a 300g sample along with your latest moisture and water activity readings.',
                    'quantity_interested_bags' => 150,
                    'shipping_address' => 'Tokyo Craft Roasters, Shibuya-ku, Jingumae 4-2-12, Tokyo 150-0001, Japan',
                    'courier_account' => 'FedEx #884129571',
                    'sample_status' => 'approved',
                    'tracking_number' => 'FEDEX-ETH-44182901',
                    'status' => 'replied',
                ]
            );

            // Price negotiation inquiry 3: Seattle buyer on Guji Hambela
            MarketplaceInquiry::updateOrCreate(
                [
                    'listing_id' => $createdListings[2]->id,
                    'buyer_id' => $buyerUsers[3]['user']->id,
                ],
                [
                    'seller_id' => $createdListings[2]->seller_id,
                    'inquiry_type' => 'price_quote',
                    'subject' => 'Volume FOB Inquiry: 180 Bags Guji Hambela G1',
                    'message' => 'Hello Guji Union. If we contract the full 180 bag lot with payment via 100% advance wire, can you provide a firm FOB Djibouti offer at $4.05/lb?',
                    'quantity_interested_bags' => 180,
                    'shipping_address' => 'Pacific Green Coffee Co., 1420 5th Ave, Seattle, WA 98101, USA',
                    'sample_status' => 'none',
                    'status' => 'pending',
                ]
            );
        }
    }
}
