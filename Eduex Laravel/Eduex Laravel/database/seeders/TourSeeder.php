<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tour;
use Carbon\Carbon;

class TourSeeder extends Seeder
{
    public function run(): void
    {
        $tours = [
            [
                'title' => 'Yirgacheffe & Gedeo Specialty Coffee Origin Expedition',
                'slug' => 'yirgacheffe-gedeo-specialty-coffee-origin-expedition',
                'summary' => 'Explore world-renowned high-altitude washing stations, meet cooperative farmers, and cup Grade 1 natural & washed coffees at the source.',
                'description' => 'Embark on an immersive 4-day professional expedition into the heart of Yirgacheffe and the Gedeo zone. Tour iconic micro-regions, observe selective hand-picking, witness advanced wet fermentation methods, and engage in cupping sessions with master cuppers.',
                'destination' => 'Yirgacheffe & Dilla, Gedeo Zone, Southern Ethiopia',
                'meeting_point' => 'Bole International Airport / Fira Link HQ, Addis Ababa',
                'duration_days' => 4,
                'duration_hours' => 96,
                'start_date' => Carbon::now()->addDays(14)->toDateString(),
                'end_date' => Carbon::now()->addDays(18)->toDateString(),
                'departure_time' => '06:00 AM',
                'total_seats' => 16,
                'price' => 18500.00,
                'currency' => 'ETB',
                'category' => 'Origin Expedition',
                'difficulty_level' => 'Moderate',
                'featured_image' => 'tours/tour-1-yirgacheffe-origin-expedition.jpg',
                'gallery' => [
                    'tours/tour-1-yirgacheffe-origin-expedition.jpg',
                    'tours/tour-3-sidama-bensa-washing-stations.jpg',
                ],
                'itinerary' => [
                    ['day' => 1, 'title' => 'Addis Ababa to Hawassa & Dilla Gateway', 'description' => 'Scenic drive through the Rift Valley, briefing on regional terroirs, evening welcome dinner in Dilla.'],
                    ['day' => 2, 'title' => 'Yirgacheffe Washing Stations & Wet Milling', 'description' => 'Visit Kochere and Chelelektu processing mills, study pulping, grading channels, and underwater fermentation.'],
                    ['day' => 3, 'title' => 'Raised African Drying Beds & Farm Cupping', 'description' => 'Inspect moisture management on raised drying tables, participate in calibrated field cupping.'],
                    ['day' => 4, 'title' => 'Cooperative Union Headquarters & Return', 'description' => 'Meeting with export management team, contract discussions, and return flight/drive to Addis Ababa.'],
                ],
                'included_services' => [
                    'All 4WD ground transportation with professional drivers',
                    '3 nights eco-lodge accommodation with full board',
                    'Licensed Q-Grader guided field cuppings',
                    'Entry permits and cooperative mill access passes',
                    'Fira Link Origin Certificate of Completion',
                ],
                'exclusions' => [
                    'Personal travel insurance',
                    'Alcoholic beverages and personal souvenirs',
                ],
                'requirements' => [
                    'Sturdy walking/hiking boots for muddy mill terraces',
                    'Lightweight rain jacket and sun hat',
                    'Valid government ID or passport',
                ],
                'cancellation_policy' => 'Full refund up to 7 days before departure. 50% refund within 3 to 6 days.',
                'is_published' => true,
                'is_featured' => true,
            ],
            [
                'title' => 'Kaffa UNESCO Biosphere & Ancient Coffee Forest Discovery',
                'slug' => 'kaffa-unesco-biosphere-ancient-coffee-forest-discovery',
                'summary' => 'Journey into the birthplace of wild Coffea arabica inside the protected Kaffa rainforest with botanical researchers.',
                'description' => 'Experience the primeval origins of coffee inside the UNESCO Kaffa Biosphere Reserve. Trek through dense ancient cloud forests where wild Arabica thrives naturally, visit traditional forest coffee harvesters, and understand agroforestry conservation.',
                'destination' => 'Bonga & Kaffa Rainforest, Southwestern Ethiopia',
                'meeting_point' => 'Fira Link HQ, Addis Ababa',
                'duration_days' => 5,
                'duration_hours' => 120,
                'start_date' => Carbon::now()->addDays(28)->toDateString(),
                'end_date' => Carbon::now()->addDays(33)->toDateString(),
                'departure_time' => '05:30 AM',
                'total_seats' => 12,
                'price' => 24000.00,
                'currency' => 'ETB',
                'category' => 'Heritage & Ecology',
                'difficulty_level' => 'Challenging',
                'featured_image' => 'tours/tour-2-kaffa-ancient-forest-discovery.jpg',
                'gallery' => [
                    'tours/tour-2-kaffa-ancient-forest-discovery.jpg',
                    'tours/tour-1-yirgacheffe-origin-expedition.jpg',
                ],
                'itinerary' => [
                    ['day' => 1, 'title' => 'Drive to Jimma & Bonga', 'description' => 'Travel southwest through lush highlands to the ancient Kingdom of Kaffa.'],
                    ['day' => 2, 'title' => 'Wild Coffee Forest Trekking', 'description' => 'Guided botanical trek through natural mother coffee trees and epiphytes.'],
                    ['day' => 3, 'title' => 'Traditional Harvesters & Honey-Coffee Guilds', 'description' => 'Learn indigenous processing techniques and wild forest honey harvesting.'],
                    ['day' => 4, 'title' => 'Kaffa National Coffee Museum & Research Center', 'description' => 'Visit the national coffee research plots and sensory laboratory.'],
                    ['day' => 5, 'title' => 'Return to Addis Ababa', 'description' => 'Scenic return drive with farewell dinner and reflection session.'],
                ],
                'included_services' => [
                    'Full-board eco-lodge lodging in Bonga',
                    'Forestry guide and armed scout escort',
                    'All domestic transit in Toyota Land Cruisers',
                    'Field equipment and rain gear provision',
                ],
                'exclusions' => ['Personal expenses'],
                'requirements' => ['Good physical fitness for 3-4 hour rainforest walks'],
                'cancellation_policy' => '100% refund up to 10 days before tour start.',
                'is_published' => true,
                'is_featured' => true,
            ],
            [
                'title' => 'Sidama Bensa High-Elevation Micro-Lot Intensive',
                'slug' => 'sidama-bensa-high-elevation-micro-lot-intensive',
                'summary' => 'Visit Cup of Excellence winning farms in Bensa, Sidama above 2,200m altitude and master experimental processing.',
                'description' => 'Sidama Bensa has become the epicenter of Ethiopias highest scoring Cup of Excellence micro-lots. This intensive 3-day tour is tailored for specialty buyers, roasters, and aspiring export entrepreneurs who want to inspect anaerobic fermentation tanks, yeast inoculations, and slow shade-drying setups.',
                'destination' => 'Bensa, Daye & Hawassa, Sidama Region',
                'meeting_point' => 'Hawassa Airport or Addis Ababa HQ',
                'duration_days' => 3,
                'duration_hours' => 72,
                'start_date' => Carbon::now()->addDays(21)->toDateString(),
                'end_date' => Carbon::now()->addDays(24)->toDateString(),
                'departure_time' => '07:00 AM',
                'total_seats' => 14,
                'price' => 16500.00,
                'currency' => 'ETB',
                'category' => 'Micro-Lot & Processing',
                'difficulty_level' => 'Easy',
                'featured_image' => 'tours/tour-3-sidama-bensa-washing-stations.jpg',
                'gallery' => [
                    'tours/tour-3-sidama-bensa-washing-stations.jpg',
                ],
                'itinerary' => [
                    ['day' => 1, 'title' => 'Addis to Hawassa & Lakeside Briefing', 'description' => 'Flight or express drive to Hawassa, introduction to Sidama micro-climates.'],
                    ['day' => 2, 'title' => 'Bensa Elevation Ascent (2,200m - 2,400m)', 'description' => 'Full-day site visit to award-winning private washing stations and outgrowers.'],
                    ['day' => 3, 'title' => 'Experimental Lot Cupping & Return', 'description' => 'Blind cupping of natural, washed, and anaerobic lots with scoring sheets.'],
                ],
                'included_services' => [
                    'Hawassa luxury hotel accommodations',
                    'All meals and refreshing highland coffee tastings',
                    'CoE style calibrated cupping forms and spoons',
                ],
                'exclusions' => ['International flights'],
                'requirements' => ['Enthusiasm for specialty cupping standards'],
                'cancellation_policy' => 'Free cancellation up to 5 days prior.',
                'is_published' => true,
                'is_featured' => false,
            ],
            [
                'title' => 'Harar Ancient Walled City & Sun-Dried Longberry Heritage Tour',
                'slug' => 'harar-ancient-walled-city-sun-dried-longberry-heritage-tour',
                'summary' => 'Discover the legendary Harar Jugol UNESCO walled city, historical trading guilds, and unique dry-processed mocha beans.',
                'description' => 'Harar is steeped in over a thousand years of international trade history. Tour the historic spice markets, meet traditional dry-mill operators processing authentic Harar Longberry and Shortberry lots, experience the legendary night hyena feeding ritual, and discover the distinctive winey-blueberry cup profile.',
                'destination' => 'Harar Jugol & Dire Dawa, Eastern Ethiopia',
                'meeting_point' => 'Dire Dawa International Airport',
                'duration_days' => 3,
                'duration_hours' => 72,
                'start_date' => Carbon::now()->addDays(35)->toDateString(),
                'end_date' => Carbon::now()->addDays(38)->toDateString(),
                'departure_time' => '08:00 AM',
                'total_seats' => 18,
                'price' => 15000.00,
                'currency' => 'ETB',
                'category' => 'Cultural Heritage',
                'difficulty_level' => 'Easy',
                'featured_image' => 'tours/tour-4-harar-heritage-coffee-exchange.jpg',
                'gallery' => [
                    'tours/tour-4-harar-heritage-coffee-exchange.jpg',
                ],
                'itinerary' => [
                    ['day' => 1, 'title' => 'Dire Dawa to Historic Harar', 'description' => 'Arrival, transfer to Harar, walking tour through the 5 gates of Jugol.'],
                    ['day' => 2, 'title' => 'Harar Dry Mills & Spice Bazaars', 'description' => 'Inspect dry milling and hand-sorting operations, cupping of Harar Horse and Longberry lots.'],
                    ['day' => 3, 'title' => 'Arthur Rimbaud Cultural Center & Return', 'description' => 'Historical exhibition on 19th century coffee trade, transfer to Dire Dawa.'],
                ],
                'included_services' => [
                    'Heritage boutique hotel stay inside Jugol',
                    'All meals and guided cultural tours',
                    'Airport transfers from Dire Dawa',
                ],
                'exclusions' => ['Flights to Dire Dawa'],
                'requirements' => ['Modest attire for historic city gates'],
                'cancellation_policy' => 'Cancel with full refund up to 7 days before.',
                'is_published' => true,
                'is_featured' => false,
            ],
            [
                'title' => 'Guji Highland Cloud Forest Safari & Direct Trade Masterclass',
                'slug' => 'guji-highland-cloud-forest-safari-direct-trade-masterclass',
                'summary' => 'Uncover the volcanic terroirs of Uraga, Hambela, and Shakiso, and negotiate real-time direct contracts.',
                'description' => 'Guji has emerged as the premier darling of specialty coffee enthusiasts. Travel across spectacular highland passes, walk among semi-forest farms shaded by indigenous wanza and birbira trees, and study direct export contracting from farm to dry mill.',
                'destination' => 'Shakiso & Uraga, Guji Zone, Southern Oromia',
                'meeting_point' => 'Fira Link HQ, Addis Ababa',
                'duration_days' => 4,
                'duration_hours' => 96,
                'start_date' => Carbon::now()->addDays(42)->toDateString(),
                'end_date' => Carbon::now()->addDays(46)->toDateString(),
                'departure_time' => '06:00 AM',
                'total_seats' => 14,
                'price' => 21000.00,
                'currency' => 'ETB',
                'category' => 'Direct Trade',
                'difficulty_level' => 'Moderate',
                'featured_image' => 'tours/tour-5-guji-highland-coffee-safari.jpg',
                'gallery' => [
                    'tours/tour-5-guji-highland-coffee-safari.jpg',
                ],
                'itinerary' => [
                    ['day' => 1, 'title' => 'Addis to Yabello / Shakiso Route', 'description' => 'Journey south across Oromia highlands, scenic evening camp setup.'],
                    ['day' => 2, 'title' => 'Uraga Volcanic Terroirs & Community Mills', 'description' => 'Visit outgrower blocks situated at 2,300 meters above sea level.'],
                    ['day' => 3, 'title' => 'Direct Vertical Trade Contracts Workshop', 'description' => 'Hands-on review of FOB contracts, minimum pricing, and EUDR mapping.'],
                    ['day' => 4, 'title' => 'Final Cupping & Return Drive', 'description' => 'Sensory cupping of Guji natural lots and return journey.'],
                ],
                'included_services' => [
                    'Comfortable lodge accommodations',
                    'All logistics and field workshops',
                    'Direct contact list of verified Guji washing stations',
                ],
                'exclusions' => ['Personal expenditures'],
                'requirements' => ['Comfortable walking attire'],
                'cancellation_policy' => '100% refund before 7 days.',
                'is_published' => true,
                'is_featured' => true,
            ],
        ];

        foreach ($tours as $tourData) {
            Tour::updateOrCreate(
                ['slug' => $tourData['slug']],
                $tourData
            );
        }
    }
}
