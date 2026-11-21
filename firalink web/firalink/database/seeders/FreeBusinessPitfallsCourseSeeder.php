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
        $instructor = User::firstOrCreate(
            ['email' => 'academy@fira-link.com'],
            [
                'name' => 'Fira Link Academy',
                'password' => Hash::make(Str::random(48)),
                'role' => 'instructor',
                'is_approved' => true,
                'email_verified_at' => now(),
                'bio' => 'Fira Link platform learning resources.',
            ]
        );

        $instructorRole = Role::where('name', 'instructor')->first();
        if ($instructorRole && ! $instructor->hasRole($instructorRole->name)) {
            $instructor->assignRole($instructorRole);
        }
        $business = CourseCategory::where('slug', 'business')->firstOrFail();
        $english = CourseLanguage::where('name', 'English')->firstOrFail();

        $course = Course::updateOrCreate(
            ['title' => 'Avoiding Common Business Pitfalls: 3 Critical Mistakes and Solutions'],
            [
                'instructor_id' => $instructor->id,
                'description' => '<p>This foundational module identifies three frequently overlooked mistakes entrepreneurs make in business operations. Through practical analysis, learners refine their business models, improve market alignment, and boost operational efficiency.</p><p>It covers target-audience research, market-demand validation, and the difference between merely staying busy and doing high-impact work that drives revenue and growth.</p>',
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
                'requirements' => '<p>No prerequisites. Suitable for new entrepreneurs, small-business owners, and startup founders.</p>',
                'objectives' => '<ul><li>Assess current practices against three common business pitfalls.</li><li>Implement research-based audience engagement strategies.</li><li>Apply analytical methods to confirm market demand.</li><li>Prioritize high-efficiency daily workflows.</li></ul>',
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
                'description' => '<p>This introductory module outlines five practical, low-cost ventures that can be launched with minimal initial capital. Learners explore digital commerce and service businesses, then learn how to structure, manage, and market a lean business model.</p>',
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
                'requirements' => '<p>No prerequisites. Suitable for aspiring entrepreneurs, freelancers, and small-business starters.</p>',
                'objectives' => '<ul><li>Identify a suitable business model based on personal skills and resources.</li><li>Understand the requirements for a low-capital startup.</li><li>Apply basic operational and marketing strategies.</li><li>Evaluate the feasibility of a business with limited capital.</li></ul>',
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
                'description' => '<p>This module covers the foundational steps for launching a coffee business in Ethiopia, from business planning and supply-chain management to sales strategies and sustainable profit growth.</p><p>Learners gain insight into local market dynamics, document preparation, and effective positioning of coffee products.</p>',
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
                'requirements' => '<p>No prerequisites. Suitable for aspiring entrepreneurs, local business owners, and coffee-industry enthusiasts.</p>',
                'objectives' => '<ul><li>Identify core legal and operational requirements for a coffee venture.</li><li>Implement quality-control and supply-chain practices.</li><li>Develop a strategic sales plan for target consumers.</li><li>Analyze operations for sustainable profitability.</li></ul>',
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
                'description' => '<p>This module introduces essential principles for financial success through a disciplined mindset and a strategic approach to business. It explores dedication, hard work, and consistency as foundations for sustainable wealth creation.</p><p>Learners evaluate their current habits and apply a framework that focuses energy on high-impact work.</p>',
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
                'requirements' => '<p>No prerequisites. Suitable for aspiring entrepreneurs and individuals seeking financial independence.</p>',
                'objectives' => '<ul><li>Define mindset traits that support financial growth.</li><li>Apply consistency to daily business or personal tasks.</li><li>Identify distractions and refocus on high-priority goals.</li></ul>',
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
                'description' => '<p>This module outlines the foundational steps for launching a sustainable pool house business. It provides a practical breakdown of startup capital, location selection, and revenue management.</p><p>Learners explore high-traffic demographics and complementary services that can support long-term profitability with manageable ongoing expenses.</p>',
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
                'requirements' => '<p>No prerequisites. Suitable for aspiring entrepreneurs and small-business starters in Ethiopia.</p>',
                'objectives' => '<ul><li>Assess pool-house feasibility using local demographics.</li><li>Estimate startup capital and an operational budget.</li><li>Identify complementary revenue opportunities.</li><li>Plan a high-margin service business for long-term operation.</li></ul>',
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
                'description' => '<p>This module gives an end-to-end overview of Ethiopia’s coffee export supply chain, following the journey from coffee-growing areas in Sidama and Guji to processing facilities in Addis Ababa.</p><p>Learners explore producers, suppliers, exporters, preparation, processing, logistics, and international quality standards.</p>',
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
                'requirements' => '<p>No prerequisites. Suitable for aspiring entrepreneurs, coffee-industry beginners, and trade enthusiasts.</p>',
                'objectives' => '<ul><li>Describe the Ethiopian coffee export value chain.</li><li>Recognize the roles of farmers, suppliers, and exporters.</li><li>Identify requirements for export-ready coffee.</li><li>Evaluate entry into the coffee export market.</li></ul>',
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
                'description' => '<p>This module outlines a practical, low-cost tutoring-service model, from solo student entrepreneurship to building a scalable tutoring agency. It focuses on social-media marketing, parent trust, and team management.</p><p>The lesson presents pricing and growth strategies as educational examples; results depend on local demand, execution, costs, and other factors.</p>',
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
                'requirements' => '<p>No prerequisites. Suitable for students, job seekers, and aspiring entrepreneurs.</p>',
                'objectives' => '<ul><li>Launch a tutoring service with minimal capital.</li><li>Promote services through social media and local outreach.</li><li>Implement a team-based operational structure.</li><li>Design a pricing structure that supports sustainable growth.</li></ul>',
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
                'description' => '<p>This module provides a roadmap for entering Ethiopia’s coffee export market, covering legal, administrative, and strategic requirements from business concept to active exportation.</p><p>It introduces regulatory orientation, sourcing, customer acquisition, and financial planning. Learners should confirm current requirements with the relevant authorities and qualified advisers before acting.</p>',
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
                'requirements' => '<p>No prerequisites. Suitable for aspiring coffee exporters, small-business owners, and agri-preneurs.</p>',
                'objectives' => '<ul><li>Understand the process for pursuing a coffee export licence in Ethiopia.</li><li>Identify and qualify potential international Arabica coffee buyers.</li><li>Recognize processing and shipping logistics requirements.</li><li>Estimate startup capital and operational costs for an export business.</li></ul>',
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
                'description' => '<p>This module covers the documentation and logistical frameworks required to export coffee internationally, including shipping terms, certification processes, and paperwork for global markets.</p><p>Learners gain practical orientation to product compliance, quality control, and customs-clearance preparation. Requirements vary by destination and must be verified with current authorities and trade professionals.</p>',
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
                'requirements' => '<p>No prerequisites. Suitable for aspiring exporters, coffee-business owners, and logistics professionals.</p>',
                'objectives' => '<ul><li>Identify core coffee-export documentation.</li><li>Explain common shipping incoterms and their risk and cost implications.</li><li>Determine quality and safety certifications for target markets.</li><li>Organize paperwork for the journey from warehouse to port.</li></ul>',
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
                'description' => '<p>This module presents seven practical strategies for Ethiopian entrepreneurs to identify and connect with international buyers. It focuses on export-oriented sectors such as coffee and agriculture, combining B2B platforms, trade fairs, professional networks, and digital tools.</p><p>Learners build a practical roadmap for expanding their reach into global markets and developing sustainable export relationships.</p>',
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
                'requirements' => '<p>No prerequisites. Suitable for Ethiopian entrepreneurs, exporters, and small-business owners.</p>',
                'objectives' => '<ul><li>Select digital and physical channels for reaching target export markets.</li><li>Use trade organizations to grow a professional network.</li><li>Apply digital marketing and SEO for international visibility.</li><li>Build relationships with credible brokers and agents.</li></ul>',
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
                'description' => '<p>This module compares Arabica and Robusta, the two dominant coffee species in the global market. It examines their origins, physical characteristics, chemical composition, and market positioning.</p><p>Learners explore why Ethiopia is associated with Arabica and how variety knowledge supports coffee-export and trading decisions.</p>',
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
                'requirements' => '<p>No prerequisites. Suitable for coffee exporters, agricultural entrepreneurs, and coffee-industry professionals.</p>',
                'objectives' => '<ul><li>Distinguish Arabica and Robusta using physical and chemical traits.</li><li>Evaluate demand for coffee varieties in export markets.</li><li>Understand geographical and environmental cultivation requirements.</li><li>Assess the economic potential of Ethiopian Arabica.</li></ul>',
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
