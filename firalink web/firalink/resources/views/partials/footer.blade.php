@php
    $siteName = site_name();
    $currentLocale = app()->getLocale();
@endphp

<!-- Fira Link Unified Global Footer (Design System v2.0) -->
<footer class="fira-site-footer">
    <!-- Main Footer Container (Strictly 1200px) -->
    <div class="fira-container">
        <!-- Newsletter Intelligence Strip -->
        <div class="fira-footer-newsletter-wrap">
            <div class="fira-newsletter-text">
                <span class="fira-eyebrow" style="color: var(--fira-brand-tint, #F3E8FF); margin-bottom: 4px;">Market Intelligence</span>
                <h3 style="font-size: 1.25rem; font-weight: 700; color: #FFFFFF; line-height: 1.3;">
                    Subscribe to Ethiopian Coffee Trade Intelligence
                </h3>
                <p style="font-size: 0.875rem; color: #E9D5FF; max-width: 50ch; margin-top: 4px;">
                    Weekly ECX price indices, harvest crop forecasts, and FOB export reports directly to your inbox.
                </p>
            </div>
            <form action="{{ route('newsletter.subscribe') }}" method="POST" class="fira-newsletter-form">
                @csrf
                <div class="fira-newsletter-input-group">
                    <input type="email"
                           name="email"
                           class="fira-newsletter-input"
                           placeholder="Enter your business email"
                           required
                           aria-label="Your business email address">
                    <button type="submit" class="fira-newsletter-btn">
                        Subscribe
                    </button>
                </div>
            </form>
        </div>

        <!-- 4-Column Directory Grid -->
        <div class="fira-footer-grid">
            <!-- Col 1: Platform & Sourcing -->
            <div class="fira-footer-col fira-footer-brand-col">
                <a href="{{ route('home') }}" class="fira-footer-logo" aria-label="Fira Link Platform home">
                    <img src="{{ asset('assets/front/img/logo/fira_header_logo.png') }}"
                         alt="Fira Link Platform"
                         width="132"
                         height="42"
                         style="filter: brightness(0) invert(1);">
                </a>
                <p class="fira-footer-bio">
                    Ethiopia's premier digital coffee trade and export platform, connecting certified washing stations and cooperatives directly with international roasters and brokers.
                </p>
                <div class="fira-footer-pill-tag">
                    <span class="fira-pulse-dot"></span>
                    Built for Ethiopian coffee trade
                </div>
                <!-- Social links -->
                <div class="fira-footer-socials">
                    <a href="https://linkedin.com" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" class="fira-social-link">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
                    </a>
                    <a href="https://t.me/firalink" target="_blank" rel="noopener noreferrer" aria-label="Telegram" class="fira-social-link">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                    </a>
                    <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="fira-social-link">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
                    </a>
                    <a href="https://youtube.com" target="_blank" rel="noopener noreferrer" aria-label="YouTube" class="fira-social-link">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon></svg>
                    </a>
                </div>
            </div>

            <!-- Col 2: Marketplace & Sourcing -->
            <div class="fira-footer-col">
                <h4 class="fira-footer-heading">B2B Marketplace</h4>
                <ul class="fira-footer-links">
                    <li><a href="{{ route('marketplace.index') }}">Browse Coffee Lots</a></li>
                    <li><a href="{{ route('marketplace.suppliers') }}">Exporters &amp; Suppliers</a></li>
                    <li><a href="{{ route('marketplace.pricing') }}">Pricing &amp; Memberships</a></li>
                    <li><a href="{{ route('market-prices.index') }}">Live Market Prices</a></li>
                    <li><a href="{{ route('tours.index') }}">Origin Expeditions &amp; Safaris</a></li>
                </ul>
            </div>

            <!-- Col 3: Academy & Tools -->
            <div class="fira-footer-col">
                <h4 class="fira-footer-heading">Academy &amp; Tools</h4>
                <ul class="fira-footer-links">
                    <li><a href="{{ route('courses') }}">Specialty Export Courses</a></li>
                    <li><a href="{{ route('free-tools.index') }}">Coffee Export Terminal</a></li>
                    <li><a href="{{ route('verify-certificate') }}">Verify Certificates</a></li>
                    <li><a href="{{ route('events.index') }}">Coffee Trade Summits</a></li>
                    <li><a href="{{ route('blog.index') }}">Industry News &amp; Reports</a></li>
                </ul>
            </div>

            <!-- Col 4: Advisory & Legal -->
            <div class="fira-footer-col">
                <h4 class="fira-footer-heading">Trade Advisory &amp; Legal</h4>
                <ul class="fira-footer-links">
                    <li><a href="{{ route('about') }}">About Fira Link</a></li>
                    <li><a href="{{ route('contact') }}">Contact Trade Desk</a></li>
                    <li><a href="{{ route('faq') }}">Frequently Asked Questions</a></li>
                    <li><a href="{{ route('privacy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('terms') }}">Terms of Service</a></li>
                </ul>
                <div class="fira-footer-contact-snippet">
                    Bole Sub-City, Addis Ababa, Ethiopia<br>
                    <a href="mailto:info@fira-link.com">info@fira-link.com</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Copyright & Language Picker Bar -->
    <div class="fira-footer-bottom">
        <div class="fira-container fira-footer-bottom-flex">
            <p class="fira-footer-copy">
                &copy; {{ date('Y') }} Fira Link Platform. All rights reserved. A digital trade initiative of Fira Link Business Solution.
            </p>
            <div class="fira-footer-bottom-actions">
                <!-- Language Picker in Footer -->
                <div class="fira-footer-lang">
                    <span style="font-size: 13px; color: #A78BFA;">Language:</span>
                    <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="fira-footer-lang-link {{ $currentLocale === 'en' ? 'is-active' : '' }}">English</a>
                    <span style="color: #6D28D9;">•</span>
                    <a href="{{ request()->fullUrlWithQuery(['lang' => 'am']) }}" class="fira-footer-lang-link {{ $currentLocale === 'am' ? 'is-active' : '' }}">አማርኛ</a>
                </div>
                <div class="fira-footer-legal-inline">
                    <a href="{{ route('privacy') }}">Privacy</a>
                    <span>•</span>
                    <a href="{{ route('terms') }}">Terms</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
/* ==========================================================================
   FIRA LINK UNIFIED FOOTER STYLES (v2.0)
   4-Columns Max, Deep Velvet Indigo (#3B0764) Surface, 1200px Aligned
   ========================================================================== */
.fira-site-footer {
    background-color: var(--fira-brand-deep, #3B0764);
    color: #E9D5FF;
    font-family: 'Inter', sans-serif;
    padding-top: var(--fira-space-8, 64px);
    margin-top: var(--fira-space-12, 96px);
    border-top: 1px solid rgba(233, 228, 240, 0.15);
}

.fira-footer-newsletter-wrap {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 32px;
    padding: 32px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 16px;
    margin-bottom: var(--fira-space-8, 64px);
}

@media (max-width: 900px) {
    .fira-footer-newsletter-wrap {
        flex-direction: column;
        align-items: flex-start;
        padding: 24px;
        gap: 20px;
    }
}

.fira-newsletter-form {
    width: 100%;
    max-width: 440px;
}

.fira-newsletter-input-group {
    display: flex;
    gap: 8px;
    width: 100%;
}

@media (max-width: 500px) {
    .fira-newsletter-input-group {
        flex-direction: column;
    }
}

.fira-newsletter-input {
    height: 44px;
    padding: 0 16px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.1);
    color: #FFFFFF;
    font-size: 14px;
    font-family: inherit;
    flex-grow: 1;
    outline: none;
    transition: border-color 0.15s ease, background-color 0.15s ease;
}

.fira-newsletter-input::placeholder {
    color: #D8B4FE;
}

.fira-newsletter-input:focus {
    border-color: #F3E8FF;
    background: rgba(255, 255, 255, 0.15);
}

.fira-newsletter-btn {
    height: 44px;
    padding: 0 22px;
    border-radius: 10px;
    border: none;
    background: #FFFFFF;
    color: var(--fira-brand-deep, #3B0764);
    font-size: 14px;
    font-weight: 700;
    font-family: inherit;
    cursor: pointer;
    white-space: nowrap;
    transition: background-color 0.15s ease, transform 0.15s ease;
}

.fira-newsletter-btn:hover {
    background: #F3E8FF;
}

/* 4-Column Directory Grid */
.fira-footer-grid {
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr 1.1fr;
    gap: 40px;
    padding-bottom: 56px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

@media (max-width: 1024px) {
    .fira-footer-grid {
        grid-template-columns: 1fr 1fr;
        gap: 32px;
    }
}

@media (max-width: 600px) {
    .fira-footer-grid {
        grid-template-columns: 1fr;
        gap: 28px;
    }
}

.fira-footer-logo {
    display: inline-block;
    margin-bottom: 16px;
}

.fira-footer-bio {
    font-size: 13.5px;
    line-height: 1.6;
    color: #DDD6FE;
    max-width: 38ch;
    margin-bottom: 16px;
}

.fira-footer-pill-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    font-weight: 600;
    color: #FFFFFF;
    background: rgba(255, 255, 255, 0.1);
    padding: 4px 12px;
    border-radius: 999px;
    margin-bottom: 18px;
}

.fira-pulse-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #34D399;
}

.fira-footer-socials {
    display: flex;
    align-items: center;
    gap: 12px;
}

.fira-social-link {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.08);
    color: #DDD6FE;
    display: grid;
    place-items: center;
    text-decoration: none;
    transition: all 0.15s ease;
}

.fira-social-link:hover {
    color: #FFFFFF;
    background: var(--fira-brand-primary, #6D1A95);
    transform: translateY(-2px);
}

.fira-footer-heading {
    font-size: 15px;
    font-weight: 700;
    color: #FFFFFF;
    letter-spacing: -0.01em;
    margin-bottom: 18px;
}

.fira-footer-links {
    list-style: none;
    margin: 0;
    padding: 0;
}

.fira-footer-links li + li {
    margin-top: 10px;
}

.fira-footer-links a {
    color: #DDD6FE;
    font-size: 13.5px;
    text-decoration: none;
    transition: color 0.15s ease;
}

.fira-footer-links a:hover {
    color: #FFFFFF;
    text-decoration: underline;
}

.fira-footer-contact-snippet {
    margin-top: 16px;
    font-size: 12.5px;
    line-height: 1.6;
    color: #C4B5FD;
}

.fira-footer-contact-snippet a {
    color: #FFFFFF;
    font-weight: 600;
    text-decoration: none;
}

/* Footer Bottom Bar */
.fira-footer-bottom {
    padding: 24px 0;
    background: rgba(0, 0, 0, 0.15);
}

.fira-footer-bottom-flex {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.fira-footer-copy {
    font-size: 12.5px;
    color: #C4B5FD;
    margin: 0;
}

.fira-footer-bottom-actions {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.fira-footer-lang {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
}

.fira-footer-lang-link {
    color: #DDD6FE;
    text-decoration: none;
    font-weight: 500;
}

.fira-footer-lang-link.is-active,
.fira-footer-lang-link:hover {
    color: #FFFFFF;
    font-weight: 700;
}

.fira-footer-legal-inline {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: #C4B5FD;
}

.fira-footer-legal-inline a {
    color: #DDD6FE;
    text-decoration: none;
}

.fira-footer-legal-inline a:hover {
    color: #FFFFFF;
}
</style>
