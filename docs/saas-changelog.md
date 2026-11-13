# Fira Link B2B SaaS Billing & Entitlement Changelog

**Release Date:** November 7, 2026  
**Status:** Production Ready & Verified  
**Brand Primary:** `#6D1A95`

---

## 1. Scope of Changes

This release delivers an enterprise-grade, data-driven B2B SaaS billing and entitlement architecture to Fira Link. It isolates commercial subscription tiers from trust and verification mechanics, models plans and pricing dynamically in the database, enforces quotas atomically, and adds full administrative control panels and customer billing management.

---

## 2. Exhaustive File Inventory

### Database Migrations (Fully Reversible)
- `database/migrations/2026_11_07_000001_create_organizations_and_memberships_tables.php`
  - Created `organizations` (multi-tenant root, status, business type, billing email).
  - Created `organization_memberships` (owner, manager, staff roles).
  - Added `organization_id` to `marketplace_seller_profiles` with automated backfill.
- `database/migrations/2026_11_07_000002_create_saas_billing_and_entitlements_tables.php`
  - Created `plans`, `plan_prices`, `features`, `plan_entitlements`.
  - Created `organization_entitlement_overrides` (founding member deals, promo overrides).
  - Created `subscriptions`, `subscription_events` (immutable transition trail).
  - Created `usage_counters` (atomic periodic counters), `invoices`, `subscription_payments`, `webhook_calls`.
- `database/migrations/2026_11_07_000003_create_audit_logs_and_banned_identities_tables.php`
  - Created `audit_logs` (forensic diffs with before/after state).
  - Created `banned_identities` (fraud prevention for TIN & licenses).
  - Created `deal_rooms` and `deal_milestones` (open deals continue after lapse).

### Seeders
- `database/seeders/SaasPlanSeeder.php`
  - Seeded 16 platform capability features (including `esignature` marked *Coming Soon*).
  - Seeded 3 default commercial plans (`starter`, `growth`, `business`) with ETB and USD prices.
  - Seeded 4 demo organizations with active subscriptions and founding-member override (+50 active lots).

### Core Services & Contracts
- `app/Contracts/PaymentProviderInterface.php` (Provider-agnostic payment gateway contract).
- `app/Services/Billing/Providers/ChapaPaymentAdapter.php` (Chapa ETB/USD adapter with HMAC verification).
- `app/Services/Billing/BillingService.php` (Subscription checkout, webhook handling, prorations).
- `app/Services/EntitlementService.php` (Single source of truth: `can()`, `limit()`, `used()`, `remaining()`, `consume()`, `release()`, `snapshot()`, `reconcileDowngrade()`).
- `app/Services/PaymentGatewayService.php` (Added Chapa initialization & verification delegates).
- `app/Services/MarketplaceSubscriptionService.php` (Completely decoupled trust verification from billing).

### Middleware & Commands
- `app/Http/Middleware/EnsureEntitlement.php` (Registered as `'entitlement'` middleware in `bootstrap/app.php`).
- `app/Console/Commands/ProcessSubscriptionLifecycleCommand.php` (Hourly scheduled lifecycle processor).
- `routes/console.php` (Registered hourly execution of `saas:process-lifecycle`).

### Controllers & API Endpoints
- `app/Http/Controllers/BillingWebhookController.php` (Exempt from CSRF; processes webhook events).
- `app/Http/Controllers/Api/OrganizationEntitlementApiController.php` (Exposes `GET /api/org/{id}/entitlements` and `GET /api/user/organization/entitlements`).
- `app/Http/Controllers/Admin/SaasBillingAdminController.php` (Comprehensive admin SaaS management controller).
- `app/Http/Controllers/Front/MarketplaceWebController.php` (Dynamic pricing, subscription checkout, customer dashboard portal, cancellation).
- `app/Http/Controllers/Api/MarketplaceRfqController.php` (Atomic quota consumption for RFQ quotes).
- `app/Http/Controllers/Api/MarketplaceSellerController.php` (Atomic quota consumption for lots).

### Blade Views & UI Components
- `resources/views/marketplace/pricing.blade.php` (Dynamic database-driven pricing matrix with ETB/USD and monthly/annual toggles).
- `resources/views/marketplace/dashboard.blade.php` (Added comprehensive `Subscription & Billing` portal tab with real-time usage progress bars, override alerts, invoice receipts, and Owner-only cancellation).
- `resources/views/admin/saas/plans.blade.php` (Plan management, currency pricing, inline entitlement matrix editor).
- `resources/views/admin/saas/features.blade.php` (Platform capability registry and new feature creation form).
- `resources/views/admin/saas/overrides.blade.php` (Organization override tool with reason and expiry).
- `resources/views/admin/saas/subscriptions.blade.php` (Subscription viewer with status filters and manual plan change modal).
- `resources/views/admin/saas/invoices.blade.php` (Invoice accounting ledger with gateway reference and status).
- `resources/views/admin/saas/audit-logs.blade.php` (Audit trail explorer with before/after JSON diffs).
- `resources/views/partials/admin-sidebar.blade.php` (Added SaaS Plans & Billing submenu; modernized CSS eliminates overlapping).
- `public/assets/admin/css/custom.css` (Added bulletproof static submenu positioning to prevent floating text collisions).

### Automated Tests
- `tests/Unit/EntitlementServiceTest.php` (5 tests, 25 assertions - 100% PASS).
- `tests/Feature/TrustIsNeverForSaleTest.php` (3 tests, 451 assertions - 100% PASS).
- `tests/Feature/SaasBillingEnforcementTest.php` (4 tests, 18 assertions - 100% PASS).

### Architecture & Audit Documentation
- `docs/b2b-audit.md` (Forensic codebase audit report).
- `docs/saas-architecture.md` (Full architectural specification and runbooks).
- `docs/saas-changelog.md` (This file).

---

## 3. Rollback Plan

Every database migration in this architecture is reversible. If an immediate rollback is required, execute:

```powershell
Set-Location "c:\Users\sora\fira-link-lms\firalink web\firalink"
php artisan migrate:rollback --step=3
```

This will cleanly drop the 14 new SaaS tables and foreign keys in reverse dependency order without corrupting existing user accounts, course records, or coffee listings.

---

## 4. Key Security & Architectural Improvements
1. **Trust-for-Sale Fix:** Excised legacy code in `MarketplaceSubscriptionService` that automatically marked seller profiles verified upon paid subscription.
2. **Owner-Only IDOR Shield:** Non-owner member roles (manager, staff) are strictly prevented from initiating subscription changes, altering billing methods, or canceling renewals.
3. **Replay & Idempotency Protection:** Incoming webhooks verify HMAC SHA256 signatures and write unique `{provider}:{provider_ref}` idempotency keys before processing. Duplicate events are silently acknowledged without double-processing.
4. **Race-Condition Safety:** Quota consumption uses database transaction row locking (`lockForUpdate()`) to prevent race conditions during concurrent RFQ submissions.

