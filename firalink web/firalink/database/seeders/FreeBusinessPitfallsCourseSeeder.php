<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseLanguage;
use App\Models\CourseTopic;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class FreeBusinessPitfallsCourseSeeder extends Seeder
{
    /**
     * Seed Fira Link's platform-owned, single-lesson free course.
     */
    public function run(): void
    {
        $instructor = User::updateOrCreate(
            ['email' => 'academy@fira-link.com'],
            [
                'name' => 'Firaol Kebede',
                'password' => Hash::make(Str::random(48)),
                'role' => 'instructor',
                'is_approved' => true,
                'email_verified_at' => now(),
                'bio' => 'Lead Trade Mentor & Export Practitioner at Fira Link Platform.',
            ]
        );

        $instructorRole = Role::where('name', 'instructor')->first();
        if ($instructorRole && ! $instructor->hasRole($instructorRole->name)) {
            $instructor->assignRole($instructorRole);
        }
        $business = CourseCategory::firstOrCreate(
            ['slug' => 'business'],
            [
                'name' => 'Business',
                'description' => 'Business, entrepreneurship, and management learning resources.',
                'is_active' => true,
                'display_order' => 1,
            ]
        );
        $english = CourseLanguage::firstOrCreate(
            ['name' => 'English'],
            [
                'code' => 'en',
                'is_active' => true,
            ]
        );

        $course = Course::updateOrCreate(
            ['title' => 'Avoiding Common Business Pitfalls: 3 Critical Mistakes and Solutions'],
            [
                'instructor_id' => $instructor->id,
                'description' => '<p>Building a resilient enterprise requires understanding the operational vulnerabilities that cause new ventures to stall. This course examines the three most frequent structural mistakes made by entrepreneurs: operating without precise target audience segmentation, investing capital before validating commercial demand, and confusing superficial daily activity with high-leverage revenue drivers.</p><p>Designed for startup founders, small-business operators, and trade entrepreneurs, this program provides actionable frameworks to de-risk operations, validate willingness-to-pay, and maintain capital efficiency from inception.</p>',
                'featured_image' => 'https://i.ytimg.com/vi/StVAeC2-N1M/maxresdefault.jpg',
                'visibility' => 'public',
                'public_course' => true,
                'difficulty' => 'beginner',
                'pricing_model' => 'free',
                'regular_price' => 0,
                'sale_price' => 0,
                'course_category_id' => $business->id,
                'course_language_id' => $english->id,
                'category' => 'Entrepreneurship / Business Management',
                'language' => 'English',
                'tags' => 'entrepreneurship, business management, target audience, market validation, productivity',
                'requirements' => '<p>No prior business experience required. Suitable for aspiring entrepreneurs, early-stage startup founders, and small-business owners.</p>',
                'objectives' => '<ul><li>Identify and diagnose the three critical failure patterns in early-stage business execution.</li><li>Develop precise buyer personas and target audience criteria to eliminate marketing waste.</li><li>Execute low-cost demand validation experiments before committing capital to inventory or facilities.</li><li>Design daily operational workflows centered on high-leverage, revenue-generating activities.</li><li>Establish objective feedback loops and customer metrics to detect performance bottlenecks early.</li></ul>',
                'status' => 'published',
                'approval_reason' => 'Platform-owned free course',
                'rejection_reason' => null,
                'is_live_course' => false,
            ]
        );

        $topic = CourseTopic::updateOrCreate(
            ['course_id' => $course->id, 'order' => 1],
            [
                'title' => 'Three critical business mistakes',
                'description' => 'Audience definition, market-demand validation, and efficient execution.',
            ]
        );

        Lesson::updateOrCreate(
            ['course_topic_id' => $topic->id, 'order' => 1],
            [
                'title' => 'Avoiding common business pitfalls',
                'description' => 'Learn how to define your audience, validate genuine demand, and focus on effective work.',
                'video_type' => 'url',
                'video_url' => 'https://www.youtube.com/embed/StVAeC2-N1M',
                // The existing schema records duration in whole minutes; the source video is 8:16.
                'duration' => 8,
                'is_preview' => false,
                'is_live' => false,
            ]
        );

        $lowCapitalCourse = Course::updateOrCreate(
            ['title' => '5 Accessible Business Ideas with Low Starting Capital'],
            [
                'instructor_id' => $instructor->id,
                'description' => '<p>Capital scarcity should not prevent ambitious individuals from building profitable enterprises. This practical masterclass breaks down five viable, low-risk business models that require minimal startup expenditure: digital commerce, value-added packaging and spices, brand financing, targeted professional service agencies, and commercial cleaning operations.</p><p>Tailored for first-time founders, freelancers, and enterprising professionals, the course outlines unit economics, market positioning, and lean operating principles required to generate positive cash flow without external loans or debt.</p>',
                'featured_image' => 'https://i.ytimg.com/vi/DgOkEx-IKgA/maxresdefault.jpg',
                'visibility' => 'public',
                'public_course' => true,
                'difficulty' => 'beginner',
                'pricing_model' => 'free',
                'regular_price' => 0,
                'sale_price' => 0,
                'course_category_id' => $business->id,
                'course_language_id' => $english->id,
                'category' => 'Entrepreneurship / Small Business Development',
                'language' => 'English',
                'tags' => 'entrepreneurship, small business, low capital, e-commerce, services',
                'requirements' => '<p>No capital prerequisites or specialized degrees needed. Accessible to any committed entrepreneur, freelancer, or job seeker.</p>',
                'objectives' => '<ul><li>Evaluate five battle-tested low-capital business models based on personal skills and market opportunity.</li><li>Calculate accurate startup overhead, break-even thresholds, and operating margins.</li><li>Launch direct-to-consumer and business-to-business distribution using accessible digital channels.</li><li>Implement lean procurement strategies to preserve working capital while scaling sales volume.</li><li>Reinvest early operating profits systematically to expand service capacity and build financial resilience.</li></ul>',
                'status' => 'published',
                'approval_reason' => 'Platform-owned free course',
                'rejection_reason' => null,
                'is_live_course' => false,
            ]
        );

        $lowCapitalTopic = CourseTopic::updateOrCreate(
            ['course_id' => $lowCapitalCourse->id, 'order' => 1],
            ['title' => 'Five low-capital business ideas', 'description' => 'Practical ventures that can be started with limited initial investment.']
        );

        Lesson::updateOrCreate(
            ['course_topic_id' => $lowCapitalTopic->id, 'order' => 1],
            [
                'title' => '5 accessible business ideas with low starting capital',
                'description' => 'Explore online retailing, packing and spices, brand financing, service provision, and cleaning businesses.',
                'video_type' => 'url',
                'video_url' => 'https://www.youtube.com/embed/DgOkEx-IKgA',
                // The source video is 5:50; duration is stored as whole minutes.
                'duration' => 6,
                'is_preview' => false,
                'is_live' => false,
            ]
        );

        $coffeeCourse = Course::updateOrCreate(
            ['title' => 'Starting a Profitable Coffee Business in Ethiopia: A Step-by-Step Guide'],
            [
                'instructor_id' => $instructor->id,
                'description' => '<p>Ethiopia represents one of the world\'s most dynamic and culturally rooted coffee environments, offering tremendous commercial potential for entrepreneurs who understand how to structure a modern coffee venture. This comprehensive course guides participants through launching a profitable domestic coffee enterprise, covering regulatory licensing, direct farmer-cooperative sourcing, roasting standards, packaging design, and multi-channel retail distribution.</p><p>Aimed at emerging coffee entrepreneurs, café proprietors, roasters, and value-chain investors, this training details the exact operational methodologies needed to build a trusted, compliant, and sustainable brand in the Ethiopian marketplace.</p>',
                'featured_image' => 'https://i.ytimg.com/vi/2X-5w_W_G1w/maxresdefault.jpg',
                'visibility' => 'public',
                'public_course' => true,
                'difficulty' => 'beginner',
                'pricing_model' => 'free',
                'regular_price' => 0,
                'sale_price' => 0,
                'course_category_id' => $business->id,
                'course_language_id' => $english->id,
                'category' => 'Entrepreneurship / Small Business Development',
                'language' => 'English',
                'tags' => 'coffee business, Ethiopia, entrepreneurship, supply chain, sales',
                'requirements' => '<p>No specialized coffee background required. Ideal for aspiring café founders, coffee roasters, retailers, and agribusiness entrepreneurs.</p>',
                'objectives' => '<ul><li>Navigate municipal business licensing, health certifications, and commercial food-handling compliance in Ethiopia.</li><li>Establish reliable direct-supply relationships with regional washing and drying stations.</li><li>Calculate accurate green-to-roasted batch economics, roasting shrinkage, and packaging overhead.</li><li>Create an effective retail and wholesale marketing strategy tailored to Ethiopian consumer preferences.</li><li>Implement rigorous quality inspection and moisture control to ensure consistent product standards.</li></ul>',
                'status' => 'published',
                'approval_reason' => 'Platform-owned free course',
                'rejection_reason' => null,
                'is_live_course' => false,
            ]
        );

        $coffeeTopic = CourseTopic::updateOrCreate(
            ['course_id' => $coffeeCourse->id, 'order' => 1],
            ['title' => 'Starting a coffee business in Ethiopia', 'description' => 'Business foundations, product management, sales, and profitability.']
        );

        Lesson::updateOrCreate(
            ['course_topic_id' => $coffeeTopic->id, 'order' => 1],
            [
                'title' => 'Starting a profitable coffee business in Ethiopia',
                'description' => 'A practical roadmap covering documentation, the coffee value chain, sales, marketing, and profit growth.',
                'video_type' => 'url',
                'video_url' => 'https://www.youtube.com/embed/2X-5w_W_G1w',
                // The source video is 7:09; duration is stored as whole minutes.
                'duration' => 7,
                'is_preview' => false,
                'is_live' => false,
            ]
        );

        $personalFinance = CourseCategory::firstOrCreate(
            ['slug' => 'personal-finance'],
            [
                'name' => 'Personal Finance',
                'description' => 'Financial literacy, wealth-building habits, and entrepreneurship.',
                'is_active' => true,
                'display_order' => 5,
            ]
        );

        $wealthCourse = Course::updateOrCreate(
            ['title' => 'The Secrets to Becoming Rich Quickly: Simple Ways to Success'],
            [
                'instructor_id' => $instructor->id,
                'description' => '<p>Sustainable financial independence is not built on speculation or shortcuts, but on mastering capital discipline, asymmetric skill acquisition, and relentless execution consistency. This program breaks down the fundamental economic and psychological pillars that separate high-earners and generational wealth-builders from those trapped in financial stagnation.</p><p>Crafted for ambitious professionals, founders, and self-employed operators, this course equips participants with practical tools to cultivate high-income capabilities, eliminate financial leaks, automate long-term asset building, and align daily actions with compounding financial returns.</p>',
                'featured_image' => 'https://i.ytimg.com/vi/DXglxmxaR7o/maxresdefault.jpg',
                'visibility' => 'public',
                'public_course' => true,
                'difficulty' => 'beginner',
                'pricing_model' => 'free',
                'regular_price' => 0,
                'sale_price' => 0,
                'course_category_id' => $personalFinance->id,
                'course_language_id' => $english->id,
                'category' => 'Personal Finance / Entrepreneurship',
                'language' => 'English',
                'tags' => 'personal finance, entrepreneurship, financial growth, consistency, productivity',
                'requirements' => '<p>An open mindset and commitment to financial self-discipline. No prior investing or financial expertise required.</p>',
                'objectives' => '<ul><li>Shift psychological perspective from short-term consumer thinking to long-term owner-investor capital allocation.</li><li>Identify and master monetizable, high-leverage skills that command above-market compensation.</li><li>Implement automated savings, debt-containment, and capital preservation systems.</li><li>Eliminate low-value distractions and structure daily routines around high-impact economic activities.</li><li>Develop a multi-year personal financial roadmap with verifiable milestones and performance metrics.</li></ul>',
                'status' => 'published',
                'approval_reason' => 'Platform-owned free course',
                'rejection_reason' => null,
                'is_live_course' => false,
            ]
        );

        $wealthTopic = CourseTopic::updateOrCreate(
            ['course_id' => $wealthCourse->id, 'order' => 1],
            ['title' => 'Mindset and habits for financial growth', 'description' => 'Dedication, consistency, and strategic focus.']
        );

        Lesson::updateOrCreate(
            ['course_topic_id' => $wealthTopic->id, 'order' => 1],
            [
                'title' => 'Simple ways to build lasting success',
                'description' => 'A practical lesson on commitment, consistent effort, and focusing on high-impact tasks.',
                'video_type' => 'url',
                'video_url' => 'https://www.youtube.com/embed/DXglxmxaR7o',
                // The source video is 10:53; duration is stored as whole minutes.
                'duration' => 11,
                'is_preview' => false,
                'is_live' => false,
            ]
        );

        $poolHouseCourse = Course::updateOrCreate(
            ['title' => 'Starting a Profitable Pool House Business: A Step-by-Step Guide'],
            [
                'instructor_id' => $instructor->id,
                'description' => '<p>Recreational and entertainment facilities represent resilient commercial opportunities in urban and peri-urban centers when planned with analytical precision. This practical guide covers every stage of establishing and operating a profitable commercial billiard and entertainment lounge, including site selection, foot-traffic analysis, equipment sourcing, security management, and integrated concession sales.</p><p>Tailored for hospitality entrepreneurs, property owners, and local investors, this course provides clear operational playbooks to maximize table utilization rates, minimize equipment depreciation, and build a safe, community-oriented social hub.</p>',
                'featured_image' => 'https://i.ytimg.com/vi/eGC3XMGactc/maxresdefault.jpg',
                'visibility' => 'public',
                'public_course' => true,
                'difficulty' => 'beginner',
                'pricing_model' => 'free',
                'regular_price' => 0,
                'sale_price' => 0,
                'course_category_id' => $business->id,
                'course_language_id' => $english->id,
                'category' => 'Entrepreneurship / Small Business Management',
                'language' => 'English',
                'tags' => 'pool house, entrepreneurship, small business, hospitality, Ethiopia',
                'requirements' => '<p>No hospitality experience needed. Geared toward prospective business owners, property managers, and retail operators.</p>',
                'objectives' => '<ul><li>Conduct demographic surveys and pedestrian traffic evaluations to select high-yield commercial rental spaces.</li><li>Itemize capital expenditure for tournament-grade slate tables, specialized lighting, flooring, and interior fit-outs.</li><li>Diversify revenue through hourly rentals, competitive league memberships, and beverage/snack concessions.</li><li>Establish strict cash handling, shift auditing, and inventory control protocols to safeguard daily proceeds.</li><li>Implement safety measures, dispute de-escalation protocols, and positive customer service standards.</li></ul>',
                'status' => 'published',
                'approval_reason' => 'Platform-owned free course',
                'rejection_reason' => null,
                'is_live_course' => false,
            ]
        );

        $poolHouseTopic = CourseTopic::updateOrCreate(
            ['course_id' => $poolHouseCourse->id, 'order' => 1],
            ['title' => 'Launching a pool house business', 'description' => 'Location, capital, operations, and revenue diversification.']
        );

        Lesson::updateOrCreate(
            ['course_topic_id' => $poolHouseTopic->id, 'order' => 1],
            [
                'title' => 'Starting a profitable pool house business',
                'description' => 'A practical guide to location research, capital planning, daily operations, and complementary services.',
                'video_type' => 'url',
                'video_url' => 'https://www.youtube.com/embed/eGC3XMGactc',
                // The source video is 5:43; duration is stored as whole minutes.
                'duration' => 6,
                'is_preview' => false,
                'is_live' => false,
            ]
        );

        $internationalTrade = CourseCategory::firstOrCreate(
            ['slug' => 'international-trade'],
            [
                'name' => 'International Trade',
                'description' => 'Export, import, and international-market learning resources.',
                'is_active' => true,
                'display_order' => 6,
            ]
        );

        $coffeeExportCourse = Course::updateOrCreate(
            ['title' => 'Coffee Export Business: From Farm to Global Market'],
            [
                'instructor_id' => $instructor->id,
                'description' => '<p>Ethiopia\'s position as the historical birthplace of Arabica coffee provides an unmatched foundation for international commodity trade, yet navigating the export value chain demands deep technical and logistical competence. This comprehensive course charts the complete trajectory of specialty and commercial coffee, from high-altitude cultivation zones in Sidama, Yirgacheffe, and Guji through washing stations, dry milling operations, Ethiopian Commodity Exchange (ECX) protocols, and sea freight loading.</p><p>Designed for prospective exporters, international trade managers, agribusiness professionals, and foreign buyers, this module demystifies origin-to-port supply dynamics, processing methodologies, export grading, and global buyer expectations.</p>',
                'featured_image' => 'https://i.ytimg.com/vi/Qxyqv3c_2xc/maxresdefault.jpg',
                'visibility' => 'public',
                'public_course' => true,
                'difficulty' => 'beginner',
                'pricing_model' => 'free',
                'regular_price' => 0,
                'sale_price' => 0,
                'course_category_id' => $internationalTrade->id,
                'course_language_id' => $english->id,
                'category' => 'International Trade / Entrepreneurship',
                'language' => 'English',
                'tags' => 'coffee export, Ethiopia, international trade, supply chain, entrepreneurship',
                'requirements' => '<p>General interest in global trade and agribusiness. Beneficial for prospective exporters, coffee brokers, and trade professionals.</p>',
                'objectives' => '<ul><li>Trace the physical and commercial flow of coffee from primary producers and washing stations to international dispatch ports.</li><li>Understand the legal roles and interactions between outgrowers, washing station owners, suppliers, and licensed exporters.</li><li>Distinguish washed versus natural processing methods and their impact on physical cup quality and international price differentials.</li><li>Evaluate statutory quality inspection, moisture thresholds, and export defect tolerances under Ethiopian regulatory authorities.</li><li>Plan efficient multimodal logistics and containerized transit corridors through Djibouti port.</li></ul>',
                'status' => 'published',
                'approval_reason' => 'Platform-owned free course',
                'rejection_reason' => null,
                'is_live_course' => false,
            ]
        );

        $coffeeExportTopic = CourseTopic::updateOrCreate(
            ['course_id' => $coffeeExportCourse->id, 'order' => 1],
            ['title' => 'Coffee from farm to global market', 'description' => 'Export landscape, supply chain, preparation, standards, and logistics.']
        );

        Lesson::updateOrCreate(
            ['course_topic_id' => $coffeeExportTopic->id, 'order' => 1],
            [
                'title' => 'Coffee export business: from farm to global market',
                'description' => 'A foundation in Ethiopia’s coffee export value chain and international-market preparation.',
                'video_type' => 'url',
                'video_url' => 'https://www.youtube.com/embed/Qxyqv3c_2xc',
                // The source video is 10:14; duration is stored as whole minutes.
                'duration' => 10,
                'is_preview' => false,
                'is_live' => false,
            ]
        );

        $sideHustle = CourseCategory::firstOrCreate(
            ['slug' => 'side-hustle'],
            [
                'name' => 'Side Hustle',
                'description' => 'Practical service businesses and low-capital income opportunities.',
                'is_active' => true,
                'display_order' => 7,
            ]
        );

        $tutoringCourse = Course::updateOrCreate(
            ['title' => 'Starting a Low-Capital Tutoring Business: Scaling from 500 Birr to 100,000+ Birr Monthly'],
            [
                'instructor_id' => $instructor->id,
                'description' => '<p>Educational service agencies are among the most accessible high-margin service enterprises for knowledge workers and ambitious university graduates. This masterclass illustrates the exact operational roadmap to evolve from an individual, hourly home tutor earning modest stipends into a professional, scalable tutoring agency coordinating dozens of vetted instructors across private households and international school learners.</p><p>Aimed at educators, ambitious graduates, and service business starters, the curriculum focuses on parent trust building, structured pedagogical assessment, digital customer acquisition, tutor contractor management, and recurring monthly retainers.</p>',
                'featured_image' => 'https://i.ytimg.com/vi/BrA56vrGNIc/maxresdefault.jpg',
                'visibility' => 'public',
                'public_course' => true,
                'difficulty' => 'beginner',
                'pricing_model' => 'free',
                'regular_price' => 0,
                'sale_price' => 0,
                'course_category_id' => $sideHustle->id,
                'course_language_id' => $english->id,
                'category' => 'Entrepreneurship / Side Hustle / Service Business',
                'language' => 'English',
                'tags' => 'tutoring, side hustle, service business, entrepreneurship, social media marketing',
                'requirements' => '<p>Strong communication skills and basic academic literacy. No substantial startup capital or commercial real estate required.</p>',
                'objectives' => '<ul><li>Structure competitive academic tutoring packages that solve urgent parent pain points and support premium pricing.</li><li>Deploy organic social media marketing and neighborhood referral programs to acquire qualified family leads without paid ads.</li><li>Develop standardized screening, background verification, and pedagogical onboarding frameworks for hiring sub-tutors.</li><li>Implement agency contracts, lesson verification logs, and commission-split agreements to ensure reliable client delivery.</li><li>Transition client payments from irregular hourly billing into automated, recurring monthly subscriptions.</li></ul>',
                'status' => 'published',
                'approval_reason' => 'Platform-owned free course',
                'rejection_reason' => null,
                'is_live_course' => false,
            ]
        );

        $tutoringTopic = CourseTopic::updateOrCreate(
            ['course_id' => $tutoringCourse->id, 'order' => 1],
            ['title' => 'Building and scaling a tutoring service', 'description' => 'Lean startup, marketing, operations, and financial planning.']
        );

        Lesson::updateOrCreate(
            ['course_topic_id' => $tutoringTopic->id, 'order' => 1],
            [
                'title' => 'Starting a low-capital tutoring business',
                'description' => 'A practical roadmap for launching, marketing, operating, and scaling a tutoring service.',
                'video_type' => 'url',
                'video_url' => 'https://www.youtube.com/embed/BrA56vrGNIc',
                // The source video is 12:25; duration is stored as whole minutes.
                'duration' => 13,
                'is_preview' => false,
                'is_live' => false,
            ]
        );

        $coffeeExportRequirementsCourse = Course::updateOrCreate(
            ['title' => 'Navigating the Ethiopian Coffee Export Industry: Procedures and Requirements'],
            [
                'instructor_id' => $instructor->id,
                'description' => '<p>Entering the Ethiopian coffee export sector requires navigating rigorous regulatory frameworks established by the Ethiopian Coffee and Tea Authority (ECTA), Ministry of Trade and Regional Integration (MOTRI), and National Bank of Ethiopia (NBE). This course delivers a step-by-step regulatory and strategic blueprint, guiding new and prospective trade entrants through competency certification, minimum capital benchmarks, foreign exchange retention laws, and statutory quality clearances.</p><p>Crucial for prospective exporters, commercial farm owners, returning diaspora investors, and commodity trading executives, this program equips participants with legal clarity and procedural certainty to prevent costly bureaucratic delays and non-compliance penalties.</p>',
                'featured_image' => 'https://i.ytimg.com/vi/8pGf32RLVVc/maxresdefault.jpg',
                'visibility' => 'public',
                'public_course' => true,
                'difficulty' => 'beginner',
                'pricing_model' => 'free',
                'regular_price' => 0,
                'sale_price' => 0,
                'course_category_id' => $internationalTrade->id,
                'course_language_id' => $english->id,
                'category' => 'International Trade / Agri-business',
                'language' => 'English',
                'tags' => 'coffee export, Ethiopia, international trade, agri-business, export requirements',
                'requirements' => '<p>Basic orientation to Ethiopian trade or commercial agriculture. Highly recommended for prospective export license applicants and business owners.</p>',
                'objectives' => '<ul><li>Complete the formal application workflow to secure a verified coffee export competency certificate from the ECTA.</li><li>Comply with statutory capital requirements, commercial registrations, and municipal trade authorizations.</li><li>Master National Bank of Ethiopia directives regarding foreign currency retention accounts, Letter of Credit rules, and export proceeds repatriation.</li><li>Submit pre-shipment green coffee lots to the ECTA Liquoring and Inspection Laboratory for grading and export clearance certificates.</li><li>Establish operational controls to fulfill mandatory minimum pricing directives and export contract registrations.</li></ul>',
                'status' => 'published',
                'approval_reason' => 'Platform-owned free course',
                'rejection_reason' => null,
                'is_live_course' => false,
            ]
        );

        $coffeeExportRequirementsTopic = CourseTopic::updateOrCreate(
            ['course_id' => $coffeeExportRequirementsCourse->id, 'order' => 1],
            ['title' => 'Procedures and requirements for coffee export', 'description' => 'Licensing, sourcing, marketing, customer acquisition, and capital planning.']
        );

        Lesson::updateOrCreate(
            ['course_topic_id' => $coffeeExportRequirementsTopic->id, 'order' => 1],
            [
                'title' => 'Navigating Ethiopia’s coffee export procedures and requirements',
                'description' => 'An introduction to licences, market sourcing, customer acquisition, and operating requirements.',
                'video_type' => 'url',
                'video_url' => 'https://www.youtube.com/embed/8pGf32RLVVc',
                // The source video is 20:01; duration is stored as whole minutes.
                'duration' => 20,
                'is_preview' => false,
                'is_live' => false,
            ]
        );

        $exportDocumentsCourse = Course::updateOrCreate(
            ['title' => 'Essential Documentation and Procedures for International Coffee Export'],
            [
                'instructor_id' => $instructor->id,
                'description' => '<p>In international trade, documentation precision determines whether a containerized cargo clears customs smoothly or incurs crippling port demurrage and banking rejections. This operational course details the mandatory legal, commercial, and transport documents governing Ethiopian coffee exports. Participants will analyze Incoterms 2020 obligations (FOB Djibouti, CIF, CFR), international sales contracts, Commercial Invoices, Packing Lists, Certificates of Origin (ICO and Ethiopian Chamber), Phytosanitary Certificates, and Ocean Bills of Lading.</p><p>Essential for export logistics officers, documentation specialists, freight forwarders, and commercial trading managers, this training ensures total alignment with international banking standards (UCP 600) and global destination customs requirements.</p>',
                'featured_image' => 'https://i.ytimg.com/vi/AtyMQVgz6xw/maxresdefault.jpg',
                'visibility' => 'public',
                'public_course' => true,
                'difficulty' => 'beginner',
                'pricing_model' => 'free',
                'regular_price' => 0,
                'sale_price' => 0,
                'course_category_id' => $internationalTrade->id,
                'course_language_id' => $english->id,
                'category' => 'International Trade / Export Management',
                'language' => 'English',
                'tags' => 'coffee export, export documents, incoterms, logistics, customs clearance',
                'requirements' => '<p>Basic understanding of business operations. Tailored for export documentation officers, logistics coordinators, and trade operators.</p>',
                'objectives' => '<ul><li>Draft comprehensive international coffee sales contracts incorporating standard dispute-resolution, quality tolerance, and force majeure clauses.</li><li>Apply Incoterms 2020 rules accurately to allocate risk, insurance liability, and multimodal shipping costs between buyer and seller.</li><li>Prepare complete commercial shipping documentation suites matching banking documentary collection and Letter of Credit requirements.</li><li>Acquire and verify mandatory sanitary credentials, including Ministry of Agriculture Phytosanitary Certificates and ICO Certificates of Origin.</li><li>Coordinate container stuffing, grain-pro desiccants, multimodal rail manifests, and carrier Bill of Lading releases without administrative discrepancies.</li></ul>',
                'status' => 'published',
                'approval_reason' => 'Platform-owned free course',
                'rejection_reason' => null,
                'is_live_course' => false,
            ]
        );

        $exportDocumentsTopic = CourseTopic::updateOrCreate(
            ['course_id' => $exportDocumentsCourse->id, 'order' => 1],
            ['title' => 'Coffee export documents and procedures', 'description' => 'Shipping terms, primary paperwork, and compliance certificates.']
        );

        Lesson::updateOrCreate(
            ['course_topic_id' => $exportDocumentsTopic->id, 'order' => 1],
            [
                'title' => 'Essential documentation for international coffee export',
                'description' => 'An introduction to incoterms, export documents, certifications, and customs-ready paperwork.',
                'video_type' => 'url',
                'video_url' => 'https://www.youtube.com/embed/AtyMQVgz6xw',
                // The source video is 18:03; duration is stored as whole minutes.
                'duration' => 18,
                'is_preview' => false,
                'is_live' => false,
            ]
        );

        $internationalBuyersCourse = Course::updateOrCreate(
            ['title' => 'Proven Strategies for Finding International Buyers'],
            [
                'instructor_id' => $instructor->id,
                'description' => '<p>Securing solvent, reputable foreign buyers is the single most critical milestone in establishing a profitable commodity export enterprise. This tactical course presents seven proven methodologies for African exporters and suppliers to locate, qualify, negotiate with, and retain international buyers across Europe, North America, the Middle East, and Asia. The curriculum explores specialized B2B trade platforms, premier global expos (SCA, World of Coffee, Gulfood), bilateral commercial trade missions, digital search intelligence, and structured direct outreach.</p><p>Built for export marketing directors, trading house founders, and cooperative marketing executives, this training delivers real-world customer acquisition frameworks designed to bypass opportunistic intermediaries and build high-value, long-term trade partnerships.</p>',
                'featured_image' => 'https://i.ytimg.com/vi/DzVcqrXyEFs/maxresdefault.jpg',
                'visibility' => 'public',
                'public_course' => true,
                'difficulty' => 'beginner',
                'pricing_model' => 'free',
                'regular_price' => 0,
                'sale_price' => 0,
                'course_category_id' => $internationalTrade->id,
                'course_language_id' => $english->id,
                'category' => 'Export Business / International Trade',
                'language' => 'English',
                'tags' => 'international buyers, export business, B2B platforms, trade fairs, SEO, Ethiopia',
                'requirements' => '<p>Basic knowledge of company offerings and export ambitions. No prior international travel or marketing background required.</p>',
                'objectives' => '<ul><li>Identify and profile high-intent foreign importers, roasters, and distributors using specialized international trade registries.</li><li>Develop high-converting corporate capability decks, digital product catalogs, and professional export sample presentation protocols.</li><li>Leverage international coffee trade fairs and expos (SCA, World of Coffee) to execute high-impact face-to-face buyer meetings.</li><li>Conduct thorough counterparty financial due-diligence and credit checks to prevent international payment default or fraud.</li><li>Negotiate sustainable pricing terms and structured trial shipment contracts that cultivate recurring seasonal orders.</li></ul>',
                'status' => 'published',
                'approval_reason' => 'Platform-owned free course',
                'rejection_reason' => null,
                'is_live_course' => false,
            ]
        );

        $internationalBuyersTopic = CourseTopic::updateOrCreate(
            ['course_id' => $internationalBuyersCourse->id, 'order' => 1],
            ['title' => 'Finding and connecting with international buyers', 'description' => 'Platforms, events, networks, digital marketing, and broker relationships.']
        );

        Lesson::updateOrCreate(
            ['course_topic_id' => $internationalBuyersTopic->id, 'order' => 1],
            [
                'title' => 'Proven strategies for finding international buyers',
                'description' => 'A practical overview of B2B platforms, trade fairs, digital marketing, and professional networks.',
                'video_type' => 'url',
                'video_url' => 'https://www.youtube.com/embed/DzVcqrXyEFs',
                // The source video is 18:18; duration is stored as whole minutes.
                'duration' => 18,
                'is_preview' => false,
                'is_live' => false,
            ]
        );

        $agriculture = CourseCategory::firstOrCreate(
            ['slug' => 'agriculture'],
            [
                'name' => 'Agriculture',
                'description' => 'Agriculture, coffee, and agri-business learning resources.',
                'is_active' => true,
                'display_order' => 8,
            ]
        );

        $coffeeVarietiesCourse = Course::updateOrCreate(
            ['title' => 'Understanding Coffee Varieties: Arabica vs. Robusta'],
            [
                'instructor_id' => $instructor->id,
                'description' => '<p>A deep scientific and commercial understanding of coffee botany is indispensable for anyone trading or evaluating green coffee on the world stage. This authoritative course compares the two primary botanical species of the genus Coffea: Coffea Arabica and Coffea Canephora (Robusta). The curriculum investigates their distinct chromosomal makeup, agronomic thresholds (altitude, rainfall, canopy shading, disease resistance), sensory characteristics, caffeine concentrations, and world commodity benchmark pricing.</p><p>Special emphasis is placed on Ethiopia\'s exclusive status as the indigenous genetic cradle of Arabica coffee, exploring how heirloom landraces, regional microclimates, and forest preservation create high-scoring cup profiles that command substantial price premiums in the international specialty marketplace.</p>',
                'featured_image' => 'https://i.ytimg.com/vi/oPxNNoC4Ioc/maxresdefault.jpg',
                'visibility' => 'public',
                'public_course' => true,
                'difficulty' => 'beginner',
                'pricing_model' => 'free',
                'regular_price' => 0,
                'sale_price' => 0,
                'course_category_id' => $agriculture->id,
                'course_language_id' => $english->id,
                'category' => 'Agriculture / Agribusiness / International Trade',
                'language' => 'English',
                'tags' => 'coffee varieties, Arabica, Robusta, Ethiopia, coffee export, agribusiness',
                'requirements' => '<p>Open to all coffee enthusiasts, agronomists, traders, baristas, and prospective export professionals. No botany prerequisites required.</p>',
                'objectives' => '<ul><li>Compare the botanical, chromosomal, and agronomic characteristics of Coffea Arabica versus Coffea Canephora (Robusta).</li><li>Analyze how elevation, temperature, soil composition, and shade canopy influence bean density, acidity, and sensory profile development.</li><li>Understand the global commercial landscape, New York "C" market pricing versus London Robusta terminal market benchmarks, and specialty differentials.</li><li>Evaluate Ethiopia’s unique genetic biodiversity, wild forest coffee reserves, and distinct regional terroir (Sidama, Yirgacheffe, Harar, Limu, Jimma).</li><li>Articulate variety and origin differentiators to international green buyers to negotiate premium specialty prices.</li></ul>',
                'status' => 'published',
                'approval_reason' => 'Platform-owned free course',
                'rejection_reason' => null,
                'is_live_course' => false,
            ]
        );

        $coffeeVarietiesTopic = CourseTopic::updateOrCreate(
            ['course_id' => $coffeeVarietiesCourse->id, 'order' => 1],
            ['title' => 'Arabica and Robusta coffee varieties', 'description' => 'Origins, characteristics, growing conditions, and market applications.']
        );

        Lesson::updateOrCreate(
            ['course_topic_id' => $coffeeVarietiesTopic->id, 'order' => 1],
            [
                'title' => 'Understanding Arabica versus Robusta',
                'description' => 'A comparison of coffee origins, flavour, caffeine, growing conditions, and market value.',
                'video_type' => 'url',
                'video_url' => 'https://www.youtube.com/embed/oPxNNoC4Ioc',
                // The source video is 10:50; duration is stored as whole minutes.
                'duration' => 11,
                'is_preview' => false,
                'is_live' => false,
            ]
        );
    }
}
