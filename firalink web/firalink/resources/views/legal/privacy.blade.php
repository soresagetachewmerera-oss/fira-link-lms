@extends('layouts.app')

@section('breadcrumb')
    @include('partials.breadcrumb', [
        'page_title' => 'Privacy Policy',
        'base_url' => route('home'),
    ])
@endsection

@section('content')
<div class="fira-legal-page section-padding" style="padding: 60px 0 90px; background-color: #FAFAFC;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-md-11">
                <div class="card border-0 shadow-sm p-4 p-md-5" style="border-radius: 18px; background: #FFFFFF;">
                    <span class="badge px-3 py-2 rounded-pill mb-3 text-uppercase fw-semibold" style="background-color: rgba(109, 26, 149, 0.1); color: #6D1A95; font-size: 12px; width: fit-content;">
                        Legal &amp; Compliance
                    </span>
                    <h2 class="fw-bold mb-3" style="color: #0F172A; font-size: 32px;">Data Protection &amp; Privacy Governance</h2>
                    <p class="text-muted small mb-4">Last Updated: October 2026 &bull; Effective Date: January 1, 2026</p>

                    <div class="legal-content text-secondary" style="font-size: 15px; line-height: 1.8;">
                        <h2 class="h5 fw-bold text-dark mt-4 mb-2">1. Introduction &amp; Scope</h2>
                        <p>
                            Fira Link Academy ("the Academy", "we", "us", or "our"), operated by Fira Link Business Solution in Addis Ababa, Ethiopia, is committed to safeguarding the privacy and personal data of our learners, event attendees, tour participants, and website visitors. This Privacy Policy details how we collect, process, store, and protect your information when you interact with our digital platform at fira-link.com and associated subdomains.
                        </p>

                        <h2 class="h5 fw-bold text-dark mt-4 mb-2">2. Information We Collect</h2>
                        <p>
                            We collect personal information necessary to deliver educational courses, issue authenticated certificates, facilitate field tours, and execute transaction settlements:
                        </p>
                        <ul>
                            <li><strong>Account &amp; Profile Information:</strong> Name, email address, phone number, and physical city or regional state.</li>
                            <li><strong>Educational Records:</strong> Course progress, quiz scores, assignment submissions, and certificate credential identifiers.</li>
                            <li><strong>Transaction Data:</strong> Telebirr, CBE, or Chapa payment reference IDs. We never store credit card numbers or banking passwords on our servers.</li>
                            <li><strong>Usage &amp; Device Information:</strong> Browser type, IP address, device operating system, and anonymous page interaction metrics to optimize portal speed and accessibility.</li>
                        </ul>

                        <h2 class="h5 fw-bold text-dark mt-4 mb-2">3. How We Use Your Information</h2>
                        <p>
                            We strictly process your personal information for lawful and transparent business purposes:
                        </p>
                        <ul>
                            <li>Enabling enrollment access to video curriculum and course resources.</li>
                            <li>Issuing verifiable digital certificates of completion with unique QR verification codes.</li>
                            <li>Processing course and tour bookings with compliant payment verification.</li>
                            <li>Sending essential service notifications, class reminders, and regulatory trade alerts.</li>
                            <li>Maintaining platform security and preventing fraudulent activities.</li>
                        </ul>

                        <h2 class="h5 fw-bold text-dark mt-4 mb-2">4. Data Sharing &amp; Third Parties</h2>
                        <p>
                            Fira Link Academy does not sell, rent, or trade your personal information to third-party advertisers. Information is only shared with verified technical infrastructure providers required to operate the service (e.g., licensed payment processors such as Chapa, transactional SMS gateways, and secure cloud storage facilities).
                        </p>

                        <h2 class="h5 fw-bold text-dark mt-4 mb-2">5. Data Retention &amp; Security</h2>
                        <p>
                            Your information is stored in encrypted databases protected by standard firewall protocols, TLS encryption in transit, and restricted administrative access. We retain learner account records for as long as your account remains active to preserve your lifetime certificate verification rights.
                        </p>

                        <h2 class="h5 fw-bold text-dark mt-4 mb-2">6. Your Rights &amp; Inquiries</h2>
                        <p>
                            You have the right to request access to your personal data, update your details, or request account deletion. To exercise these rights, please contact our Data Protection Officer at <a href="mailto:privacy@fira-link.com" style="color: #6D1A95;">privacy@fira-link.com</a> or visit our offices in Bole Sub-City, Addis Ababa, Ethiopia.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
