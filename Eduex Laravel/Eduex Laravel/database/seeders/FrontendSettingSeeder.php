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
            'about_text' => 'Eduex connects learners with expert instructors through engaging online and in-person experiences.',
            'tagline' => 'Inspiring learning for every stage of life.',
            'copyright' => '© ' . date('Y') . ' Eduex. All rights reserved.',
        ]);

        FrontendSetting::setValue('footer.contact', [
            'email' => 'hello@eduex.com',
            'phone' => '+1 (800) 555-1234',
            'address' => '371 7th Ave, New York, NY 10001',
            'hours' => 'Mon - Fri: 9:00am - 6:00pm ET',
        ]);

        FrontendSetting::setValue('footer.social_links', [
            [
                'label' => 'Follow on Facebook',
                'url' => 'https://facebook.com/eduex',
                'icon' => 'fab fa-facebook-f',
            ],
            [
                'label' => 'Follow on Twitter',
                'url' => 'https://twitter.com/eduex',
                'icon' => 'fab fa-twitter',
            ],
            [
                'label' => 'Connect on LinkedIn',
                'url' => 'https://linkedin.com/company/eduex',
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
                'title' => 'Eduex',
                'description' => 'Join Eduex to access premium online courses, expert instructors, and immersive learning experiences tailored to every stage of your education.',
            ],
            'courses' => [
                'title' => 'Browse Courses',
                'description' => 'Explore hundreds of curated courses covering technology, design, business, marketing, and more. Find the perfect program to grow your skills.',
            ],
            'faq' => [
                'title' => 'Frequently Asked Questions',
                'description' => 'Find answers to popular questions about Eduex courses, certifications, instructors, pricing, and support.',
            ],
            'about' => [
                'title' => 'About Eduex',
                'description' => 'Discover Eduex’s mission, values, and the people behind our platform committed to transforming education for everyone.',
            ],
            'events' => [
                'title' => 'Upcoming Events & Workshops',
                'description' => 'Explore upcoming Eduex events, workshops, and live sessions designed to accelerate your learning journey.',
            ],
            'instructors' => [
                'title' => 'Meet Our Instructors',
                'description' => 'Meet our expert instructors who bring real-world expertise and passion to every course. Find the perfect mentor for your learning journey.',
            ],
            'contact' => [
                'title' => 'Contact Us',
                'description' => 'Have questions? Contact our support team for assistance. We’re here to help you every step of the way.',
            ],
            'blog' => [
                'title' => 'Read Our Blog',
                'description' => 'Stay informed with our latest blog posts covering industry trends, learning strategies, and success stories. Get expert tips and insights for your educational journey.',
            ],
            'testimonials' => [
                'title' => 'Student Testimonials',
                'description' => 'Hear from learners who have transformed their skills through Eduex courses and mentorship.',
            ],
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'description' => 'Read our privacy policy to understand how we collect, use, and protect your personal information when you use Eduex.',
            ],
            'terms-and-conditions' => [
                'title' => 'Terms of Service',
                'description' => 'Review our terms of service to learn about your rights and responsibilities when using Eduex services.',
            ],
            'gdpr-compliance' => [
                'title' => 'GDPR Compliance',
                'description' => 'Learn about how Eduex complies with GDPR regulations to protect your privacy and data security.',
            ],
        ]);

        FrontendSetting::setValue('pages.marquee.items', [
            [
                'image' => 'assets/front/img/home-1/icon1.png',
                'text' => 'Education & University',
            ],
            [
                'image' => 'assets/front/img/home-1/icon1.png',
                'text' => 'Online Education',
            ],
            [
                'image' => 'assets/front/img/home-1/icon1.png',
                'text' => '230+ Quality Courses',
            ],
            [
                'image' => 'assets/front/img/home-1/icon1.png',
                'text' => 'Experienced Instructors',
            ],
            [
                'image' => 'assets/front/img/home-1/icon1.png',
                'text' => '25% Extra Coupon Bonus',
            ],
        ]);

        FrontendSetting::setValue('pages.contact.hero', [
            'eyebrow' => 'Need Any Help?',
            'heading' => 'Get in touch with us',
            'description' => 'Our support team is here to help you choose the right course, solve account issues, and keep your learning on track. Reach out and we will get back to you within one business day.',
        ]);

        FrontendSetting::setValue('pages.contact.methods', [
            [
                'icon' => 'fa-light fa-phone',
                'label' => 'Call us anytime',
                'type' => 'phone',
                'value' => '+1 (800) 555-1234',
            ],
            [
                'icon' => 'fa-light fa-envelope',
                'label' => 'Send us an email',
                'type' => 'email',
                'value' => 'hello@eduex.com',
            ],
            [
                'icon' => 'fa-light fa-location-dot',
                'label' => 'Visit our office',
                'type' => 'address',
                'value' => '371 7th Ave, New York, NY 10001',
            ],
            [
                'icon' => 'fa-light fa-clock',
                'label' => 'Support hours',
                'type' => 'hours',
                'value' => 'Mon - Fri: 9:00am - 6:00pm ET',
            ],
        ]);

        FrontendSetting::setValue('pages.contact.form', [
            'heading' => 'Send us a message',
            'description' => 'Let us know how we can help. We usually respond within one business day.',
            'submit_label' => 'Send Message',
            'success_message' => 'Thank you for contacting Eduex. We will reach out shortly!',
            'subject_prefix' => '[Eduex Contact]',
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
            'cta_url' => route('courses'),
        ]);

        FrontendSetting::setValue('pages.home.testimonial', [
            'title' => 'Students Reviews',
            'subtitle' => 'What learners say about our platform',
            'cta_label' => 'All Testimonials',
            'cta_url' => route('testimonials.index'),
        ]);

        FrontendSetting::setValue('pages.home.blog', [
            'title' => 'Our Blogs',
            'subtitle' => 'Insights from instructors & industry experts',
            'description' => 'Stay updated with the latest learning guides, course launches, and trends from across the Eduex community.',
        ]);

        FrontendSetting::setValue('pages.home.about', [
            'subheading' => 'About Us',
            'heading' => 'Transforming Learning Into Lasting Impact',
            'description' => 'We partner with expert instructors to create immersive learning pathways that deliver measurable results for students and teams around the globe.',
            'cta_label' => 'Explore More',
            'cta_url' => route('about'),
            'author_name' => 'Ronald Richards',
            'author_title' => 'Co-Founder',
        ]);

        FrontendSetting::setValue('pages.home.discount', [
            'heading' => 'Act Fast: 50% Off for the First 50 Students!',
            'description' => 'The ability to learn at my own pace was a game-changer for me. The flexible schedule allowed me to balance my studies with work and personal life, making it possible.',
            'primary_label' => 'Become a Student',
            'primary_url' => route('register'),
            'secondary_label' => 'Become a Teacher',
            'secondary_url' => route('instructor.register'),
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
            'heading' => 'Transforming Learning Into Lasting Impact',
            'description' => 'Eduex is a learning platform built for growth. We pair committed instructors with curious learners, crafting programs that inspire action, unlock career opportunities, and deliver measurable outcomes.',
            'cta_label' => 'Explore Courses',
            'cta_url' => route('courses'),
            'author_name' => 'Eduex Team',
            'author_title' => 'Committed to lifelong learning',
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
