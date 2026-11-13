# Fira Link B2B Platform & Billing Architecture Audit

**Date:** November 7, 2026  
**Auditor:** Senior Laravel/Next.js Engineer & SaaS Billing Architect  
**Codebase:** Fira Link (Ethiopian B2B Export Platform & Academy LMS)  
**Repository Working Paths:**  
- Web Backend & Front: `C:\Users\sora\fira-link-lms\firalink web\firalink`  
- Mobile Client: `C:\Users\sora\fira-link-lms\firalink flutter`  

---

## 1. Stack and Structure

### Framework Versions & Environment
- **Laravel Framework:** `12.0.x` (`laravel/framework: ^12.0` in `composer.json`)
- **PHP Version:** `^8.2` (PHP 8.2+ runtime environment)
- **Database:** SQLite (`database/database.sqlite`), configured for seamless development and testing with foreign keys enabled.
- **Frontend Assets & Bundler:**
  - Vite `7.0.7` (`@tailwindcss/vite: ^4.0.0`, `tailwindcss: ^4.0.0`, `laravel-vite-plugin: ^2.0.0`)
  - Tailwind CSS v4 on web marketplace routes (`@vite(['resources/css/app.css', 'resources/js/app.js'])`)
  - Admin Dashboard: Stisla Admin template with Bootstrap 4.x and custom stylesheets
  - Vanilla/Blade theme with Fira Design System tokens
- **Next.js Status:** **NOT PRESENT** in repository. Audit confirms zero Next.js configuration or code files (`next.config.js` does not exist; git log confirms no Next.js files in the active branches). Frontend consists of Blade templates (Web) and Flutter (Mobile). Per Hard Constraint #3, Blade remains Blade.

### Module Layout & Where B2B Code Lives
1. **LMS / Academy Module:**
   - Controllers: `app/Http/Controllers/Admin/CourseController.php`, `LessonController`, `QuizController`, `AssignmentController`, `CertificateController`, `LiveClassController`, `EnrollmentController`.
   - Views: `resources/views/admin/courses/`, `lessons/`, `quizzes/`.
2. **Coffee Market Intelligence Module:**
   - Controllers: `app/Http/Controllers/Admin/CoffeeMinimumPriceAdminController.php`, `app/Http/Controllers/Front/MarketPricesController.php`, `app/Http/Controllers/Api/CoffeeMarketChartApiController.php`.
   - Services: `CoffeePriceImportService.php`, `CoffeeMarketSynthesisService.php`, `CoffeeMarketService.php`, `CoffeeTradingChartService.php`.
   - Views: `resources/views/admin/coffee-prices/`, `resources/views/market-prices/`.
3. **Coffee Export Toolkit Module:**
   - Controllers: `app/Http/Controllers/Api/CoffeeToolkitController.php`, `app/Http/Controllers/Front/FreeToolsController.php`.
   - Services: `CoffeeExportService.php` (FOB Calculator, Readiness Assessment, Offer Sheet Generator with DomPDF).
   - Views: `resources/views/free-tools/`.
4. **Coffee B2B Marketplace Module:**
   - Public Web: `app/Http/Controllers/Front/MarketplaceWebController.php` (`routes/web.php` lines 34–50).
   - Admin Management: `app/Http/Controllers/Admin/MarketplaceAdminController.php` (`routes/admin.php` lines 44–59).
   - Mobile API: `app/Http/Controllers/Api/MarketplaceController.php`, `MarketplaceSellerController.php`, `MarketplaceRfqController.php` (`routes/api.php` lines 96–106 and 198–222).
   - Services: `MarketplaceService.php`, `MarketplaceSubscriptionService.php`.
   - Views: `resources/views/marketplace/` (`index.blade.php`, `show.blade.php`, `suppliers.blade.php`, `supplier-show.blade.php`, `rfqs.blade.php`, `create-rfq.blade.php`, `create-listing.blade.php`, `pricing.blade.php`, `dashboard.blade.php`).
   - Admin Views: `resources/views/admin/marketplace/` (`sellers.blade.php`, `seller-detail.blade.php`, `listings.blade.php`, `rfqs.blade.php`, `subscriptions.blade.php`).
5. **Fira AI Assistant:**
   - `app/Http/Controllers/Student/AiChatController.php`, `app/Services/GeminiService.php` (Google Gemini API).

### Authentication, Guards & Authorization
- **Web Guard:** Laravel Fortify session-based authentication (`config/fortify.php`) with OTP support via SMS Ethiopia (`app/Services/SmsEthiopiaService.php`).
- **API Guard:** Laravel Sanctum token guard (`auth:sanctum` in `routes/api.php`).
- **Roles & Permissions:** `spatie/laravel-permission: ^6.22`. Default seeded roles: `admin`, `instructor`, `student`. Additionally, `users` table has an explicit `role` column (`admin`, `instructor`, `student`).

### Queue & Scheduler Setup
- Queue connection: `database` (`jobs` table).
- Scheduled commands: Defined in `routes/console.php` and `app/Console/Commands/` (e.g. `SendLiveClassReminders`).

### Design Tokens & Shared Components
- **Primary Brand Purple:** `#6D1A95`
- **Secondary Palette:** Lavender background `#faf5ff`, active purple tint `#f3e8ff`, verified emerald `#10b981`, amber warning `#f59e0b`.
- **Blade Partials:** `resources/views/partials/admin-header.blade.php`, `admin-sidebar.blade.php`, `admin-footer.blade.php`, `marketplace-navbar.blade.php`.
- **CSS Tokens:** `public/assets/front/css/fira-design-system.css`, `public/assets/admin/css/custom.css`.

---

## 2. Data Model Inventory

| Entity / Concept | Migration File | DB Table Name | Eloquent Model | Relationships & Schema Status |
| :--- | :--- | :--- | :--- | :--- |
| **Organizations** | **MISSING** | None | None | **BLOCKER FOUND:** No `organizations` table exists. Sellers are 1:1 tied to individual users via `marketplace_seller_profiles`. |
| **Org Memberships & Roles** | **MISSING** | None | None | No team seats or member assignment exists. |
| **Seller / Exporter Profiles** | `2026_11_03_220000_create_coffee_marketplace_tables.php` | `marketplace_seller_profiles` | `MarketplaceSellerProfile` | BelongsTo `User` (`user_id`). Holds `company_name`, `company_slug`, `business_type`, `export_license_no`, `tin_number`, `verification_status`, `verified_at`, `license_document_path`. |
| **Verification Tiers & Logs** | Embedded column only | `verification_status` column | None | Embedded string column on `marketplace_seller_profiles` ('pending', 'under_review', 'verified', 'rejected'). No standalone verification history or tier table. |
| **Coffee Lots / Listings** | `2026_11_03_220000` | `coffee_listings` | `CoffeeListing` | BelongsTo `User` (`seller_id`). HasMany `CoffeeListingMedia`. Holds origin, variety, grade, cupping score, bag counts, pricing, incoterm, status. |
| **Listing Media** | `2026_11_03_220000` | `coffee_listing_media` | `CoffeeListingMedia` | BelongsTo `CoffeeListing` (`listing_id`). |
| **Buyer RFQs** | `2026_11_03_220000` | `marketplace_rfqs` | `MarketplaceRfq` | BelongsTo `User` (`buyer_id`). HasMany `MarketplaceQuotation`. Holds target price, quantity bags, shipping terms, status. |
| **Quotations / Bids** | `2026_11_03_220000` | `marketplace_quotations` | `MarketplaceQuotation` | BelongsTo `MarketplaceRfq` (`rfq_id`), BelongsTo `User` (`seller_id`), BelongsTo `CoffeeListing` (`listing_id`). |
| **Inquiries & Samples** | `2026_11_03_220000` | `marketplace_inquiries` | `MarketplaceInquiry` | BelongsTo `CoffeeListing`, `User` (`buyer_id`), `User` (`seller_id`). Partially holds sample fields (`sample_status`, `courier_account`, `tracking_number`). |
| **Deal Rooms & Milestones** | **MISSING** | None | None | No deal room or milestone tables exist. |
| **Documents / Templates** | **MISSING** | None | None | Only ad-hoc file paths on models (`license_document_path`, `CoffeeExportOffer::pdf_path`). No reusable document storage or template model. |
| **Audit Logs** | **MISSING** | None | None | No dedicated audit logging table. |
| **Banned Identities / Reports** | **MISSING** | None | None | No banned identity table. |
| **Marketplace Subscription Plans** | `2026_11_03_220000` | `marketplace_subscription_plans` | `MarketplaceSubscriptionPlan` | Hardcoded column limits: `active_listings_limit`, `rfq_response_limit`, `verified_badge_included`. Not dynamic or entitlement-based. |
| **User Marketplace Subscriptions** | `2026_11_03_220000` | `user_marketplace_subscriptions` | `UserMarketplaceSubscription` | BelongsTo `User`, BelongsTo `MarketplaceSubscriptionPlan`, BelongsTo `Payment`. Scoped to `user_id` rather than an organization. |
| **Legacy LMS Subscriptions** | `2026_08_05_080314` & `2026_10_15_000001` | `subscription_plans`, `user_subscriptions` | None | **FLAGGED AS REMOVED / UNUSED:** Explicitly dropped in migration `2026_10_15_000001_remove_subscription_system.php`. |

---

## 3. Feature Inventory

| B2B Capability | Status | Route / Controller / File Evidence |
| :--- | :--- | :--- |
| **Coffee Catalog & Lot Discovery** | **IMPLEMENTED END TO END** | `GET /marketplace`, `GET /marketplace/listings/{slug}` in `MarketplaceWebController.php`; `GET /api/marketplace/listings` in `MarketplaceController.php`. Verified by tests `CoffeeMarketplaceTest.php`. |
| **Lot Creation & Publishing** | **IMPLEMENTED END TO END** | `GET|POST /marketplace/publish-coffee` in `MarketplaceWebController.php`; `POST /api/marketplace/seller/listings` in `MarketplaceSellerController.php`. Handles multi-image upload, origin validation, draft/active statuses. |
| **Buyer RFQs & Tenders** | **IMPLEMENTED END TO END** | `GET /marketplace/rfqs`, `GET|POST /marketplace/request-coffee` in `MarketplaceWebController.php`; `GET|POST /api/marketplace/rfqs` in `MarketplaceRfqController.php`. Verified by `test_rfq_bidding_and_acceptance_lifecycle`. |
| **Seller Quotations on RFQs** | **IMPLEMENTED END TO END** | `POST /api/marketplace/rfqs/{id}/quote` in `MarketplaceRfqController.php`; quotation status transition (`pending` -> `accepted` / `rejected`). |
| **Sample Requests** | **PARTIAL** | `POST /marketplace/listings/{id}/inquire` records `inquiry_type = 'sample_request'` and `sample_status = 'requested'` in `marketplace_inquiries` table, but has no dedicated sample dispatching/tracking interface or limit gating. |
| **Deal Rooms & Milestones** | **MISSING** | No routes, controllers, or database schema exist for deal rooms or escrow milestones. |
| **FOB Export Cost Calculator** | **IMPLEMENTED END TO END** | `POST /api/coffee-toolkit/calculate`, `POST /free-tools/calculate` in `CoffeeToolkitController.php` & `FreeToolsController.php`. Uses `CoffeeExportService.php`. |
| **Export Readiness Assessment** | **IMPLEMENTED END TO END** | `POST /api/coffee-toolkit/assess` in `CoffeeToolkitController.php` with institutional grading (0–100 score). |
| **Formal Offer Sheet Generator** | **IMPLEMENTED END TO END** | `POST /api/coffee-toolkit/offers`, `GET /api/coffee-toolkit/offers/{id}/pdf` via Barryvdh DomPDF in `CoffeeExportService.php`. |
| **Market Intelligence & Minimum Prices**| **IMPLEMENTED END TO END** | Daily ECTA price datasets, Excel parsing via `CoffeePriceImportService.php`, CSV stream export, Gemini AI market synthesis. |
| **Exporter Verification Workflow** | **PARTIAL** | Basic admin approve/reject buttons in `MarketplaceAdminController::verifySeller`. Lacks multi-tier verification (Tier 1 document, Tier 2 physical inspection, Tier 3 financial vetting) and audit trail. |
| **SaaS Subscription & Gating** | **PARTIAL / BROKEN** | Web pricing page renders seeded plans from `marketplace_subscription_plans`. However, limits are hardcoded, gateway calls in `MarketplaceSubscriptionService` fail, and plan activation illegally auto-verifies trust badges. |

### Baseline Test Suite Results
Executed `php artisan test`:
- **Tests Passed:** 23
- **Tests Failed:** 1 (`CoffeeMarketIntelligenceTest > unauthenticated or non admin cannot access coffee price admin management`: expected 403, received 302 redirect).
- **Total Assertions:** 239 assertions passed.

---

## 4. Existing Billing, Plan, Subscription & Gating Code

### 1. Existing Legacy Plan Model
In `MarketplaceSubscriptionPlan.php`:
- Columns: `active_listings_limit`, `rfq_response_limit`, `featured_slots`, `verified_badge_included`, `analytics_access`, `priority_support`.
- Hardcoded columns violate the requirement that plans must be dynamic data rather than database column schema.

### 2. Hardcoded Gating in `MarketplaceSubscriptionService.php`
- `canPostListing(User $user)`:
  - If unauthenticated or no subscription: limits seller to **1 active lot**.
  - If subscribed: checks `$activeSub->plan->active_listings_limit` (0 = unlimited).
- `canSubmitQuote(User $user)`:
  - If unauthenticated or no subscription: hardcoded to **3 quotes per month**.
  - If subscribed: checks `$activeSub->plan->rfq_response_limit` (0 = unlimited).
  - Uses unindexed `count()` query over `created_at >= startOfMonth()`, vulnerable to race conditions under concurrent requests.

### 3. Critical Trust-for-Sale Violation
In `MarketplaceSubscriptionService::activateSubscription()`:
```php
if ($subscription->plan?->verified_badge_included && $subscription->user->sellerProfile) {
    if ($subscription->user->sellerProfile->verification_status !== 'verified') {
        $subscription->user->sellerProfile->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verification_notes' => 'Auto-verified with active ' . $subscription->plan->name . ' tier.',
        ]);
    }
}
```
**CRITICAL:** Paying for a plan automatically grants a verified exporter badge! This violates Hard Constraint #4: *"TRUST IS NEVER FOR SALE. No plan, price, or entitlement may influence: verification tier, verification approval outcome or speed"*.

---

## 5. Existing Enforcement Points

| Enforcement Point | Current Code Location | Current Check | Work Needed in Phase 2 |
| :--- | :--- | :--- | :--- |
| **Lot Creation (Web)** | `MarketplaceWebController::storeListing` | Checks `$user->canPostMarketplaceListing()` | Replace with `EnsureEntitlement:active_lots` middleware + `EntitlementService::consume()`. |
| **Lot Creation (API)** | `MarketplaceSellerController::storeListing` | Calls `$this->subscriptionService->canPostListing($user)` | Guard with `EnsureEntitlement:active_lots`. Return structured `{code: "limit_reached"}` error. |
| **RFQ Quotation Submission (API)**| `MarketplaceRfqController::submitQuote` | Calls `$this->subscriptionService->canSubmitQuote($user)` | Guard with `EnsureEntitlement:rfq_responses_per_month` (atomic consumption). |
| **RFQ Creation (Buyer)** | `MarketplaceRfqController::store` | Authenticated buyer check only | Free for international buyers. |
| **Toolkit / Calculations** | `CoffeeToolkitController::calculate` | Publicly accessible | Gate advanced toolkit features with `EnsureEntitlement:toolkit_level`. |
| **Offer Sheet Export (PDF)** | `CoffeeToolkitController::downloadOfferPdf` | Authenticated check | Gate with `EnsureEntitlement:contract_templates`. |
| **Team Seats / Invitations** | **Non-existent** | None | Build seat assignment gated by `EnsureEntitlement:seats`. |
| **Deal Room Creation** | **Non-existent** | None | Build deal room creation gated by `EnsureEntitlement:active_deal_rooms`. |

---

## 6. Existing Payment Integrations (Chapa, Telebirr, CBE Birr)

### 1. Gateway Status
- **Configured Gateway:** Chapa is the sole enabled gateway in `PaymentGatewaySetting` (`identifier = 'chapa'`).
- **Telebirr & CBE Birr:** Supported indirectly via Chapa's checkout redirect (Chapa natively accepts Telebirr and CBE Birr in Ethiopia). No direct standalone merchant API integration currently exists for Telebirr or CBE Birr.
- **Card Payments:** International Visa/Mastercard processed through Chapa or legacy Stripe fields on `payments` table.

### 2. Missing Implementation Bug in `PaymentGatewayService`
In `MarketplaceSubscriptionService.php`:
```php
$checkoutResult = $this->paymentGatewayService->initializeChapa([...]);
```
`initializeChapa()` and `verifyChapa()` were **never implemented** on `PaymentGatewayService`! Calling online checkout threw a fatal `MethodNotFoundException`. (Inline cURL implementations existed only in `CourseEnrollmentController` and `TourController`).

### 3. Webhook Handling & Idempotency
- In `SecurityRegressionTest.php`, a Chapa webhook test verifies that signatures are rejected when invalid.
- However, there is no generic, provider-agnostic webhook controller or table to store raw payloads, verify idempotency keys, and handle out-of-order delivery.

---

## 7. Risks & Security Gaps

1. **ARCHITECTURAL BLOCKER — Absence of Organizations Model:**
   - The platform models users as standalone individuals. Subscriptions, lots, and RFQs point directly to `user_id`.
   - Multi-user organizations, team seats (`seats` entitlement), and role separation (Owner, Manager, Staff) cannot function without an `organizations` and `organization_memberships` table.
2. **Trust for Sale Security Hazard:**
   - Plan subscriptions currently auto-verify seller profiles. Must be completely severed immediately.
3. **Race Conditions on Quotas:**
   - Monthly quotation and active lot checks use non-atomic `count()` queries without database locks, enabling quota bypass under concurrent API requests.
4. **IDOR Risks:**
   - Without organization-scoped tenancy checks, users could theoretically query or modify another organization's billing data if routes rely on plain model IDs.
5. **No Audit Logging:**
   - Admin plan updates, price changes, manual subscription overrides, and verification approvals leave no immutable audit trail.

---

## 8. Gap Table: SaaS Requirement vs. Current State vs. Work Needed

| SaaS Requirement | Current State | Work Needed |
| :--- | :--- | :--- |
| **Organizations & Multi-User Tenancy** | No `organizations` table. `marketplace_seller_profiles` tied 1:1 to `user_id`. | **Propose minimal foundation:** Create `organizations` and `organization_memberships` (owner/manager/staff), bridging existing `marketplace_seller_profiles` seamlessly without breaking existing APIs. |
| **Dynamic Plans & Prices** | Hardcoded columns on `marketplace_subscription_plans`. | Create `plans`, `plan_prices` (USD & ETB, monthly & annual), `features` registry, and `plan_entitlements`. Deprecate hardcoded column limits. |
| **Per-Org Entitlement Overrides** | Non-existent. | Create `organization_entitlement_overrides` (org_id, feature_key, value, reason, expires_at, created_by). |
| **Unified Entitlement Enforcement** | Scattered ad-hoc checks in `MarketplaceSubscriptionService`. | Implement single-source `EntitlementService` with atomic `consume()`, `remaining()`, `snapshot()`, plus `EnsureEntitlement` middleware. |
| **Trust Separation Guarantee** | Plans auto-verified sellers (`verified_badge_included`). | Delete auto-verification from billing. Add automated guard test asserting no plan entitlement key touches verification, evidence, or ranking. |
| **Open Deals Survival on Lapse** | No deal room model. Subscriptions had simple active/expired statuses. | Add subscription states (`trialing`, `active`, `past_due`, `grace`, `canceled`, `expired`) and guarantee open deals and documents are never locked or deleted. |
| **Provider-Agnostic Payments** | Ad-hoc cURL in controllers; missing methods on `PaymentGatewayService`. | Create `PaymentProviderInterface` with robust Chapa adapter (supporting Telebirr, CBE Birr, cards), webhook idempotency, and raw payload storage. |
| **Admin & Customer Screens** | Basic pricing view and simple admin table. | Build dynamic public comparison page, org billing dashboard, limit-reached prompts, downgrade wizard, and full admin plan/override management. |

---

## Next Steps: Foundation Proposal for Phase 2

Because the audit identified that no `organizations` or `organization_memberships` table exists, we will establish the minimal, non-breaking foundation:
1. Create `organizations` table and `organization_memberships` table (`user_id`, `organization_id`, `role: owner|manager|staff`).
2. Add nullable `organization_id` to `marketplace_seller_profiles` and backfill each existing profile into an organization where the profile's `user_id` is the `owner`. Existing queries filtering by `user_id` continue working with zero disruption.
3. Proceed with the complete Phase 2 dynamic SaaS schema (`plans`, `plan_prices`, `features`, `plan_entitlements`, `organization_entitlement_overrides`, `subscriptions`, `subscription_events`, `usage_counters`, `invoices`, `payments`).

