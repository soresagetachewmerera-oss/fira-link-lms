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
            [
                'title' => 'Request Account & Data Deletion',
                'slug' => 'delete-account',
                'description' => 'Instructions and public request channels for deleting your Fira Link Platform account and associated personal data in accordance with Google Play developer policies.',
                'content' => <<<HTML
<div class="account-deletion-container" style="max-width: 850px; margin: 0 auto; line-height: 1.7;">
    <div style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; padding: 32px 24px; border-radius: 12px; margin-bottom: 30px; text-align: center;">
        <h1 style="color: white; margin: 0 0 10px 0; font-size: 28px;">Account and Associated Data Deletion</h1>
        <p style="margin: 0; font-size: 16px; opacity: 0.95;">Fira Link Platform — Google Play Policy & Data Privacy Compliance</p>
    </div>

    <section style="margin-bottom: 28px;">
        <h2 style="color: #1e293b; font-size: 22px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">1. Overview & Your Rights</h2>
        <p>At <strong>Fira Link Platform</strong> (operated by <strong>Fira Link Business Solution</strong>), we respect your fundamental right to control your personal data and digital footprint. In full compliance with Google Play Store Developer Policies, GDPR, and data protection laws, any user who has registered an account through our mobile app (<code>com.firalink.academy</code>) or web platform can request the permanent deletion of their account and all associated personal data at any time.</p>
        <p>You do <strong>not</strong> need to have the app currently installed on your device to submit a deletion request.</p>
    </section>

    <section style="margin-bottom: 28px;">
        <h2 style="color: #1e293b; font-size: 22px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">2. How to Request Account & Data Deletion</h2>
        <div style="display: grid; grid-gap: 16px; margin-top: 15px;">
            <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; background-color: #f8fafc;">
                <h3 style="color: #2563eb; margin-top: 0; font-size: 18px;">Option A: In-App Request (Recommended if you have the app)</h3>
                <ol style="margin-bottom: 0; padding-left: 20px;">
                    <li>Open the <strong>Fira Link Platform</strong> application on your Android device.</li>
                    <li>Log in to your account and tap on the <strong>Profile / Settings</strong> tab.</li>
                    <li>Scroll down to the account management section and tap <strong>Delete Account</strong>.</li>
                    <li>Confirm your request. Your account deletion will be queued immediately.</li>
                </ol>
            </div>

            <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; background-color: #f8fafc;">
                <h3 style="color: #2563eb; margin-top: 0; font-size: 18px;">Option B: Web or Email Request (No App Installation Required)</h3>
                <p>If you have uninstalled the app or cannot access your device, you can request account and data deletion through any of the following direct channels:</p>
                <ul>
                    <li><strong>Direct Email:</strong> Send an email to <a href="mailto:support@fira-link.com" style="color: #2563eb; font-weight: 600;">support@fira-link.com</a> or <a href="mailto:contact@fira-link.com" style="color: #2563eb; font-weight: 600;">contact@fira-link.com</a> with the subject: <code>Account Deletion Request - Fira Link Platform</code>. Please include the registered email address or phone number associated with your account.</li>
                    <li><strong>Web Contact Form:</strong> Visit our official web contact form at <a href="https://lms.fira-link.com/contact" target="_blank" style="color: #2563eb; font-weight: 600;">https://lms.fira-link.com/contact</a> and specify your account deletion request.</li>
                    <li><strong>Support Phone:</strong> Reach our customer support team at <span style="font-weight: 600;">+251 91 122 3344</span>.</li>
                </ul>
            </div>
        </div>
    </section>

    <section style="margin-bottom: 28px;">
        <h2 style="color: #1e293b; font-size: 22px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">3. Data Types That Will Be Deleted</h2>
        <p>When your account deletion request is processed, the following categories of data will be permanently and irreversibly purged from our live production databases:</p>
        <ul style="padding-left: 20px;">
            <li><strong>Identity & Profile Data:</strong> Full name, email address, mobile phone number, profile photo, biography, and credentials.</li>
            <li><strong>Authentication Data:</strong> Password hashes, active authentication tokens, session identifiers, and SMS OTP verification logs.</li>
            <li><strong>Learning Records:</strong> Video watch progress, completed lesson trackers, quiz attempts, and course enrollment records.</li>
            <li><strong>Interactive Data:</strong> Discussion board questions, answers, comments, and instructor direct messages.</li>
            <li><strong>Device & Notification Data:</strong> Push notification tokens (Firebase Cloud Messaging tokens) and device metadata.</li>
        </ul>
    </section>

    <section style="margin-bottom: 28px;">
        <h2 style="color: #1e293b; font-size: 22px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">4. Data Retention & Legal Exceptions</h2>
        <p>In strict accordance with applicable commercial code, banking regulations, and tax compliance laws:</p>
        <ul style="padding-left: 20px;">
            <li><strong>Financial & Payment Transaction Records:</strong> Invoices, payment gateway reference numbers (e.g., Telebirr, CBE Birr, Chapa reference receipts), and VAT records must be retained in an encrypted, non-active financial ledger for up to 5 to 7 years solely to satisfy statutory legal and tax audit requirements. These records are never used for marketing or commercial analytics.</li>
            <li><strong>Issued Verified Certificates:</strong> Digital completion certificates already issued with cryptographic serial numbers may be retained in verification archives to maintain the academic integrity of past employer verifications.</li>
        </ul>
    </section>

    <section style="margin-bottom: 28px;">
        <h2 style="color: #1e293b; font-size: 22px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">5. Processing Timeline</h2>
        <p>All deletion requests are verified and processed within <strong>30 calendar days</strong> of receipt. Once the deletion is complete, you will receive a final confirmation notification, and your credentials will no longer be valid to sign in.</p>
    </section>

    <div style="background-color: #eff6ff; border-left: 4px solid #3b82f6; padding: 16px 20px; border-radius: 4px; margin-top: 30px;">
        <h4 style="margin: 0 0 8px 0; color: #1e40af;">Need Assistance?</h4>
        <p style="margin: 0; color: #1e3a8a; font-size: 14px;">Contact our Data Privacy Support Team at Fira Link Business Solution: <a href="mailto:support@fira-link.com" style="color: #2563eb; text-decoration: underline;">support@fira-link.com</a> | Bole Sub-City, Addis Ababa, Ethiopia.</p>
    </div>
</div>
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

