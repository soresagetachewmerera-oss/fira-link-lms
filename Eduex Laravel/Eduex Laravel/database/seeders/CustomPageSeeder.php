<?php

namespace Database\Seeders;

use App\Models\CustomPage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CustomPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'description' => 'Outline how we collect, use, and protect user data.',
                'content' => <<<HTML
<h2>Privacy Policy</h2>
<p>We value your privacy and are committed to safeguarding your personal information. This privacy policy explains how we collect, use, disclose, and protect your data when you use our platform.</p>
<h3>Information We Collect</h3>
<ul>
    <li>Account information such as your name and email address.</li>
    <li>Usage data including course progress and interaction history.</li>
    <li>Payment information processed securely via trusted payment gateways.</li>
</ul>
<h3>How We Use Your Information</h3>
<p>Your data helps us deliver course content, provide support, and improve our services. We never sell personal information to third parties.</p>
HTML,
            ],
            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms-and-conditions',
                'description' => 'Important guidelines about using the platform.',
                'content' => <<<HTML
<h2>Terms &amp; Conditions</h2>
<p>By accessing and using our platform, you agree to abide by the following terms and conditions. Please read them carefully.</p>
<h3>Account Responsibilities</h3>
<p>You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account.</p>
<h3>Course Access</h3>
<p>Course materials are provided for personal learning use only. Redistribution or resale of content is prohibited without prior written consent.</p>
<h3>Refund Policy</h3>
<p>Refunds are handled in accordance with our refund guidelines. Please contact support within 14 days of purchase for assistance.</p>
HTML,
            ],
            [
                'title' => 'GDPR Compliance',
                'slug' => 'gdpr-compliance',
                'description' => 'Details about our adherence to GDPR regulations.',
                'content' => <<<HTML
<h2>GDPR Compliance Statement</h2>
<p>We comply with the General Data Protection Regulation (GDPR) and strive to provide transparent control over your personal data.</p>
<h3>Your Rights</h3>
<ul>
    <li><strong>Right to Access:</strong> Request a copy of the personal data we hold about you.</li>
    <li><strong>Right to Rectification:</strong> Update or correct your personal information.</li>
    <li><strong>Right to Erasure:</strong> Request deletion of your personal data, subject to legal obligations.</li>
</ul>
<h3>Data Requests</h3>
<p>To exercise any of these rights, please contact our Data Protection Officer at <a href="mailto:privacy@example.com">privacy@example.com</a>.</p>
HTML,
            ],
        ];

        foreach ($pages as $page) {
            CustomPage::updateOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'description' => $page['description'],
                    'content' => $page['content'],
                ]
            );
        }
    }
}

