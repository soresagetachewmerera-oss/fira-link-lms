<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogComment;
use App\Models\BlogPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BlogPostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoryLookup = BlogCategory::query()
            ->pluck('id', 'slug')
            ->all();

        $posts = [
            [
                'title' => 'The Future of Coffee Trade: How Fira Link Connects Ethiopian Producers Directly to Global Buyers',
                'slug' => 'future-of-coffee-trade-fira-link-ethiopian-producers-global-buyers',
                'category' => 'Fira Link Platform',
                'author_name' => 'Fira Link Research & Trade Desk',
                'excerpt' => 'Discover how Fira Link is transforming Ethiopia’s specialty coffee sector through digital lot traceability, transparent farm-gate pricing, and direct-to-roaster matchmaking.',
                'content' => <<<'HTML'
<p>Ethiopia produces some of the most sought-after Arabica coffees on earth, yet for decades, smallholder farming families and primary cooperatives have operated through fragmented, opaque supply chains. Middlemen, delayed payments, and disconnected logistics have historically diluted both the origin identity and the financial returns for the producers who nurture these exceptional beans.</p>

<h3>Bridging the Origin Gap with Digital Innovation</h3>
<p>The <strong>Fira Link Platform</strong> was engineered from the ground up to solve this systemic challenge. By uniting digital origin traceability with an integrated B2B direct trade architecture, Fira Link empowers Ethiopian cooperatives and licensed washing station operators to showcase verified micro-lots directly to specialty roasters in North America, Europe, the Middle East, and Asia.</p>

<blockquote>
    <p>“Before Fira Link, our cooperative had to wait months to understand where our Grade 1 Yirgacheffe landed in Europe. Today, every container is logged with cupping scores, GPS coordinates, and verified direct payouts to our member farmers.”</p>
    <footer>─ Tadesse Meskele, Union Chairman & Coffee Veteran</footer>
</blockquote>

<h3>Core Platform Capabilities Powering Direct Trade</h3>
<ul>
    <li><strong>End-to-End Lot Traceability:</strong> From elevation benchmarks (1,800m–2,200m) and varietal classification to milling dates and harvest batch numbers, every lot features an immutable digital origin passport.</li>
    <li><strong>Fair Farm-Gate Valuation:</strong> Real-time benchmarking aligned with Ethiopian Coffee and Tea Authority (ECTA) minimum release prices and global ICE C-market dynamics ensures equitable grower margins.</li>
    <li><strong>Integrated Quality Scoring:</strong> Pre-shipment cupping sheets calibrated by certified Q-Graders are uploaded directly to the lot profile alongside high-definition green bean inspection imagery.</li>
    <li><strong>Direct Escrow & Smart Contracts:</strong> Automated milestone verification unlocks trade financing and protects both roaster pre-payments and producer deliveries.</li>
</ul>

<p>As the international coffee community demands greater environmental transparency and ethical accountability, platforms like Fira Link provide the technological foundation necessary to make direct origin sourcing intuitive, legally compliant, and mutually profitable.</p>
HTML,
                'featured_image' => 'storage/blogs/blog-1-ethiopian-coffee-harvest-fira-link.jpg',
                'tags' => ['fira-link', 'coffee-trade', 'ethiopia', 'traceability', 'direct-trade', 'producers'],
                'reading_time_minutes' => 6,
                'is_published' => true,
                'published_at' => now()->subDays(2),
                'meta' => [
                    'title' => 'How Fira Link Connects Ethiopian Coffee Producers to Global Roasters',
                    'description' => 'Explore how Fira Link’s digital traceability and direct trade platform is revolutionizing Ethiopia’s specialty coffee value chain.',
                ],
            ],
            [
                'title' => 'The Traditional Ethiopian Coffee Ceremony: Preserving Heritage in the Specialty Coffee Era',
                'slug' => 'traditional-ethiopian-coffee-ceremony-preserving-heritage-specialty-coffee',
                'category' => 'Coffee Heritage',
                'author_name' => 'Genet Wolde, Cultural Ambassador',
                'excerpt' => 'From freshly pan-roasted green beans to the aromatic pour of the clay Jebena, explore the spiritual and cultural foundation of Buna that shaped global coffee culture.',
                'content' => <<<'HTML'
<p>In Ethiopia—the undisputed birthplace of <em>Coffea Arabica</em>—coffee is not merely a beverage to kickstart a busy morning; it is a sacred social sacrament known as <strong>Buna</strong>. The Ethiopian coffee ceremony is a multi-sensory ritual practiced daily in millions of households, bringing neighbors together for connection, storytelling, and spiritual reflection.</p>

<h3>The Architecture of the Ceremony</h3>
<p>The ritual unfolds in an atmosphere of deliberate mindfulness. The floor is strewn with fresh, fragrant green grass (*ketema*), symbolizing abundance and renewal. An incense burner (*itan*) releases aromatic frankincense smoke into the room to purify the air and welcome guests.</p>

<p>The host—typically a woman dressed in an intricately embroidered white cotton <em>Habesha Kemis</em>—washes raw green coffee beans before roasting them by hand in a flat iron pan (*mitad*) over hot glowing coals. As the beans crack and turn a shimmering mahogany, she walks around the room, wafting the fragrant, freshly roasted aroma toward every guest to inhale.</p>

<blockquote>
    <p>“Buna Tetu (come drink coffee) is an invitation to pause time. You cannot rush the Jebena. In our culture, to share coffee is to share peace.”</p>
    <footer>─ Genet Wolde, Lead Cultural Educator at Fira Link</footer>
</blockquote>

<h3>The Three Sacred Pours</h3>
<p>Once coarsely pounded with a wooden mortar and pestle (*mukecha* and *zenezena*), the coffee grounds are simmered in boiling water inside a spherical, long-necked black clay pot called the <strong>Jebena</strong>. The coffee is served in three distinct rounds into handleless ceramic cups (*cini*):</p>
<ol>
    <li><strong>Abol:</strong> The first and strongest extraction, enjoyed for its bold complexity, body, and awakening energy.</li>
    <li><strong>Tona:</strong> Water is added back to the Jebena and brewed a second time, fostering deeper dialogue and warmth.</li>
    <li><strong>Baraka:</strong> The third and final round, known as the cup of blessing, sealing the gathering with goodwill.</li>
</ol>

<p>Through the <strong>Fira Link Academy</strong> and Origin Tours, international coffee professionals are immersed in this living heritage, discovering that the true essence of specialty coffee was mastered centuries ago in the Ethiopian highlands.</p>
HTML,
                'featured_image' => 'storage/blogs/blog-2-traditional-ethiopian-jebena-ceremony.jpg',
                'tags' => ['jebena', 'ethiopian-coffee', 'culture', 'buna', 'heritage', 'habesha'],
                'reading_time_minutes' => 5,
                'is_published' => true,
                'published_at' => now()->subDays(5),
                'meta' => [
                    'title' => 'The Traditional Ethiopian Coffee Ceremony & Cultural Heritage',
                    'description' => 'Discover the sacred Buna ceremony, from clay Jebena brewing to the three pours of Abol, Tona, and Baraka.',
                ],
            ],
            [
                'title' => 'Calibrating the Cup: A Deep Dive into Specialty Coffee Cupping and Q-Grader Scoring in Addis Ababa',
                'slug' => 'calibrating-the-cup-specialty-coffee-cupping-q-grader-scoring-addis-ababa',
                'category' => 'Grading & Cupping',
                'author_name' => 'Dawit Alemayehu, Certified Q-Arabica Grader',
                'excerpt' => 'Step inside a premier cupping laboratory in Addis Ababa to understand how licensed Q-Graders evaluate fragrance, aroma, acidity, and body to award 85+ specialty scores.',
                'content' => <<<'HTML'
<p>Behind every top-tier Ethiopian micro-lot exported across the globe lies an uncompromising sensory calibration protocol. In the cupping laboratories of Addis Ababa, certified Q-Graders and sensory specialists evaluate hundreds of lots weekly, translating subtle chemical nuances into standardized Specialty Coffee Association (SCA) scores.</p>

<h3>The Anatomy of the Cupping Table</h3>
<p>A rigorous cupping session follows precise scientific parameters: exactly 8.25 grams of whole bean coffee per 150ml of clean filtered water heated to 93°C (200°F). Grind size is calibrated slightly coarser than drip filter to expose maximum surface area without over-extracting astringent phenolics.</p>

<p>Sensory evaluation occurs across four distinct temperature stages:</p>
<ul>
    <li><strong>Dry Fragrance (Room Temp):</strong> Evaluating the dry grounds for sweet florals, citrus zest, stone fruit, or warm spices.</li>
    <li><strong>Wet Aroma (Immediately Post-Pour & Crust Break):</strong> Inhaling the concentrated steam released when the coffee crust is gently pushed aside with a silver cupping spoon at minute four.</li>
    <li><strong>Flavor & Aftertaste (70°C down to 60°C):</strong> Slurping forcefully to aerate the coffee across the palate, analyzing balance, sweetness, and lingering resonance.</li>
    <li><strong>Acidity, Clean Cup & Uniformity (Cooling to 37°C):</strong> Scoring brightness—from effervescent phosphoric notes in washed Yirgacheffes to winey malic acid in anaerobic Sidamas.</li>
</ul>

<blockquote>
    <p>“An 88-point Ethiopian lot does not happen by accident. It demands immaculate cherry maturity at the tree, precise water temperature in the wet mill, and defect-free drying on raised beds.”</p>
    <footer>─ Dawit Alemayehu, Chief Sensory Calibration Director</footer>
</blockquote>

<h3>Fira Link Digital Sensory Scorecards</h3>
<p>Fira Link incorporates full digital cupping records into every lot listing. Importers and roasters can examine detailed sensory spider-graphs, moisture meter logs, and screen-size breakdowns before ever ordering physical green samples, accelerating trade negotiations with absolute confidence.</p>
HTML,
                'featured_image' => 'storage/blogs/blog-3-ethiopian-coffee-cupping-grading-lab.jpg',
                'tags' => ['cupping', 'q-grader', 'sca-scoring', 'sensory-analysis', 'addis-ababa', 'coffee-lab'],
                'reading_time_minutes' => 7,
                'is_published' => true,
                'published_at' => now()->subDays(9),
                'meta' => [
                    'title' => 'Specialty Coffee Cupping & Q-Grader Scoring Protocols in Addis Ababa',
                    'description' => 'How certified Q-Graders score Ethiopian specialty coffees using SCA standards, sensory evaluation, and digital quality metrics.',
                ],
            ],
            [
                'title' => 'Mastering Sidama & Guji Processing: The Science of Raised African Drying Beds and Fermentation',
                'slug' => 'mastering-sidama-guji-processing-african-drying-beds-fermentation',
                'category' => 'Processing & Roasting',
                'author_name' => 'Almaz Tadesse, Post-Harvest Specialist',
                'excerpt' => 'Explore how meticulous cherry selection, shaded African drying beds, and controlled anaerobic fermentation yield award-winning Ethiopian Cup of Excellence profiles.',
                'content' => <<<'HTML'
<p>The highlands of Sidama and Guji—perched at dizzying elevations exceeding 2,100 meters above sea level—are home to some of the world's most sophisticated post-harvest coffee processing innovations. While genetic terroir provides the blueprint, it is post-harvest processing that dictates whether a lot achieves commercial commodity status or reaches coveted Cup of Excellence tier.</p>

<h3>The Architecture of Raised African Beds</h3>
<p>Unlike patio drying where heat from the concrete can scorch delicate parchment, traditional raised African beds utilize breathable mesh wire suspended roughly one meter off the ground. This elevates air circulation both above and underneath the drying cherry layer, preventing condensation and mold development.</p>

<p>Key quality control practices on the beds include:</p>
<ul>
    <li><strong>Continuous Turning:</strong> Cherries are gently turned by hand every 30 to 45 minutes to maintain uniform moisture migration throughout the fruit pulp.</li>
    <li><strong>Midday Shading:</strong> During peak sun hours (12:00 PM to 2:30 PM), breathable shade netting is deployed across beds to prevent thermal shock and embryonic death of the seed.</li>
    <li><strong>Target Moisture Resting:</strong> Drying continues until the cherry reaches an optimal 10.5% to 11.5% moisture content, followed by a mandatory 30-day parchment conditioning phase in temperature-controlled warehouses.</li>
</ul>

<blockquote>
    <p>“Proper moisture management on raised beds is the guardian of longevity. It is what allows an Ethiopian lot to arrive in Hamburg or Tokyo with its floral brilliance intact months later.”</p>
    <footer>─ Almaz Tadesse, Lead Processing Agronomist</footer>
</blockquote>

<h3>The Rise of Anaerobic & Honey Fermentations</h3>
<p>Over the past three seasons, progressive Ethiopian millers partnered with Fira Link have adopted sealed anaerobic tanks, introducing carbonic maceration and low-oxygen yeast ferments that amplify wild berry, cacao nib, and lavender complexities.</p>
HTML,
                'featured_image' => 'storage/blogs/blog-4-sidama-washing-stations-drying-beds.jpg',
                'tags' => ['sidama', 'guji', 'drying-beds', 'natural-process', 'post-harvest', 'fermentation'],
                'reading_time_minutes' => 6,
                'is_published' => true,
                'published_at' => now()->subDays(14),
                'meta' => [
                    'title' => 'Sidama & Guji Coffee Processing on Raised African Beds',
                    'description' => 'A comprehensive guide to post-harvest processing, sun-drying beds, and anaerobic fermentation in Ethiopian specialty coffee.',
                ],
            ],
            [
                'title' => 'Navigating Ethiopian Coffee Export Regulations: Direct Contracts, ECTA Compliance, and Logistics',
                'slug' => 'navigating-ethiopian-coffee-export-regulations-ecta-compliance-logistics',
                'category' => 'Export & Trade',
                'author_name' => 'Tewodros Mengistu, International Trade Consultant',
                'excerpt' => 'A definitive operational guide for international importers and Ethiopian exporters on direct trade licenses, minimum price benchmarks, and shipping corridors.',
                'content' => <<<'HTML'
<p>Executing green coffee export contracts from Ethiopia requires clear mastery of evolving regulatory frameworks, financial instruments, and multimodal logistics. Following historic reforms by the Ethiopian Coffee and Tea Authority (ECTA), direct export licenses now enable commercial growers and smallholder unions to trade directly with international buyers, bypassing central commodity auctions.</p>

<h3>Key Regulatory Milestones for Green Coffee Exporters</h3>
<p>To successfully clear a shipping container of Ethiopian specialty coffee, exporters must fulfill strict multi-agency requirements:</p>
<ul>
    <li><strong>ECTA Minimum Release Price Alignment:</strong> Every contract price per pound must meet or exceed the official weekly indicative benchmark published by the ECTA for each origin and grade.</li>
    <li><strong>National Quality Inspection (CQI / ECTA Lab):</strong> Every outbound lot is sampled and graded at the national terminal in Addis Ababa to confirm export grade specifications (Grade 1 through Grade 5).</li>
    <li><strong>Phytosanitary & ICO Certification:</strong> Official documentation certifying freedom from agricultural pests and validating the International Coffee Organization (ICO) origin serials.</li>
    <li><strong>Foreign Exchange Regulations:</strong> Compliance with National Bank of Ethiopia foreign exchange retention directives and Letter of Credit (LC) or Cash Against Documents (CAD) settlement terms.</li>
</ul>

<blockquote>
    <p>“Speed of documentation is the difference between fresh harvest arrival and stale cargo. Digital platforms that harmonize compliance documents eliminate weeks of port demurrage.”</p>
    <footer>─ Tewodros Mengistu, Trade & Supply Chain Advisor</footer>
</blockquote>

<h3>The Djibouti Transit Corridor</h3>
<p>Once cleared from inland container depots in Kality or Modjo, containerized lots travel via the modern electrified Ethio-Djibouti Railway or specialized road convoys to the Port of Djibouti, where maritime vessels embark for destinations worldwide.</p>
HTML,
                'featured_image' => 'storage/blogs/blog-5-ethiopian-coffee-export-logistics.jpg',
                'tags' => ['export', 'ecta', 'ecx', 'contracts', 'logistics', 'djibouti-corridor', 'trade'],
                'reading_time_minutes' => 8,
                'is_published' => true,
                'published_at' => now()->subDays(20),
                'meta' => [
                    'title' => 'Ethiopian Coffee Export Compliance, ECTA Regulations & Shipping Logistics',
                    'description' => 'Step-by-step guidance on Ethiopian green coffee export licensing, minimum price benchmarks, and international container logistics.',
                ],
            ],
            [
                'title' => 'Terroir and Elevation in Yirgacheffe: Why High-Altitude Ethiopian Coffees Dominate the Global Stage',
                'slug' => 'terroir-and-elevation-in-yirgacheffe-high-altitude-ethiopian-coffees',
                'category' => 'Origin & Terroir',
                'author_name' => 'Elias Kassa, Agronomist & Tour Director',
                'excerpt' => 'Unraveling the geological and climatic factors of Yirgacheffe—from volcanic iron-rich soil to extreme diurnal temperature shifts—that produce world-renowned sweetness and complexity.',
                'content' => <<<'HTML'
<p>Among all origin names whispered with reverence across specialty coffee roasteries, none commands greater recognition than <strong>Yirgacheffe</strong>. Located in the Southern Nations, Nationalities, and Peoples' Region within the Gedeo Zone, this compact geographical enclave consistently produces coffees of ethereal florality, citrus brightness, and tea-like elegance.</p>

<h3>The Geological Gift of Elevation and Soil</h3>
<p>Yirgacheffe's magic begins with extreme geography. Coffee farms and forest plots here climb to elevations between 1,900 and 2,250 meters above sea level. At these altitudes, cooler ambient temperatures dramatically slow cherry maturation, forcing the coffee tree to concentrate organic sugars, amino acids, and complex flavor precursors into the seed over nine months.</p>

<p>Key agro-ecological pillars include:</p>
<ul>
    <li><strong>Ancient Volcanic Soils:</strong> Deep, red basaltic soils rich in iron, potassium, and nitrogen provide optimal nutrient availability without requiring synthetic chemical fertilizers.</li>
    <li><strong>Indigenous Shade Canopy:</strong> Giant native trees such as <em>Cordia africana</em> and <em>Millettia ferruginea</em> provide dappled shade, protecting delicate coffee shrubs from intense equatorial midday ultraviolet radiation.</li>
    <li><strong>Heirloom Landraces:</strong> Thousands of naturally selected wild Ethiopian Arabica varietals—such as Dega, Kurume, and Wolisho—contribute irreplaceable biodiversity not found anywhere else in the world.</li>
</ul>

<blockquote>
    <p>“When you taste a washed Yirgacheffe, you are tasting thousands of years of evolutionary forest genetics nurtured under high-altitude mist.”</p>
    <footer>─ Elias Kassa, Origin Agronomist & Expedition Lead</footer>
</blockquote>

<h3>Experience Origin Through Fira Link Tours</h3>
<p>Through the <strong>Fira Link Coffee Origin Expeditions</strong>, roasters and sensory professionals can walk the terraced hillsides of Kochere and Chelelektu, cup side-by-side with millers, and secure micro-lot allocations before harvest season concludes.</p>
HTML,
                'featured_image' => 'storage/blogs/blog-6-yirgacheffe-origin-expedition.jpg',
                'tags' => ['yirgacheffe', 'terroir', 'high-altitude', 'specialty-coffee', 'arabica', 'origin'],
                'reading_time_minutes' => 5,
                'is_published' => true,
                'published_at' => now()->subDays(25),
                'meta' => [
                    'title' => 'Yirgacheffe Terroir, Elevation & Specialty Arabica Flavor Profiles',
                    'description' => 'Explore what makes Yirgacheffe coffee world-famous: volcanic soils, indigenous shade trees, extreme elevation, and ancient heirloom landraces.',
                ],
            ],
        ];

        // Clear existing legacy generic posts
        BlogPost::query()->delete();

        foreach ($posts as $postData) {
            $postData['excerpt'] ??= Str::limit(strip_tags($postData['content']), 240);

            $categorySlug = Str::slug($postData['category'] ?? '');
            $categoryId = $categorySlug !== '' ? Arr::get($categoryLookup, $categorySlug) : null;

            BlogPost::updateOrCreate(
                ['slug' => $postData['slug']],
                array_merge($postData, ['blog_category_id' => $categoryId])
            );
        }

        // Add engaging real-world industry comments to the first post
        $firstPost = BlogPost::where('slug', 'future-of-coffee-trade-fira-link-ethiopian-producers-global-buyers')->first();

        if ($firstPost && $firstPost->comments()->doesntExist()) {
            $createdComments = $firstPost->comments()->createMany([
                [
                    'author_name' => 'Michael Chen',
                    'author_email' => 'michael@nordicorigins.coffee',
                    'author_website' => 'https://nordicorigins.coffee',
                    'body' => 'This is a tremendous milestone for direct trade transparency. We purchased two containers of Grade 1 Sidama through Fira Link this season, and having pre-shipment cupping sheets alongside farm-gate payment verification gives us complete confidence with our retail customers in Copenhagen.',
                    'is_approved' => true,
                    'ip_address' => '127.0.0.1',
                ],
                [
                    'author_name' => 'Sara Alemseged',
                    'author_email' => 'sara.a@bensacoffee.org',
                    'author_website' => null,
                    'body' => 'As a washing station operator in Bensa, Fira Link has made communicating with overseas specialty buyers seamless. We are proud to see our farmers recognized for their dedication to cherry maturity and raised bed management.',
                    'is_approved' => true,
                    'ip_address' => '127.0.0.1',
                ],
            ]);

            if ($createdComments->isNotEmpty()) {
                $firstComment = $createdComments->first();

                BlogComment::create([
                    'blog_post_id' => $firstPost->id,
                    'parent_id' => $firstComment->id,
                    'author_name' => 'Tewodros Mengistu',
                    'author_email' => 'tewodros@firalink.com',
                    'author_website' => null,
                    'body' => 'Thank you Michael! Our mission is to make direct Ethiopian specialty coffee sourcing as transparent and accessible as possible. Looking forward to our cupping session next week!',
                    'is_approved' => true,
                    'ip_address' => '127.0.0.1',
                ]);
            }
        }
    }
}
