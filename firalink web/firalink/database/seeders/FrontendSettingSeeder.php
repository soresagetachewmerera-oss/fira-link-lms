<?php

namespace Database\Seeders;

use App\Models\FrontendSetting;
use Illuminate\Database\Seeder;

class FrontendSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FrontendSetting::setValue('footer.branding', [
            'about_text' => 'Fira Link connects Ethiopian coffee producers, verified exporters, and global buyers through AI export tooling, origin education, and direct trade.',
            'tagline' => 'Bridging Ethiopian Specialty Coffee to the Global Market.',
            'copyright' => '© ' . date('Y') . ' Fira Link. All rights reserved.',
        ]);

        FrontendSetting::setValue('footer.contact', [
            'email' => 'hello@fira-link.com',
            'phone' => '+251 91 123 4567',
            'address' => 'Bole Sub-City, Addis Ababa, Ethiopia',
            'hours' => 'Mon - Fri: 8:30am - 5:30pm EAT',
        ]);

        FrontendSetting::setValue('footer.social_links', [
            [
                'label' => 'Follow on Facebook',
                'url' => 'https://facebook.com/firalink',
                'icon' => 'fab fa-facebook-f',
            ],
            [
                'label' => 'Follow on Twitter',
                'url' => 'https://twitter.com/firalink',
                'icon' => 'fab fa-twitter',
            ],
            [
                'label' => 'Connect on LinkedIn',
                'url' => 'https://linkedin.com/company/firalink',
                'icon' => 'fab fa-linkedin-in',
            ],
        ]);

        FrontendSetting::setValue('pages.faq.items', [
            [
                'question' => 'What courses do you offer?',
                'answer' => '<p>We provide a diverse catalog covering technology, design, business, and creative skills. Each course is curated and taught by experienced instructors.</p>',
            ],
            [
                'question' => 'Can I learn at my own pace?',
                'answer' => '<p>Yes. All on-demand courses are self-paced so you can study whenever it fits your schedule while keeping track of your progress.</p>',
            ],
            [
                'question' => 'Do you offer certificates of completion?',
                'answer' => '<p>Every paid course includes a downloadable certificate demonstrating the skills you have acquired once you pass the final assessment.</p>',
            ],
            [
                'question' => 'How can I get support if I have questions?',
                'answer' => '<p>You can reach our support team anytime via email or live chat. Instructors and peers are also available in the course discussion boards.</p>',
            ],
        ]);

        FrontendSetting::setValue('pages.seo.meta', [
            'home' => [
                'title' => 'Fira Link - Ethiopian Specialty Coffee Marketplace & AI Trade Studio',
                'description' => 'Connect directly with verified Ethiopian coffee exporters and cooperatives. Access our AI Export Documentation Studio, origin expeditions, and coffee academy.',
            ],
            'courses' => [
                'title' => 'Specialty Coffee Academy',
                'description' => 'Explore certified courses in Q-Grader preparation, coffee processing, roasting science, and export trade compliance.',
            ],
            'faq' => [
                'title' => 'Frequently Asked Questions',
                'description' => 'Find answers to popular questions about Fira Link B2B trade, export documentation, cupping protocols, origin tours, and certifications.',
            ],
            'about' => [
                'title' => 'About Fira Link',
                'description' => 'Discover Fira Link’s mission to digitize Ethiopian specialty coffee trade, empower local producers, and ensure global regulatory compliance.',
            ],
            'events' => [
                'title' => 'Origin Expeditions & Trade Events',
                'description' => 'Explore upcoming cupping sessions, harvest tours, washing station visits, and international specialty coffee trade workshops.',
            ],
            'instructors' => [
                'title' => 'Meet Our Coffee Experts',
                'description' => 'Learn from certified Q-Graders, agronomy consultants, master roasters, and seasoned international coffee trade attorneys.',
            ],
            'contact' => [
                'title' => 'Contact Us',
                'description' => 'Reach out to Fira Link for export trade inquiries, origin partnerships, documentation assistance, and academy admissions.',
            ],
            'blog' => [
                'title' => 'Read Our Coffee & Trade Blog',
                'description' => 'Stay informed with market analyses, ECTA regulations, EUDR compliance guidelines, and harvest reports directly from Ethiopia.',
            ],
            'testimonials' => [
                'title' => 'Partner & Buyer Testimonials',
                'description' => 'Hear from international green coffee importers, local washing stations, and certified baristas who trade and learn with Fira Link.',
            ],
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'description' => 'Read our privacy policy to understand how Fira Link safeguards your trade transactions, profile data, and personal information.',
            ],
            'terms-and-conditions' => [
                'title' => 'Terms of Service',
                'description' => 'Review our B2B marketplace and platform terms of service governing contracts, export document templates, and user obligations.',
            ],
            'gdpr-compliance' => [
                'title' => 'GDPR & International Compliance',
                'description' => 'Learn how Fira Link adheres to international data privacy and trade standards for EU, North American, and global partners.',
            ],
        ]);

        FrontendSetting::setValue('pages.marquee.items', [
            [
                'image' => 'assets/front/img/home-1/icon1.png',
                'text' => 'Ethiopian Specialty Coffee B2B Marketplace & Trade Hub',
            ],
            [
                'image' => 'assets/front/img/home-1/icon1.png',
                'text' => 'AI Export Documentation Studio (30 Trade Templates)',
            ],
            [
                'image' => 'assets/front/img/home-1/icon1.png',
                'text' => 'ECTA, NBE & ICO Regulatory Trade Compliance',
            ],
            [
                'image' => 'assets/front/img/home-1/icon1.png',
                'text' => 'EUDR Farm Traceability & GPS Polygon Verification',
            ],
            [
                'image' => 'assets/front/img/home-1/icon1.png',
                'text' => 'Specialty Coffee Academy & Q-Grader Mastery Programs',
            ],
            [
                'image' => 'assets/front/img/home-1/icon1.png',
                'text' => 'Origin Expeditions & Washing Station Immersion Tours',
            ],
            [
                'image' => 'assets/front/img/home-1/icon1.png',
                'text' => 'Direct Trade With Verified Exporters & Farmer Cooperatives',
            ],
        ]);

        FrontendSetting::setValue('pages.contact.hero', [
            'eyebrow' => 'Need Any Help?',
            'heading' => 'Get in touch with us',
            'description' => 'Our trade and support team is here to assist with export documentation, origin sourcing, B2B verification, and academy enrollments. Reach out and we will get back to you within one business day.',
        ]);

        FrontendSetting::setValue('pages.contact.methods', [
            [
                'icon' => 'fa-light fa-phone',
                'label' => 'Call us anytime',
                'type' => 'phone',
                'value' => '+251 91 123 4567',
            ],
            [
                'icon' => 'fa-light fa-envelope',
                'label' => 'Send us an email',
                'type' => 'email',
                'value' => 'hello@fira-link.com',
            ],
            [
                'icon' => 'fa-light fa-location-dot',
                'label' => 'Visit our office',
                'type' => 'address',
                'value' => 'Bole Sub-City, Addis Ababa, Ethiopia',
            ],
            [
                'icon' => 'fa-light fa-clock',
                'label' => 'Support hours',
                'type' => 'hours',
                'value' => 'Mon - Fri: 8:30am - 5:30pm EAT',
            ],
        ]);

        FrontendSetting::setValue('pages.contact.form', [
            'heading' => 'Send us a message',
            'description' => 'Let us know how we can help. We usually respond within one business day.',
            'submit_label' => 'Send Message',
            'success_message' => 'Thank you for contacting Fira Link. We will reach out shortly!',
            'subject_prefix' => '[Fira Link Contact]',
        ]);

        FrontendSetting::setValue('pages.contact.map', [
            'embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d6678.7619084840835!2d144.9618311901502!3d-37.81450084255415!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x6ad642b4758afc1d%3A0x3119cc820fdfc62e!2sEnvato!5e0!3m2!1sen!2sbd!4v1641984054261!5m2!1sen!2sbd',
            'caption' => 'Our headquarters is located in the heart of New York City.',
        ]);

        FrontendSetting::setValue('pages.home.hero', [
            'preheading' => 'Learn without limits',
            'heading' => 'Skill up with courses designed by experts',
            'description' => 'Build a learning plan that fits your day-to-day life. Explore immersive classes, guided projects, and live sessions to keep your skills sharp.',
            'cta_label' => 'Explore Courses',
            'cta_url' => '/courses',
        ]);

        FrontendSetting::setValue('pages.home.testimonial', [
            'title' => 'Students Reviews',
            'subtitle' => 'What learners say about our platform',
            'cta_label' => 'All Testimonials',
            'cta_url' => '/testimonials',
        ]);

        FrontendSetting::setValue('pages.home.blog', [
            'title' => 'Coffee Industry Insights',
            'subtitle' => 'Insights from instructors & industry experts',
            'description' => 'Stay updated with the latest Ethiopian specialty coffee origins, ECX export compliance, cupping methodologies, and supply-chain digital innovations curated by Fira Link certified experts.',
        ]);

        FrontendSetting::setValue('pages.home.about', [
            'subheading' => 'About Us',
            'heading' => 'Transforming Learning Into Lasting Impact',
            'description' => 'We partner with expert instructors to create immersive learning pathways that deliver measurable results for students and teams around the globe.',
            'cta_label' => 'Explore More',
            'cta_url' => '/about',
            'author_name' => 'Ronald Richards',
            'author_title' => 'Co-Founder',
        ]);

        FrontendSetting::setValue('pages.home.discount', [
            'heading' => 'Act Fast: 50% Off for the First 50 Students!',
            'description' => 'The ability to learn at my own pace was a game-changer for me. The flexible schedule allowed me to balance my studies with work and personal life, making it possible.',
            'primary_label' => 'Become a Student',
            'primary_url' => '/register',
            'secondary_label' => 'Become a Teacher',
            'secondary_url' => '/become-instructor',
        ]);

        FrontendSetting::setValue('pages.home.features', [
            'title' => 'Why Choose Us',
            'subtitle' => 'Learning That Aligns With Your Personal Goals.',
            'description' => 'Unlock your full potential with education tailored to your personal aspirations. Learn, grow, and achieve success.',
            'highlight_count' => 92,
            'highlight_label' => 'Customizable Courses.',
            'items' => [
                [
                    'icon' => 'fa-solid fa-lightbulb',
                    'title' => 'Early Learning',
                    'description' => 'Engaging programs that nurture curiosity and confidence from the start.',
                ],
                [
                    'icon' => 'fa-solid fa-paintbrush',
                    'title' => 'Art & Creativity',
                    'description' => 'Hands-on workshops designed to inspire design thinking and innovation.',
                ],
                [
                    'icon' => 'fa-solid fa-brain',
                    'title' => 'Brain Training',
                    'description' => 'Structured lessons that strengthen problem solving and critical thinking.',
                ],
                [
                    'icon' => 'fa-solid fa-music',
                    'title' => 'Music Area',
                    'description' => 'Collaborative sessions that blend creativity with technical mastery.',
                ],
            ],
        ]);

        FrontendSetting::setValue('pages.home.cta', [
            'heading' => 'Join our newsletter, spam-free.',
            'description' => 'Get curated learning tips, course launches, and exclusive offers delivered to your inbox.',
            'placeholder' => 'Enter your email',
            'button_label' => 'Subscribe Now',
        ]);

        FrontendSetting::setValue('pages.about.hero', [
            'subheading' => 'About Us',
            'heading' => 'Empowering Ethiopian Coffee Trade & Origin Mastery',
            'description' => 'Fira Link is an integrated specialty coffee ecosystem built for global trade. We bridge verified Ethiopian farmers and exporters with international buyers through AI documentation, EUDR compliance, and certified coffee education.',
            'cta_label' => 'Explore Platform',
            'cta_url' => route('courses'),
            'author_name' => 'Fira Link Team',
            'author_title' => 'Committed to coffee excellence & origin integrity',
        ]);

        FrontendSetting::setValue('pages.about.features', [
            'title' => 'Why Choose Us',
            'subtitle' => 'Learning that aligns with your personal goals.',
            'description' => 'Unlock your full potential with education tailored to your aspirations. We help learners develop practical skills, stay ahead of industry trends, and build confidence.',
            'items' => [
                [
                    'icon' => 'fa-solid fa-check-circle',
                    'text' => 'Flexible classes that fit your schedule.',
                ],
                [
                    'icon' => 'fa-solid fa-globe',
                    'text' => 'Learn anywhere, across devices.',
                ],
                [
                    'icon' => 'fa-solid fa-headset',
                    'text' => 'Dedicated support & resources.',
                ],
            ],
        ]);

        FrontendSetting::setValue('pages.about.highlights', [
            'left' => [
                [
                    'icon' => 'fa-solid fa-graduation-cap',
                    'count' => '24/7',
                    'label' => 'Learning Resources',
                ],
                [
                    'icon' => 'fa-solid fa-people-group',
                    'count' => '100%',
                    'label' => 'Community Support',
                ],
            ],
            'right' => [
                [
                    'icon' => 'fa-solid fa-briefcase',
                    'count' => 0,
                    'label' => 'Career Pathways',
                ],
            ],
        ]);

        FrontendSetting::setValue('pages.about.cta', [
            'heading' => 'Start your learning journey today.',
            'description' => 'Find the perfect course, connect with expert instructors, and build the skills you need to grow.',
            'primary_label' => 'Browse Courses',
            'primary_url' => route('courses'),
            'secondary_label' => 'Meet Our Teachers',
            'secondary_url' => route('instructors.index'),
        ]);
    }
}
