<?php

/**
 * Default deny: every route that a guest (no token, no session) may call. Anything not listed here must
 * have an auth middleware (auth, auth:sanctum). tests/Feature/Security/RouteInventoryTest fails when:
 *  - a route without auth middleware is missing from this list,
 *  - a listed route no longer exists (stale entry),
 *  - a public route that writes (POST/PUT/PATCH/DELETE) is not in a category allowed to write,
 *  - a public API route has no rate limit.
 *
 * Keys are "METHOD uri" exactly as `php artisan route:list` prints them (GET|HEAD is written GET).
 *
 * Categories:
 *  read      class A, read-only: catalog, lots (view only), market data, public pages
 *  auth      sign-in, sign-up, codes, forgot/reset password (may write)
 *  compute   stateless tools and lookups that may be POSTed: calculators, certificate check, Fira AI (D-1),
 *            public web forms (may write; rate-limited)
 *  callback  payment gateway callbacks and webhooks; every one re-verifies with the gateway (may write)
 *  signed    only works with a valid signed URL or key (email verification, signed downloads, cron key)
 *  session   acts on the signed-in web session if there is one, refuses otherwise (phone verification pages)
 *  install   web installer; locked once installed (SetupController::callAction)
 */
return [
    // ---- API: read ----
    'GET api/settings' => 'read',
    'GET api/settings/pages' => 'read',
    'GET api/settings/pages/{slug}' => 'read',
    'GET api/fira-ai/status' => 'read',
    'GET api/fira-ai' => 'read',
    'GET api/ai-chat' => 'read',
    'GET api/home' => 'read',
    'GET api/courses/recent' => 'read',
    'GET api/courses' => 'read',
    'GET api/categories' => 'read',
    'GET api/courses/{id}' => 'read',
    'GET api/bundles' => 'read',
    'GET api/bundles/{id}' => 'read',
    'GET api/video/stream' => 'read', // preview lessons only; paid lessons need a token or a signed URL
    'GET api/instructors' => 'read',
    'GET api/instructors/{id}' => 'read',
    'GET api/events' => 'read',
    'GET api/events/{id}' => 'read',
    'GET api/tours' => 'read',
    'GET api/tours/{id}' => 'read',
    'GET api/verify-certificate/{code}' => 'read',
    'GET api/instructor/verify-certificate/{code}' => 'read',
    'GET api/sellers/{firaId}' => 'read',
    'GET api/community/questions' => 'read',
    'GET api/community/questions/{id}' => 'read',
    'GET api/marketplace/listings' => 'read',
    'GET api/marketplace/listings/{idOrSlug}' => 'read',
    'GET api/marketplace/filters' => 'read',
    'GET api/marketplace/plans' => 'read',
    'GET api/workspaces/options' => 'read',
    'GET api/market/overview' => 'read',
    'GET api/market/series' => 'read',
    'GET api/market/sources' => 'read',
    'GET api/market/chart-data' => 'read',
    'GET api/market/live-tick' => 'read',
    'GET api/market/ticker' => 'read',
    'GET api/coffee-toolkit/hub-summary' => 'read',
    'GET api/coffee-toolkit/market-intelligence' => 'read',
    'GET api/coffee-toolkit/coffee-prices' => 'read',
    'GET api/export-docs/templates' => 'read',
    'GET api/export-docs/templates/{code}' => 'read',

    // ---- API: sign-in ----
    'POST api/auth/register' => 'auth',
    'POST api/auth/login' => 'auth',
    'POST api/auth/login/otp/verify' => 'auth',
    'POST api/auth/login/otp/resend' => 'auth',
    'POST api/auth/register/verify' => 'auth',
    'POST api/auth/register/resend' => 'auth',
    'POST api/auth/register/change-phone' => 'auth',
    'POST api/auth/phone/verify/send' => 'auth',
    'POST api/auth/phone/verify/check' => 'auth',
    'POST api/auth/password/forgot/otp' => 'auth',
    'POST api/auth/password/reset/otp' => 'auth',
    'POST api/auth/phone/start' => 'auth',
    'POST api/auth/phone/email-fallback' => 'auth',
    'POST api/auth/phone/verify' => 'auth',
    'POST api/auth/phone/complete' => 'auth',
    'POST api/auth/email/register/start' => 'auth',
    'POST api/auth/email/register/verify' => 'auth',
    'POST api/auth/password/forgot' => 'auth',
    'POST api/auth/password/verify-code' => 'auth',
    'POST api/auth/password/reset' => 'auth',
    'POST api/instructor/auth/register' => 'auth',
    'POST api/instructor/auth/login' => 'auth',
    'POST api/instructor/auth/login/otp/verify' => 'auth',
    'POST api/instructor/auth/login/otp/resend' => 'auth',
    'POST api/instructor/auth/forgot-password' => 'auth',

    // ---- API: stateless tools ----
    'POST api/fira-ai/send' => 'compute',
    'POST api/ai-chat/send' => 'compute',
    'POST api/verify-certificate' => 'compute',
    'POST api/instructor/verify-certificate' => 'compute',
    'POST api/coffee-toolkit/calculate' => 'compute',
    'POST api/coffee-toolkit/assess' => 'compute', // scores without saving for guests
    'POST api/export-docs/calculate' => 'compute',

    // ---- API: gateways and signed links ----
    'GET api/marketplace/subscribe/chapa/callback' => 'callback', // answers the status only
    'POST api/billing/webhook/{provider}' => 'callback',
    'GET api/certificates/download/signed/{id}' => 'signed',

    // ---- Web: public pages (outside the launch scope except /verify, see CLAUDE.md) ----
    'GET /' => 'read',
    'GET about' => 'read',
    'GET ai-chat' => 'read',
    'GET blog' => 'read',
    'GET blog/{slug}' => 'read',
    'GET bundles' => 'read',
    'GET bundles/{id}' => 'read',
    'GET contact' => 'read',
    'GET courses' => 'read',
    'GET courses/{course}' => 'read',
    'GET events' => 'read',
    'GET events/{slug}' => 'read',
    'GET events/bookings/verify/{reference}' => 'read', // ticket check at the door: no email or phone
    'GET faq' => 'read',
    'GET free-tools' => 'read',
    'GET free-tools/coffee-prices' => 'read',
    'GET free-tools/coffee-prices/export' => 'read',
    'GET free-tools/market-data' => 'read',
    'GET instructors' => 'read',
    'GET instructors/{instructor}' => 'read',
    'GET market-prices' => 'read',
    'GET market-prices/chart-data' => 'read',
    'GET market-prices/live-tick' => 'read',
    'GET marketplace' => 'read',
    'GET marketplace/listings/{slug}' => 'read',
    'GET marketplace/pricing' => 'read',
    'GET marketplace/suppliers' => 'read',
    'GET marketplace/suppliers/{slug}' => 'read',
    'GET pages/{customPage}' => 'read',
    'GET {customPage}' => 'read', // custom pages by slug (last route in web.php)
    'GET pricing' => 'read',
    'GET privacy' => 'read',
    'GET privacy-policy' => 'read',
    'GET programs' => 'read',
    'GET sitemap.xml' => 'read',
    'GET storage/{path}' => 'read', // public disk only (path-checked)
    'GET student/subscription' => 'read', // redirect
    'GET subscriptions' => 'read', // redirect
    'GET terms' => 'read',
    'GET terms-conditions' => 'read',
    'GET testimonials' => 'read',
    'GET tours' => 'read',
    'GET tours/{slug}' => 'read',
    'GET tours/{slug}/book' => 'read', // redirects to the tour: booking is in the app (D-8)
    'GET up' => 'read',
    'GET verify' => 'read',
    'GET verify/{firaId}' => 'read',
    'GET verify/{firaId}/qr.svg' => 'read',
    'GET sanctum/csrf-cookie' => 'read',

    // ---- Web: sign-in ----
    'GET login' => 'auth',
    'POST login' => 'auth',
    'GET login/otp' => 'auth',
    'POST login/otp' => 'auth',
    'POST login/otp/resend' => 'auth',
    'GET register' => 'auth',
    'POST register' => 'auth',
    'GET become-instructor' => 'auth',
    'POST become-instructor' => 'auth',
    'GET forgot-password' => 'auth',
    'POST forgot-password' => 'auth',
    'GET reset-password/{token}' => 'auth',
    'POST reset-password' => 'auth',

    // ---- Web: forms and tools ----
    'POST ai-chat/send' => 'compute',
    'POST contact' => 'compute',
    'POST newsletter/subscribe' => 'compute',
    'POST blog/{slug}/comments' => 'compute',
    'POST free-tools/calculate' => 'compute',
    'POST free-tools/ai-analysis' => 'compute',
    'POST free-tools/ask-advisor' => 'compute',
    'POST free-tools/save-calculation' => 'compute', // refuses guests inline (requires_auth)
    'GET|POST verify-certificate' => 'compute',
    'POST subscriptions/{id}/checkout' => 'compute', // redirect only
    'POST tours/{slug}/book' => 'compute', // redirects to the tour: booking is in the app (D-8)

    // ---- Web: gateways, signed links, session-only ----
    'GET|POST payment/chapa/callback' => 'callback',
    'POST payment/chapa/webhook' => 'callback',
    'POST payment/razorpay/callback' => 'callback',
    'GET|POST payment/sslcommerz/callback' => 'callback',
    'GET|POST payment/xpay/callback' => 'callback',
    'GET payment/bkash/callback' => 'callback',
    'GET payment/flutterwave/callback' => 'callback',
    'GET payment/mollie/callback' => 'callback',
    'GET payment/paypal/callback' => 'callback',
    'GET payment/paystack/callback' => 'callback',
    'GET payment/stripe/callback' => 'callback',
    'GET|POST events/payment/chapa/callback' => 'callback',
    'GET|POST tours/payment/chapa/callback' => 'callback',
    'POST billing/webhook/{provider}' => 'callback',
    'GET email/verify/{id}/{hash}' => 'signed',
    'GET cron/run' => 'signed', // needs the cron key
    'GET verify-phone' => 'session',
    'POST verify-phone' => 'session',
    'POST verify-phone/resend' => 'session',
    'POST verify-phone/change' => 'session',

    // ---- Web installer (locked once installed) ----
    'GET install' => 'install',
    'GET install/requirements' => 'install',
    'GET install/permissions' => 'install',
    'GET install/environment' => 'install',
    'POST install/environment' => 'install',
    'GET install/database' => 'install',
    'POST install/database' => 'install',
    'GET install/final' => 'install',
];
