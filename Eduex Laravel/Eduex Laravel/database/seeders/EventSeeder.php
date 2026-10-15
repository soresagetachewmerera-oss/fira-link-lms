<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventHighlight;
use App\Models\EventSpeaker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $eventsData = [
            [
                'title' => 'African Fine Coffees Conference & Exhibition (AFCC&E) - Addis Ababa 2026',
                'slug' => 'african-fine-coffees-conference-exhibition-addis-2026',
                'summary' => 'The premier pan-African coffee gathering bringing global roasters, international traders, cuppers, and Ethiopian cooperatives under one roof.',
                'description' => "Welcome to the 22nd African Fine Coffees Conference & Exhibition (AFCC&E) hosted in the heart of coffee's historic birthplace, Addis Ababa.

Over three intensive days, Millennium Hall transforms into the global epicenter of African specialty coffee. Exporters, direct-trade roasters from North America, Europe, the Middle East, and Asia gather to negotiate seasonal contracts, explore state-of-the-art milling technology, and cup freshly harvested micro-lots from across Ethiopia, Kenya, Rwanda, and Uganda.

Participants will gain deep insights into post-harvest processing innovations, sustainable agro-forestry, and crucial compliance frameworks including EU Deforestation Regulation (EUDR) origin mapping.",
                'location' => 'Millennium Hall, Bole Sub-City, Addis Ababa, Ethiopia',
                'location_description' => 'Main Exhibition Arena & VIP B2B Meeting Salons, adjacent to Bole International Airport.',
                'contact_phone' => '+251 11 667 8901',
                'contact_email' => 'events@firalink.com',
                'start_date' => Carbon::parse('2026-11-12'),
                'end_date' => Carbon::parse('2026-11-15'),
                'start_time' => '09:00:00',
                'end_time' => '17:30:00',
                'total_seats' => 120,
                'price' => 4500.00,
                'is_published' => true,
                'featured_image' => 'events/event-1-african-coffee-conference.jpg',
                'metadata' => [
                    'currency' => 'ETB',
                    'category' => 'International Trade Exhibition',
                    'badge' => 'Major Conference',
                ],
                'highlights' => [
                    ['title' => 'Global Buyer Delegation', 'description' => 'Over 80 international green coffee buyers and roaster networks present for direct sourcing.', 'position' => 1],
                    ['title' => 'Origin Cupping Pavilion', 'description' => 'Daily cupping flights featuring Grade 1 washed and natural micro-lots from Yirgacheffe, Sidama, and Guji.', 'position' => 2],
                    ['title' => 'EUDR Compliance Panels', 'description' => 'Technical symposium on digital polygon mapping, satellite traceability, and European import standards.', 'position' => 3],
                ],
                'speakers' => [
                    ['name' => 'Abebe Haile', 'title' => 'Director General, Ethiopian Coffee & Tea Authority', 'bio' => 'Key policymaker spearheading national coffee export modernization and direct trade initiatives.', 'position' => 1],
                    ['name' => 'Elena Rossi', 'title' => 'Head of Green Sourcing, Specialty Coffee Europe', 'bio' => 'Licensed Q-Grader and veteran green coffee importer managing ethical origin supply chains.', 'position' => 2],
                    ['name' => 'Dawit Mekonnen', 'title' => 'Chairman, Oromia Coffee Farmers Union', 'bio' => 'Advocate representing over 400 primary cooperative societies in specialty coffee export.', 'position' => 3],
                ],
            ],
            [
                'title' => 'Ethiopian Cup of Excellence (CoE) National Cupping Showcase 2026',
                'slug' => 'ethiopian-cup-of-excellence-national-cupping-showcase-2026',
                'summary' => 'Exclusive access to sample the top 30 winning competition lots from Yirgacheffe, Sidama, Guji, and West Arsi before the global internet auction.',
                'description' => "The Cup of Excellence (CoE) is the most prestigious competition and auction for high-quality coffees in the world. 

In this curated tasting showcase co-organized with the Alliance for Coffee Excellence (ACE), participants will taste and score the official national winning lots—each scoring above 87 to 92 points on the international specialty scale.

Attendees will receive the official lot analysis dossier detailing farm elevation, anaerobic fermentation parameters, botanical variety, and producer history, providing unbeatable market intelligence prior to the international auction.",
                'location' => 'ECTA National Cupping & Quality Center, Mexico Square, Addis Ababa',
                'location_description' => 'Level 3 Sensory Hall & Calibration Laboratory.',
                'contact_phone' => '+251 11 551 2234',
                'contact_email' => 'coe@firalink.com',
                'start_date' => Carbon::parse('2026-11-20'),
                'end_date' => Carbon::parse('2026-11-22'),
                'start_time' => '08:30:00',
                'end_time' => '16:00:00',
                'total_seats' => 50,
                'price' => 3200.00,
                'is_published' => true,
                'featured_image' => 'events/event-2-ethiopian-cup-of-excellence.jpg',
                'metadata' => [
                    'currency' => 'ETB',
                    'category' => 'Competition & Cupping',
                    'badge' => 'Pre-Auction Tasting',
                ],
                'highlights' => [
                    ['title' => 'Top 30 Auction Lots', 'description' => 'Direct sensory evaluation of 88+ and 90+ point coffees selected by an international jury.', 'position' => 1],
                    ['title' => 'Producer Meet & Greet', 'description' => 'Direct engagement with the award-winning farmers and washing station operators.', 'position' => 2],
                    ['title' => 'Official Bidding Dossier', 'description' => 'Complete technical report with moisture, water activity, screen size, and auction catalog.', 'position' => 3],
                ],
                'speakers' => [
                    ['name' => 'Marcus Vance', 'title' => 'Head Judge, Alliance for Coffee Excellence', 'bio' => 'Internationally recognized coffee sensory scientist with 20+ years adjudicating Cup of Excellence competitions worldwide.', 'position' => 1],
                    ['name' => 'Hanna Wolde', 'title' => 'Chief Q-Grader, ECTA Sensory Laboratory', 'bio' => 'Senior coffee quality auditor directing national cupping protocols and export lot grading.', 'position' => 2],
                ],
            ],
            [
                'title' => 'Specialty Coffee Association (SCA) Green Coffee Grading & Sensory Workshop',
                'slug' => 'sca-green-coffee-grading-sensory-workshop-2026',
                'summary' => 'Hands-on certified training on visual defect analysis, screen sizing, density calculation, moisture testing, and SCA cupping form execution.',
                'description' => "Precision quality grading separates standard commercial coffee from high-margin specialty export lots.

This intensive three-day technical workshop is held inside Fira Link's certified sensory laboratory. Under the instruction of certified Q-Arabica Instructors, participants will master:
- Primary and secondary defect counts according to both SCA and Ethiopian ECTA standards.
- Utilizing calibrated moisture meters and water activity instruments to prevent shipping spoilage.
- Standardized table setup, roast color verification (Agtron), and scoring protocols.
- Identifying processing taints (phenolic, ferment, earthy, over-dryer).

Designed for exporters, QC managers, and commercial buyers aiming for international accreditation.",
                'location' => 'Fira Link Coffee Academy & Sensory Lab, Kazanchis, Addis Ababa',
                'location_description' => 'Fira Link Headquarters, 5th Floor Specialty Training Suite.',
                'contact_phone' => '+251 91 123 4567',
                'contact_email' => 'academy@firalink.com',
                'start_date' => Carbon::parse('2026-12-05'),
                'end_date' => Carbon::parse('2026-12-07'),
                'start_time' => '09:00:00',
                'end_time' => '16:30:00',
                'total_seats' => 24,
                'price' => 6800.00,
                'is_published' => true,
                'featured_image' => 'events/event-3-sca-green-coffee-grading.jpg',
                'metadata' => [
                    'currency' => 'ETB',
                    'category' => 'Professional Workshop',
                    'badge' => 'Certified Training',
                ],
                'highlights' => [
                    ['title' => 'Practical Defect Sorting', 'description' => 'Physical grading of 350g samples across natural, washed, and experimental honey processes.', 'position' => 1],
                    ['title' => 'Instrument Calibration', 'description' => 'Hands-on operation of Sinar and Draminski grain moisture and water activity meters.', 'position' => 2],
                    ['title' => 'Triangle Cupping Tests', 'description' => 'Blind sensory discrimination drills to hone palate sensitivity and defect detection.', 'position' => 3],
                ],
                'speakers' => [
                    ['name' => 'Tewodros Girma', 'title' => 'Licensed Q-Arabica Instructor & Sensory Consultant', 'bio' => 'Trained over 400 professional cuppers and advised leading Ethiopian specialty exporters on quality control.', 'position' => 1],
                    ['name' => 'Selamawit Tadesse', 'title' => 'Senior Quality Assurance Chemist', 'bio' => 'Specialist in coffee post-harvest biochemistry, fermentation kinetics, and moisture retention during maritime shipping.', 'position' => 2],
                ],
            ],
            [
                'title' => 'Kaffa Biosphere & Ancient Coffee Forest Biodiversity Symposium',
                'slug' => 'kaffa-biosphere-ancient-coffee-forest-biodiversity-symposium',
                'summary' => 'Open public symposium and field dialogue on conserving the genetic birthplace of wild Coffea Arabica amidst climate change.',
                'description' => "In the misty montane cloud forests of Kaffa, Coffea Arabica has grown wild for millennia.

Fira Link is proud to sponsor this free community symposium uniting indigenous forest custodians, biological conservationists, university researchers, and youth agro-forestry scholars.

Topics will cover the preservation of landrace varieties, climate resilience, regenerative agro-forestry under indigenous shade canopies, and establishing fair-share carbon and coffee premium distributions directly to smallholder guardians.",
                'location' => 'Bonga National Coffee Museum & Research Sanctuary, Kaffa Zone, Southwest Ethiopia',
                'location_description' => 'Main Auditorium and outdoor canopy research amphitheater, Bonga.',
                'contact_phone' => '+251 47 441 0288',
                'contact_email' => 'kaffa-symposium@firalink.com',
                'start_date' => Carbon::parse('2026-12-15'),
                'end_date' => Carbon::parse('2026-12-16'),
                'start_time' => '10:00:00',
                'end_time' => '15:00:00',
                'total_seats' => 150,
                'price' => 0.00, // FREE EVENT
                'is_published' => true,
                'featured_image' => 'events/event-4-kaffa-forest-biodiversity.jpg',
                'metadata' => [
                    'currency' => 'ETB',
                    'category' => 'Conservation & Community',
                    'badge' => 'Free Community Event',
                ],
                'highlights' => [
                    ['title' => 'Wild Arabica Gene Bank', 'description' => 'Presentation on in-situ conservation of indigenous wild genetic strains.', 'position' => 1],
                    ['title' => 'Indigenous Forest Stewardship', 'description' => 'Honoring elder forest custodians and discussing community timber and coffee rights.', 'position' => 2],
                    ['title' => 'Guided Sanctuary Trek', 'description' => 'Botanical walking expedition through UNESCO core biosphere reserve trails.', 'position' => 3],
                ],
                'speakers' => [
                    ['name' => 'Dr. Kifle Desta', 'title' => 'Professor of Forest Ecology, Jimma University', 'bio' => 'Author of pioneering studies on micro-climate shifts in southwest Ethiopian montane rainforests.', 'position' => 1],
                    ['name' => 'W/ro Aster Bekele', 'title' => 'Indigenous Agro-Forestry Steward & Community Leader', 'bio' => 'Lifelong advocate for community-managed forest coffee concessions in Bonga.', 'position' => 2],
                ],
            ],
            [
                'title' => 'Coffee Export Contract Negotiation, LC & Incoterms Masterclass',
                'slug' => 'coffee-export-contract-negotiation-lc-incoterms-masterclass',
                'summary' => 'A live legal and commercial briefing on National Bank of Ethiopia foreign exchange directives, FOB Djibouti shipping, and irrevocable Letters of Credit.',
                'description' => "Executing a flawless specialty coffee export deal requires mastering banking regulations, currency surrender directives, and transport contracts.

This comprehensive masterclass is offered free to the agro-export community in a hybrid format (both in-person in Addis Ababa and broadcast live online).

Experienced international trade attorneys and commercial banking executives will break down:
- Opening, negotiating, and resolving discrepancies in Letters of Credit (UCP 600 rules).
- Understanding risk allocation between FOB Djibouti and CFR/CIF destination ports under Incoterms 2020.
- Structuring commercial contracts to withstand shipping delays and Red Sea maritime disruptions.
- Compliant forex retention utilization under the newest NBE directives.",
                'location' => 'Online Virtual Hall & Fira Link Training Auditorium, Addis Ababa',
                'location_description' => 'Available virtually via Zoom/Webcast or in person at Fira Link HQ.',
                'contact_phone' => '+251 11 661 9922',
                'contact_email' => 'trade-legal@firalink.com',
                'start_date' => Carbon::parse('2026-12-22'),
                'end_date' => Carbon::parse('2026-12-22'),
                'start_time' => '14:00:00',
                'end_time' => '17:30:00',
                'total_seats' => 300,
                'price' => 0.00, // FREE HYBRID MASTERCLASS
                'is_published' => true,
                'featured_image' => 'events/event-5-export-contracts-logistics.jpg',
                'metadata' => [
                    'currency' => 'ETB',
                    'category' => 'Trade Law & Finance',
                    'badge' => 'Free Hybrid Webinar',
                ],
                'highlights' => [
                    ['title' => 'LC & UCP 600 Practical Guide', 'description' => 'Avoiding the top 10 most common document discrepancies that delay exporter payment.', 'position' => 1],
                    ['title' => 'Incoterms 2020 Risk Allocation', 'description' => 'Clear distinction between seller risk termination at Djibouti Port vs inland destination.', 'position' => 2],
                    ['title' => 'NBE Forex Directives', 'description' => 'Navigating bank retention accounts, surrender ratios, and supplier payments compliantly.', 'position' => 3],
                ],
                'speakers' => [
                    ['name' => 'Yared Alemu', 'title' => 'International Trade Attorney & Maritime Counsel', 'bio' => 'Advises leading Ethiopian agricultural exporters on international sales contracts and arbitration.', 'position' => 1],
                    ['name' => 'Bizuneh Tilahun', 'title' => 'VP of International Banking Operations', 'bio' => 'Specialist in trade finance instruments, documentary credits, and foreign exchange compliance.', 'position' => 2],
                ],
            ],
        ];

        foreach ($eventsData as $data) {
            $highlights = $data['highlights'] ?? [];
            $speakers = $data['speakers'] ?? [];
            unset($data['highlights'], $data['speakers']);

            $event = Event::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );

            // Seed highlights
            $event->highlights()->delete();
            foreach ($highlights as $hl) {
                $event->highlights()->create($hl);
            }

            // Seed speakers
            $event->speakers()->delete();
            foreach ($speakers as $spk) {
                $event->speakers()->create($spk);
            }
        }
    }
}
