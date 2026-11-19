# Fira Link — functionality and gap analysis (2026-11-19)

Read-only audit of the mobile app (`firalink flutter/`), the API and the web (`firalink web/firalink/`).
No app code, config, migrations, database or git state was changed. Only files under `docs/audits/` were written.

**How the evidence was gathered**

- Static reading of routes, controllers, services, policies, migrations, seeders, Flutter services, providers and screens.
- `php artisan route:list --path=api --json` (327 API routes), matched by script against 225 path literals in `lib/services/`.
- Full test suites: Laravel on `DB_CONNECTION=sqlite DB_DATABASE=:memory:`, Flutter `flutter test`, `flutter analyze`.
- A throw-away **runtime probe** (kept outside the repo, in the session scratchpad). It boots the app on an in-memory
  SQLite DB with `Http::preventStrayRequests()` and dummy Chapa/Gemini keys, creates users and businesses, and sends real
  requests with real Sanctum bearer tokens. No outbound calls, no real database, `.env` values never printed.
- **The code under test is the working tree, not `HEAD`.** 105 files are modified and 20 untracked (for example
  `screens/search/`, `Console/Commands/Qa/`, five new Flutter test files). See GAP-023.

---

## 1. Executive summary

1. The trust core built in slices 0–8 is real and well tested: 286 Laravel and 164 Flutter tests pass, ownership checks on
   deal rooms, samples, receipts, bookings and documents hold, and suspension is applied consistently.
2. **Money can be taken without paying.** A guest can post an unsigned "payment succeeded" webhook and the server marks the
   invoice paid and activates a 30-day paid plan. Proven at runtime (GAP-001).
3. **Live Chapa is not blocked** while its leaked secret key is still valid. Courses, events, tours and seller plans all use
   it; only samples are switched off (GAP-003).
4. **The new Search screen is fake**: hard-coded lots with prices and a named exporter labelled "Verified • Tier 2". On a
   trust product this is the most damaging kind of placeholder (GAP-002).
5. **App-to-API paths are clean** (every app path and method exists). The contract break is authentication: 17 public
   routes read the user through the `web` guard, so a signed-in app user is treated as a guest.
6. Because of that, **Export Docs Studio does not work at all in the app** (401 on create, empty lists), the export
   readiness check **crashes with 500**, and **any guest can read any business's plan and usage** (GAP-004, 005, 006).
7. **Paid course video is broken both ways**: anyone can stream a paid video by naming a free lesson's id, and enrolled
   learners on mobile get no token, so self-hosted paid videos should fail to play (GAP-007, 008).
8. B2B events (quote received, sample requested, document reviewed) only create **in-app database rows** — no push,
   email or SMS — so sellers will miss buyers (GAP-012). There is still **no way to raise a dispute** in the app (GAP-013).
9. Amharic is incomplete outside the new slices: 334 hard-coded English strings in 70 Flutter files, 56 places that show raw
   exception text, and an API that answers in English (GAP-018).
10. Production setup has sharp edges: a default seeder with `password` admin accounts, release builds that silently use
    debug signing, no minimum-app-version gate, tokens that never expire, and secrets still in git history.

## 2. Readiness verdict: **Not ready** (not even for a public beta that takes money)

1. **Payment integrity is broken**: forged webhooks activate paid plans, and live Chapa runs on a leaked secret key.
2. **The app shows invented trust data**: fake lots, prices and a fake "Verified • Tier 2" exporter in Search.
3. **Several reachable features fail for signed-in app users**: Export Docs Studio, readiness check, paid video playback,
   disputes — and the app has no global handling for expired sessions.

A **closed beta with invited businesses, payments switched off and Search hidden** is realistic after fix slices A–C
(section 8). That is roughly 2–3 weeks of focused work by the estimates in the register.

## 3. Feature map vs promise

Legend: **Complete** (designed, built, wired, tested) · **Partial** · **Server only** · **UI only** · **Mocked** · **Missing** · **Broken**.
"Flows" = a screen exists in `docs/design/flows/canvas.json` (54 boards). Web column follows the CLAUDE.md rule (web only for public seller page and admin review).

| Capability | Flows | Server | Flutter | Web | Tests | Status | Notes |
|---|---|---|---|---|---|---|---|
| Sign in (phone OTP, email, forgot/reset) | yes (S-004/005, 7 boards) | yes | yes | yes | Auth/* (5 files), slice1 | **Complete** | No global 401 handling in app (GAP-014) |
| Language picker, onboarding | yes | n/a | yes | n/a | localization_test | **Complete** | |
| Intent, business setup, workspaces, team | yes (S-102/103/105) | yes | yes | n/a | WorkspacesTest, slice2 | **Complete** | Legacy single `intent` still accepted |
| Verification tiers, document capture, review | yes (S-021, S-108) | yes | yes | admin review | VerificationTiersTest, slice3 | **Partial** | Upload 500s if admin mail fails (GAP-011) |
| Public seller page | yes (S-110) | yes | yes | yes | VerificationTiersTest | **Complete** | |
| Lots, evidence photos, lot levels | yes (S-111, Create a lot) | yes | yes | n/a | LotsTest, slice4 | **Complete** | `lot_lifecycle_screen`, `lot_photo_capture_screen` are dead code |
| Marketplace browse and filters | yes | yes | yes | n/a | CoffeeMarketplaceTest | **Complete** | |
| Search | yes (Search board) | per-type `search` params exist | hard-coded results | n/a | none | **Mocked** | GAP-002 |
| RFQ, quotes, compare | yes (RFQ, S-115, Answer a request) | yes | yes | removed | TradeTest, slice5 | **Complete** | |
| Samples, seal ID, tracking | yes (S-118/119, Seller sample requests) | yes | yes | n/a | SamplesTest, slice6 | **Partial** | Paid samples: mock refused in production → "not available" (by design) |
| Deal room and payment milestones | yes (S-117, S-122) | yes | yes | n/a | DealRoomTrustTest, slice7 | **Partial** | No dispute path (GAP-013); `finance` role not seeded (GAP-024) |
| Business chat and off-platform warning | yes (S-129) | yes | yes | n/a | TradeTest, slice7 | **Complete** | `ScamWarningSheet` never shown |
| Report and block | yes (S-128) | yes | yes | admin | slice7 | **Complete** | |
| Events | yes (4 boards) | yes | yes | yes | EventsToursAcademyTest, slice8 | **Complete** | |
| Tours | yes (4 boards) | yes | yes | yes | EventsToursAcademyTest, slice8 | **Complete** | |
| Academy (courses, lesson player) | yes (3 boards) | yes | yes | yes | slice8 (progress only) | **Partial** | Paid video bypass and playback (GAP-007/008) |
| Payments — Chapa and bank slip | yes (in flows) | yes | yes | admin | EventsToursAcademyTest | **Partial** | Bookings are solid; billing webhook is not (GAP-001) |
| Seller plans / SaaS billing | no board | yes | yes | yes | SaasBillingEnforcementTest | **Broken** | GAP-001, GAP-009 |
| Notifications (in-app, push) | yes | B2B: database only | list screen | n/a | none | **Partial** | GAP-012 |
| Fira AI chat | yes (Orb) | yes, off by flag | yes | n/a | MarketAiTest | **Partial** | Canned answers presented as AI (GAP-010) |
| Coffee market data | (Toolkit) | yes, sources off | yes | yes | MarketDataTest | **Complete** | Prices show Unavailable until a source is approved |
| Export toolkit, FOB calculator | yes | yes | yes | n/a | CoffeeMarketIntelligenceTest | **Partial** | Readiness check 500s, hub never shows own data (GAP-005) |
| Export Docs Studio | no board | yes | yes | yes | ExportDocsStudioTest (`actingAs`) | **Broken (mobile)** | GAP-004 |
| Profile and settings | yes | yes | yes | n/a | profile_activity_test | **Complete** | |
| Delete account | Settings board | request + admin approval | yes | admin | none | **Partial** | B2B data not removed (GAP-016) |
| Admin and reviewer tools | n/a | web | n/a | yes | various | **Server/web only** | 2FA for staff in place |
| Instructor tools | no board | yes (55 routes) | some screens | yes | none | **Partial** | Not in launch scope |

## 4. Finding counts

**By severity:** P0 **4** · P1 **16** · P2 **14** · P3 **12** · total **46**

| Area | P0 | P1 | P2 | P3 | Total |
|---|---|---|---|---|---|
| 2 Contract / auth guard | – | 3 | 1 | – | 4 |
| 3 Button-to-DB traces | – | 3 | – | 1 | 4 |
| 4 Fake / mock | 1 | 1 | – | 2 | 4 |
| 5 Roles and access | – | 1 | 2 | 1 | 4 |
| 6 States and failure handling | – | 2 | 2 | – | 4 |
| 7 Payments | 2 | 1 | 2 | 2 | 7 |
| 8 Notifications and jobs | – | 2 | – | 1 | 3 |
| 9 Localization | – | 1 | 1 | 1 | 3 |
| 10 Code health | – | – | 2 | 2 | 4 |
| 11 Security and privacy | 1 | 1 | 3 | 2 | 7 |
| 12 Production readiness | – | 1 | 1 | – | 2 |
| **Total** | **4** | **16** | **14** | **12** | **46** |

(Each finding is counted once, under its main area. IDs are stable labels; 017, 022 and 025–029 are unused.
Full rows: `gap-register-2026-11-19.csv`.)

## 5. Top 15 fixes, in order

| # | ID | Fix | Effort |
|---|---|---|---|
| 1 | GAP-001 | Billing webhook: reject a missing signature, re-verify with Chapa server-to-server, check amount and currency against the invoice, take the plan from the invoice, 404 unknown providers | S |
| 2 | GAP-003 | Rotate the Chapa keys now; add a server `CHAPA_ENABLED` flag (default false, fail closed) for every Chapa path | S |
| 3 | GAP-009 | Remove the "simulated checkout" success path outside `local`/`testing`; make `CHAPA_MODE` default `live` in production | S |
| 4 | GAP-002 | Search: remove hard-coded results; call real search endpoints or hide the screen | S–M |
| 5 | GAP-021 | Rotate `APP_KEY`, `GEMINI_API_KEY`, server and admin passwords from `scratch/` (CLAUDE.md order) before real ID numbers and payout accounts are stored | S |
| 6 | GAP-006 | `/api/org/{id}/entitlements`: require `auth:sanctum` and membership | S |
| 7 | GAP-004/005 | Put export-docs and coffee-toolkit user routes behind `auth:sanctum` (or read `auth('sanctum')`); add bearer-token tests | S |
| 8 | GAP-007 | Video stream: authorise from the lesson whose `video_path` matches the requested file, never from a free `lesson_id` | S |
| 9 | GAP-008 | Paid video playback: signed, short-lived stream URLs from the server (no bearer needed) | M |
| 10 | GAP-013 | Wire "Raise a dispute" into the deal room (screen exists, endpoint exists) | S |
| 11 | GAP-012 | Push (FCM) for B2B events: quote, sample, document reviewed, deal step; route push taps to the right screen | M |
| 12 | GAP-014 | One HTTP client with timeout, 401 → sign-in, and no sign-out on 5xx | M |
| 13 | GAP-011 | Send the admin alert after commit and never fail the upload on mail errors; queue it | S |
| 14 | GAP-016 | Account deletion: handle businesses, verification files, payout accounts, chats; mark the request approved only after success | M |
| 15 | GAP-019 | Guard `AdminSeeder` (no `password` accounts outside local), fail release builds without the release keystore, add a minimum-version gate | S |

## 6. Findings by area

Format: **ID · Severity · Platform · Feature** — evidence — what is wrong — impact — fix — effort — depends on.

### Area 2 — App-to-API contract

Path and method match: 225 service path literals → 0 missing routes, 0 wrong methods (two script hits were false positives
from neighbouring lines, checked by hand in `services/auth_service.dart:139-156`). Old single-field `intent` is still accepted
(`OrganizationController.php:471-490`). The CLAUDE.md `_mockResponse` fallback in `services/marketplace_service.dart` **is gone**.

The break is authentication. A script listed every public API route whose controller reads the user through
`Auth::id()`, `$request->user()` or `auth()->user()` without the `sanctum` guard: **17 routes** (14 export-docs,
`coffee-toolkit/assess`, `coffee-toolkit/hub-summary`, `org/{id}/entitlements`). The default guard is `web`
(`config/auth.php:17`), so bearer tokens are ignored there.

**GAP-004 · P1 · Mobile/Server · Export Docs Studio**
- Evidence: `routes/api.php:524-548` (no `auth:sanctum`), `ExportDocsApiController.php:42-52`; probe: bearer `POST /api/export-docs/shipments` → `401 auth_required`, `GET …/shipments` → `200` empty list.
- Wrong: every signed-in app user is treated as signed out. Lists show empty success, create and AI calls fail.
- Impact: a ~3,000-line feature (`export_docs_studio_screen.dart`, `export_doc_editor_screen.dart`) is unusable; the empty state looks like "you have no documents".
- Fix: move the user routes into the `auth:sanctum` group; keep templates and calculate public. Add tests with `withToken()` instead of `actingAs()`.
- Effort S.

**GAP-005 · P1 · Mobile/Server · Export readiness check, toolkit hub**
- Evidence: `CoffeeToolkitController.php:271` (`evaluateReadiness(Auth::id(), …)`), `:39`; probe: `POST /api/coffee-toolkit/assess` → `500 TypeError … must be of type int, null given`.
- Wrong: the check crashes for everyone using the app; the hub never shows the user's own calculations or last assessment.
- Fix: route under `auth:sanctum` (or allow guest scoring with a nullable user id); test both. Effort S.

**GAP-006 · P1 · Server · Business plan and usage leak (IDOR)**
- Evidence: `OrganizationEntitlementApiController.php:24-35` (`if ($user && …)` skips the check when the user is null); probe: guest `GET /api/org/{B}/entitlements` → `200` with `organization, plan, subscription, entitlements, usage, overrides`; user A (not a member) → `200`.
- Impact: anyone can enumerate every business's plan, subscription and usage counts by id.
- Fix: `auth:sanctum` + member-or-admin check, 404 otherwise; test guest and non-member. Effort S.

**GAP-030 · P2 · Server · Public phone OTP sender**
- Evidence: `AuthController.php:276-287` under `routes/api.php:50` (throttle 10/min per IP only).
- Wrong: anyone can send verification SMS to any number. A per-number cooldown exists, but rotating numbers and IPs costs SMS money.
- Fix: require a signed-in user or a started sign-in flow; add a daily cap per IP and per prefix. Effort S.

### Area 3 — Button-to-database traces

Slices 1–8 actions were traced through service → route → controller → policy/service → DB, and each has a feature test
(sign-in, create business, upload document, create lot, add evidence, browse lots, post RFQ, quote, accept, request sample,
deal room steps, report and block, book event and tour, bank slip, switch workspace). Breaks found:

**GAP-013 · P1 · Mobile · Raise a dispute**
- Evidence: `screens/deal_room/dispute_resolution_screen.dart` is imported nowhere; `services/deal_room_service.dart:167-175` has `raiseDispute` with no caller; route `POST /api/deal-rooms/{id}/dispute` exists. First reported 2026-11-16 (`ui-ux-audit-2026-11-16.md`), still open.
- Impact: a buyer who paid a deposit has no in-app recourse; the public seller page shows "Open disputes" (`docs/design/flows/PublicVerify.dc.html`).
- Fix: entry point on the deal room and milestone screens, localized, with evidence upload. Effort S.

**GAP-008 · P1 · Mobile · Play a paid self-hosted lesson**
- Evidence: server builds `url('api/video/stream')?path=…&lesson_id=…` with no token (`StudentCourseController.php:692`); app plays it with `VideoPlayerController.networkUrl(Uri.parse(resolvedUrl))` and no headers (`course_access_screen.dart:214`); server requires a Sanctum user for non-free lessons (`FileController.php:57-70`).
- Wrong: an enrolled learner should get 401 and see `Failed to load video: … URL: …` (`course_access_screen.dart:250`). YouTube/Vimeo lessons are not affected.
- Not run on a device — listed in "Not verified".
- Fix: server returns a short-lived signed URL (`URL::temporarySignedRoute`), stream route accepts the signature. Effort M. Depends on GAP-007.

**GAP-016 · P1 · Server · Delete account**
- Evidence: `AccountDeletionService.php:18-55` deletes tokens, learning data, bookings and the user — not businesses, memberships, verification documents (owner ID scans on the private disk), payout accounts, trade chats or device tokens. `AccountDeletionController.php:112-126` saves `approved` **before** deleting, so a failure leaves a request that can never be retried. `marketplace_samples.buyer_id` cascades on delete (`2026_11_12_000001_enhance_trade_and_workspace_schema.php:97`), erasing the seller's sample and seal record.
- Impact: personal data kept after a "deleted" account (privacy law, Play data-deletion policy); counterparties lose records.
- Fix: a deletion plan per table (remove personal files, anonymise trade records, transfer or close owned businesses), status set only on success, test. Effort M.

**GAP-040 · P3 · Mobile · Unreachable screens**
- Evidence: never imported: `auth/reset_password_screen.dart`, `bundles/bundle_checkout_screen.dart`, `coffee_toolkit/widgets/coffee_candlestick_chart.dart`, `deal_room/dispute_resolution_screen.dart`, `home/home2_screen.dart`, `home/widgets/market_strip.dart`, `instructor/all_instructors_screen.dart`, `marketplace/lot_lifecycle_screen.dart`, `marketplace/lot_photo_capture_screen.dart`, `profile/payment_detail_screen.dart`, `trust_safety/scam_warning_sheet.dart`, `trust_safety/suspended_license_screen.dart`, `widgets/fira_ai_fab.dart`. `/ai-learning-path` and `/ai-suggestions` routes have no caller.
- Fix: delete or wire them (dispute and scam warning should be wired). Effort S.

### Area 4 — Fake, mock and placeholder scan

**GAP-002 · P0 · Mobile · Search (uncommitted file)**
- Evidence: `screens/search/search_screen.dart:357-370` hard-coded lots "Sidama Bensa G1 Washed — $3.45/lb • 320 Bags", "Yirgacheffe Kochere Natural G1 — $3.80/lb"; `:395-397` exporter "Oromia Coffee Farmers Coop Union" badge **"Verified • Tier 2"**; `:422` a course; `:28-34` fixed "recent searches". Opened from the home search bar (`home_screen.dart:160`). The file has never been committed.
- Impact: users see invented prices and a verification badge no reviewer granted, on a name that resembles a real union. This breaks the core promise ("verification is earned, never invented").
- Fix: call `/api/marketplace/listings?search=`, `/api/marketplace/exporters?search=` and `/api/courses?search=`; show the real empty state. Until then, hide the categories with no endpoint. Effort S–M.

**GAP-010 · P1 · Server · Fira AI canned answers**
- Evidence: `GeminiService.php:203-204` returns `generateIntelligentFallback()` when every model fails; `:253-297` are fixed paragraphs (Sidama, Yirgacheffe, profit, ECTA…) returned as if the model wrote them.
- Impact: when Gemini is on but failing, users get scripted answers without being told. Gemini is off today (`GEMINI_ENABLED` false), so not visible yet.
- Fix: return an "AI is unavailable, try again" state with a flag the app can show. Effort S.

**GAP-041 · P3 · Mobile · Simulated AI and "coming soon"**
- `ai_learning_path_screen.dart:136` ("Simulate AI generation delay") then a fixed UI/UX-designer path for every career (`:58-120`) — unreachable today. `widgets/fira_ai_fab.dart:130` "coming soon" snack — unimported. `marketplace_pricing_screen.dart:374` amber "Coming Soon" label (reachable; amber is off-brand). `ai_suggestions_provider.dart:103` forces a 2 s wait. `screens/dashboard/assignment_detail_screen.dart:401` fixed English description when none is given.
- Fix: delete dead screens; replace the label with a localized token-coloured state. Effort S.

**GAP-042 · P3 · Server · Dead fake-analysis code and legacy plan copy**
- `GeminiService.php:422-760` `generateDeterministicDocumentAnalysis()` (canned "forensic audit") has no caller. `models/coffee_export_models.dart:381-386` fallback "$2.430 – $10.120 / lb … Official weekly minimum prices set by ECTA" — model unused. `MarketplaceSeeder.php:40-46,112` sells "Verified Exporter trust badge" and a "Verified Buyer" plan (not in `DatabaseSeeder`; plans API uses the SaaS `Plan` table, so not shown today).
- Fix: delete. Effort S.

**Flags that fake success, and whether production refuses them**

| Flag / path | Where | Production |
|---|---|---|
| `SAMPLE_PAYMENTS_MOCK` | `SampleService.php:119` | Refused in production (checked) |
| Chapa simulated checkout | `ChapaPaymentAdapter.php:74-81` | **Not refused** when `CHAPA_MODE=sandbox` (GAP-009) |
| Chapa simulated verify | `ChapaPaymentAdapter.php:134-143` | Only `local`/`testing` |
| `AUTH_TEST_OTP_CODE` | per CLAUDE.md | Only `APP_ENV=local` (tested in `Auth/*`) |
| `QA_TOOLS_ENABLED` | `config/qa.php`, `Console/Commands/Qa/*` | Commands refuse production (`QaToolsTest`) |
| FCM "simulated" log | `FcmService.php:162-163` | Logs title and body when FCM is not configured; no fake success returned |
| `DEMO_MODE` theme revert | `ThemeService.php:157` | Theme only |

Seeders: `LocalDemoSellerSeeder` is local-only (`:33`). **`AdminSeeder` is not guarded** (GAP-019).

### Area 5 — Roles and access

Checked: deal room view (`DealRoomPolicy`), receipts (`PaymentController.php:64-65`), event and tour bookings
(`EventController.php:256-259`, `TourController.php:349-353, 396-398`), certificates, verification document delete
(`OrganizationVerificationController.php:216-221`), notifications, habits, members list/add (probe: 403 for a non-member).
All hold. Suspension blocks trade and shows "suspended" on the public page (`PublicSellerProfile.php:36-42`,
`Organization.php:94-96`). Plans never change verification (`TrustIsNeverForSaleTest`). Remaining gaps:

**GAP-007 · P1 · Server · Paid video bypass**
- Evidence: `FileController.php:46-57` — if `lesson_id` is given, that lesson decides; it is never checked against the requested `path`. Any free lesson id + a paid video path → streamed.
- Impact: paid course content can be downloaded by anyone who can see one free lesson.
- Fix: find the lesson by `video_path`; reject a `lesson_id` whose path does not match. Effort S.

**GAP-024 · P2 · Server · `finance` and `reviewer` roles are not seeded**
- Evidence: `RoleSeeder.php:45-47` creates `admin`, `instructor`, `student` only; `DealRoomPolicy.php:43` needs `finance`; only `Console/Commands/Qa/QaSeed.php:50` creates `finance`/`reviewer`.
- Impact: in production only admins can confirm deal steps and review documents unless someone creates the roles by hand.
- Fix: seed the roles; admin UI to assign them. Effort S.

**GAP-031 · P2 · Server · Missing policies (CLAUDE.md "every endpoint gets a policy")**
- Inline checks without policies: verification documents, organizations and members, export docs, notifications, community, chat, habits, focus, coffee toolkit. They work today; the rule is not met. Effort M.

**GAP-043 · P3 · Server · Tier shown to a suspended owner**
- `OrganizationVerificationController.php:139` shows the tier without the suspended check used elsewhere. Effort S.

### Area 6 — States, edge cases and failure handling

**GAP-014 · P1 · Mobile · Session expiry and server errors**
- Evidence: no global 401 handler (only `fira_error_state.dart:23` shows "session expired" text; nothing routes to sign-in). `auth_provider.dart:41-52` clears the token on **any** non-200 from `/api/user`, including 429, 500 and 503.
- Impact: after `auth:flag-weak-passwords --force` or a password change on another device, the app keeps a dead session; during a short server outage every user who opens the app is signed out.
- Fix: one HTTP client: timeout, 401 → clear and go to sign-in, 5xx → keep session and show retry. Effort M.

**GAP-015 · P1 · Mobile · Raw errors and missing timeouts in older screens**
- Evidence: 56 places show exception text, e.g. `auth_provider.dart:133` (`'An error occurred: $e'`), `checkout_screen.dart:130,199,242`, `course_access_screen.dart:250` (shows the URL). Services without any timeout or `guardedRequest`: `export_docs_service` (13 calls), `student_course_service` (11), `notification_service` (9), `course_discussion_service` (8), `coffee_toolkit_service` (8), `profile_service`, `community_service`, `habit_service`, `payment_service`, `ai_chat_service`, `home_service`, `dashboard_service`.
- Impact: English stack text, endless spinners on slow networks.
- Fix: route these services through the same client as GAP-014; map errors to localized messages. Effort M.

**GAP-032 · P2 · Server · Duplicate verify calls**
- `CourseEnrollmentController.php:2094-2190` has no lock: two simultaneous verifies can both pass the "not completed" check and run `processRevenueShare` twice. Effort S.

**GAP-033 · P2 · Mobile · Push token left on server after logout**
- `auth_provider.dart:335-346` revokes the API token first, then `deleteToken()` calls `DELETE /api/user/device-token` with the revoked token (401). FCM's own `deleteToken()` limits the harm. Fix: unregister first. Effort S.

### Area 7 — Payments and money safety

Booking payments (events, tours) are solid: server-to-Chapa verify with amount and currency match
(`BookingPaymentService.php:114-129`), bank slips on the private store, finance confirm with duplicate-reference check
(`:132-141`), reject with reason. Payout account changes need step-up and a 48-hour wait (slice 7).

**GAP-001 · P0 · Server · Forged billing webhook activates paid plans**
- Evidence: `ChapaPaymentAdapter.php:173` checks HMAC only `if ($this->webhookSecret && $signature)` — a request **without** the header is valid. `BillingService.php:189-232` then marks the invoice paid and activates the plan with no amount check and no server-to-server verify. `getProvider()` maps any unknown provider name to Chapa (`:36-43`). The `tx_ref` is predictable (`'SUB-'.$org->id.'-'.$plan->id.'-'.time()`, `:106`) and returned to the client (`:142`).
- Runtime proof (in-memory DB): guest `POST /api/billing/webhook/chapa {tx_ref, status:"success", amount:1}` → `200 Subscription activated`; invoice `pending → paid`; subscription active for 30 days; one payment row recorded.
- Impact: any seller can activate paid plans without paying, from the app's own subscribe response.
- Fix: reject missing signature; call Chapa verify before marking paid; compare amount and currency to the invoice; plan from the invoice; unknown provider → 404; tests for each. Effort S.

**GAP-003 · P0 · Server · Live Chapa is not blocked while the key is leaked**
- Evidence: no server flag gates Chapa; it is on when the admin gateway setting is on (`PaymentGatewaySettingSeeder.php:104` enables Chapa by default; `Front/EventController.php:111`). Used by courses, events, tours and seller plans. Only samples are blocked (`SampleService.php:119`). CLAUDE.md records `CHAPA_SECRET_KEY` as leaked.
- Impact: whoever holds the leaked secret can use the merchant account's API with full rights.
- Fix: rotate in the Chapa dashboard today; add `CHAPA_ENABLED` (default false) checked in `ChapaPaymentAdapter`, `ChapaClient` and the course controller. Effort S. Your decision (D-1).

**GAP-009 · P1 · Server · "Simulated" checkout can reach production**
- Evidence: `ChapaPaymentAdapter.php:74-81` returns `success:true` and a return URL with `status=success&simulated=1` when Chapa refuses **and** `mode === 'sandbox'`, in any environment. `.env.example:93` ships `CHAPA_MODE=sandbox`; the adapter defaults to sandbox (`:27`).
- Impact: with a revoked or wrong key the subscribe flow looks successful and lands on `marketplace.dashboard?subscribed=…`.
- Fix: simulation only in `local`/`testing`; production fails closed. Effort S.

**GAP-034 · P2 · Server · Seller plan verify has no amount check, public callback**
- `MarketplaceSellerController.php:373-406`: Chapa verify success → completed and subscription activated without comparing amount or currency; `GET /api/marketplace/subscribe/chapa/callback` is public. Effort S.

**GAP-035 · P2 · Server · Nine legacy gateway verify routes**
- `routes/api.php` → `payments/{bkash,mollie,paypal,paystack,razorpay,sslcommerz,stripe,xpay}/verify`, plus Razorpay/Stripe/PayPal code in `checkout_screen.dart`. Unused for Ethiopia, not reviewed here; attack surface. Fix: remove or switch off by config. Effort S.

**GAP-044 · P3 · Server · Small payment checks**
- `BookingPaymentService.php:121` accepts a Chapa answer with no amount (`$v['amount'] === null`). `CourseEnrollmentController.php:2149` checks amount but not currency. Effort S.

**GAP-045 · P3 · Server · Exchange rate hard-coded**
- `BillingService.php:115` `'exchange_rate' => $currency === 'USD' ? 120.00 : 1.00` "(UNVERIFIED)". Effort S.

### Area 8 — Notifications, jobs and background work

Scheduler (`routes/console.php:12-25`): `live-classes:send-reminders` (5 min), `subscriptions:process-lifecycle` (hourly),
`b2b:check-license-expiry` (daily), `verification:expire-and-remind` (06:00), `bookings:release-unpaid` (15 min),
`market:fetch` (5 min). **No job is queued**: nothing implements `ShouldQueue`, so mail and notifications run inside the request.
A queue worker is therefore not needed, but a slow or failing mail server slows or breaks user actions.

If cron does not run: expired verifications stay "verified", unpaid bookings hold seats, unpaid tour balances are never
cancelled, subscriptions never lapse, prices never refresh, live-class reminders never go out.

**GAP-012 · P1 · Server/Mobile · No push for B2B events**
- Evidence: `TradeUpdate`, `VerificationUpdate`, `LotEvidenceUpdate` → `via()` returns `['database']` only; only `GeneralNotification` adds FCM. Push tap routing knows only live classes and courses (`push_notification_service.dart:249-265`).
- Impact: sellers miss sample requests, quotes and deal steps unless they open the app.
- Fix: add FCM (and email for document decisions) respecting notification preferences; route taps to deal room, sample, quote, verification. Effort M.

**GAP-011 · P1 · Server · Upload fails after saving when admin email fails**
- Evidence: `DocumentReviewService.php:183-201` sends `VerificationAdminAlert` synchronously after the transaction, without try/catch.
- Impact: SMTP error → 500 to the app for a document that was stored; the user retries.
- Fix: `afterCommit` + queue + catch and log. Effort S.

**GAP-046 · P3 · Server · FCM logs message text when not configured**
- `FcmService.php:163,241` logs token, title and body. Effort S.

### Area 9 — Localization

`test/localization_test.dart` enforces en + am for keys in `lang_en.dart` / `lang_am.dart` (1,558 lines each); new slice
screens use them.

**GAP-018 · P1 · Mobile/Server · English-only text**
- Evidence: scan of `Text('…')`, `SnackBar`, `tooltip`, `labelText`, `hintText`, `'message':` literals: **334 in 70 files**. Worst: `export_docs_studio_screen.dart` 44, `export_doc_editor_screen.dart` 33, `coffee_listing_detail_screen.dart` 21 (lot detail — a buyer's first screen), `coffee_offer_generator_screen.dart` 19, `auth_screen.dart` 17. The API never applies `Accept-Language`: `SetLocale` is only in the web group (`bootstrap/app.php:28-33`); only `MarketDataController.php:60-63` and `AiChatController.php:169` set the locale. The 422 message is English (`bootstrap/app.php:71`). `resources/lang/am` has 5 of 9 files (missing `auth`, `frontend`, `instructor`, `student`).
- Fix: API locale middleware for `en`/`am`; move literals to keys, starting with lot detail, auth and errors. Effort L (incremental).

**GAP-036 · P2 · Server · Dates**
- Server dates are sent in UTC; app formatting for Africa/Addis_Ababa was not checked screen by screen (prior audit noted UTC server dates). Effort M.

**GAP-047 · P3 · Server · Removed languages still present**
- `resources/lang/{es,fr,hi,tr}` remain and `SetLocale.php:20-32` lists 13 locales, though commit `091f50d` says all but en/am were removed. Effort S.

### Area 10 — Code health and tests

| Check | Result |
|---|---|
| Laravel `php artisan test` (in-memory SQLite) | **286 passed, 2 skipped, 0 failed**, 3,256 assertions, 236 s. Skipped: live Gemini eval and live ICO smoke (opt-in). The 2026-11-16 audit said the suite failed under `:memory:` — that is fixed. |
| Fresh migrate | Passes on SQLite (every `RefreshDatabase` test). MySQL-only statements not verified. |
| Flutter `flutter test` | **164 passed, 0 failed** |
| `flutter analyze` | 28 issues: **0 errors, 2 warnings** (`test/slice7_trust_test.dart:83,101` unused optional parameters), **26 info**: `withOpacity` ×9, `Share/shareXFiles/share` ×6, FCM `onTokenRefresh/getToken/deleteToken` deprecated ×3, `use_null_aware_elements` ×8. |
| PHP static analysis | Not configured (only `pint`). |

Features with **no test**: Search, notifications/push, account deletion, Fira AI chat endpoint, instructor API (55 routes),
community, course checkout gateways, video streaming, coffee readiness. Tests for export docs and toolkit exist but use
`actingAs()`, which hides GAP-004/005.

**GAP-037 · P2 · Server · Tests hide guard bugs** — add bearer-token (`withToken`) tests for every public route that reads a user. Effort S.
**GAP-038 · P2 · Both · Giant files** — `checkout_screen.dart` 2,045 lines, `course_detail_screen.dart` 2,100, `home2_screen.dart` 1,738 (dead), `CourseEnrollmentController.php` > 2,200. Effort L.
**GAP-048 · P3 · Mobile · Deprecated FCM and share APIs** — `push_notification_service.dart:99,199,244`. Effort S.
**GAP-049 · P3 · Repo · Stray files** — `tests/TestCase.php.tmp.18584.ad51e28cde71`; root scripts `audit_repo.php`, `fix_local_urls.php`, `test_booking_payment.php`, `update_*.php` in the Laravel root. Effort S.

### Area 11 — Security and privacy

Good: tokens in secure storage with legacy migration (`base_service.dart:16-37`); Android `allowBackup=false`,
`usesCleartextTraffic=false`, data-extraction rules; no secrets tracked now (`git ls-files` shows no `.env`, keystore,
`key.properties`, `google-services.json`, `scratch/`); production errors hide internals (`bootstrap/app.php:92-96`);
private files for evidence, slips and documents; rate limits on auth (10/min), API (160/min/user), chat, reports, public seller.

**GAP-021 · P0 · Server · Leaked secrets still valid (per CLAUDE.md, not verifiable here)**
- Evidence: `.env` in history (`git log --all -- 'firalink web/firalink/.env'` → commits up to `1fb6925`); 626 `scratch/` file entries in history. CLAUDE.md lists `APP_KEY`, `CHAPA_*`, `GEMINI_API_KEY` and scratch passwords as not yet rotated.
- Impact: owner ID numbers, payout account numbers and encrypted settings are protected by a public key.
- Fix: rotate in the CLAUDE.md order before real verification data or payout accounts are stored. Effort S. Your decision on history rewrite (D-2).

**GAP-019 · P1 · Server · Default admin and demo accounts with `password`**
- Evidence: `AdminSeeder.php:20-56` creates `admin@fira-link.com`, `admin@eduex.com`, `instructor@eduex.com`, `student@eduex.com` with password `password`; called from `DatabaseSeeder.php:14-16` with no environment guard.
- Impact: one `php artisan db:seed` on the server creates a known admin login.
- Fix: refuse outside `local`, or generate a random password and print it once. Effort S.

**GAP-039 · P2 · Server · Tokens never expire** — `config/sanctum.php:50` `'expiration' => null`. Fix: expiry plus refresh on use. Effort M.
**GAP-050 · P2 · Server · Guests can upload documents to AI analysis** — `routes/api.php:41-48` (20 MB, 10/min/IP, no auth). Costs Gemini quota once enabled. Fix: `auth:sanctum`. Effort S.
**GAP-051 · P2 · Server · Token in a query string** — `FileController.php:60-65` accepts `?token=`; it ends up in access logs. Superseded by signed URLs (GAP-008). Effort S.
**GAP-052 · P3 · Server · CORS header on every API error** — `bootstrap/app.php:61-65` adds `Access-Control-Allow-Origin: *` while `config/cors.php` uses an allow-list with credentials. Effort S.
**GAP-053 · P3 · Mobile · iOS camera text** — `Info.plist` camera text mentions receipts and documents, not lot evidence photos; location text is fine. Effort S.

### Area 12 — Production readiness

**GAP-020 · P1 · Mobile · Release builds can be debug-signed; no update gate**
- Evidence: `android/app/build.gradle.kts:56-60` falls back to `signingConfigs.getByName("debug")` when `key.properties` is missing. No minimum-version check anywhere (`AppConfig.appVersion = '1.0.0'`, `pubspec.yaml:5` `1.0.0+1`). CLAUDE.md slice 8 already notes older apps get 401 for tour booking.
- Fix: fail the release build without the keystore; `/api/settings` returns `min_app_version`, app blocks below it. Effort S.

**GAP-023 · P2 · Repo · Tested code is not committed**
- Evidence: `git status`: 104 modified, 1 deleted, 20 untracked; `git diff --shortstat` → 105 files, +1,426 −2,547. Includes the fake Search screen, QA tools and five Flutter test files.
- Impact: a build from `main` is not what was tested; a lost working tree loses QA tooling.
- Fix: review and commit per slice (fake Search must not be committed as is). Effort S.

Environment variables that change behaviour (names only):

| Variable | If missing or wrong |
|---|---|
| `APP_KEY` | App will not boot; rotating signs everyone out on the web and voids signed links (CLAUDE.md) |
| `APP_ENV` / `APP_DEBUG` | Non-production env + debug leaks file paths in API errors (seen in probe under `testing`) |
| `CHAPA_SECRET_KEY` / `CHAPA_PUBLIC_KEY` / `CHAPA_MODE` / `CHAPA_WEBHOOK_SECRET` | Webhook secret falls back to the secret key (`ChapaPaymentAdapter.php:26`); `sandbox` enables simulated checkout (GAP-009) |
| `SMS_ETHIOPIA_API_KEY` | Phone sign-in refused in production (fail closed, tested) |
| `ID_HASH_KEY` | Owner ID uploads refused (fail closed) |
| `VERIFICATION_ADMIN_EMAIL` | No admin alerts (logged warning) |
| `GEMINI_ENABLED` / `MARKET_AI_ENABLED` / `GEMINI_API_KEY` | AI off (fail closed) |
| `API_NINJAS_API_KEY` | ICE price "Unavailable" |
| `FIREBASE_CREDENTIALS` | Push silently "simulated" in logs |
| `SAMPLE_PAYMENTS_MOCK` | Ignored in production |
| `QUEUE_CONNECTION` | `.env.example` says `database`, but nothing is queued |
| `QA_TOOLS_ENABLED`, `AUTH_TEST_OTP_CODE` | Must be unset in production; both refuse production |

## 7. Not verified (what I need from you)

| Item | Why not | What I need |
|---|---|---|
| Paid video playback on a phone (GAP-008) | No device | Android phone + an enrolled account on a course with a self-hosted paid lesson |
| Camera, document scanner, GPS on evidence photos, file picker, offline, slow network, app killed mid-upload | No device or emulator run | Android phone and an iPhone (or emulator) on staging |
| Production `.env` values (`APP_DEBUG`, `CHAPA_MODE`, rotated keys) and whether Chapa is enabled in `payment_gateway_settings` | Not allowed to read `.env`; no server access | Your confirmation, or read-only server access |
| Cron running, mail sending, SMS delivery, FCM credentials | No server | Staging URL and an account to receive mail/SMS |
| MySQL-only migrations and indexes; N+1 and response sizes on real data | Tests use SQLite and tiny data | Staging DB copy or `EXPLAIN` output for listings, RFQs, deal rooms |
| Legacy gateway routes (Stripe, PayPal, Razorpay…) | Out of time; not used in Ethiopia | Decision D-5 |
| Backups and restore | No server | Your hosting panel or backup script |
| Store listing: privacy policy URL, data safety form, icons, splash | Outside the repo | Play Console / App Store Connect access |
| Web Blade pages (seller verify, admin review) in a browser | No browser run | Staging URL |
| Instructor API | Not in launch scope | — |

## 8. Fix plan

Follows the CLAUDE.md slice workflow: one slice at a time, backend first, then Flutter, then the allowed Blade screens, then tests and a slice report. Nothing has been changed or fixed in the app codebase (read-only audit). Full plan also maintained in `docs/audits/fix-plan-2026-11-19.md`.

| Order | Slice | Findings | Risk of the change | Tests needed |
|---|---|---|---|---|
| A | **Payments lock-down** (P0) | GAP-001, 003, 009, 034, 044, 035 | Low. Changes only failure paths; keep bank slips working. Rotating Chapa keys may fail checkouts started just before the switch (CLAUDE.md). | Unsigned / wrong-signature / replayed webhook → 4xx, invoice stays pending; amount and currency mismatch → rejected; unknown provider → 404; `CHAPA_ENABLED=false` → every Chapa start answers "not available" and bank slip still works; simulated checkout refused outside local/testing. |
| B | **Fake data and secrets** (P0) | GAP-002, 021, 019, 042, 041 | Low. Search loses fake content; hide categories without an endpoint. Rotating `APP_KEY` signs out web users and voids signed links (CLAUDE.md step 7). | Widget test: search with no results shows the localized empty state and no hard-coded names; seeder refuses `APP_ENV=production`; localization test for new keys. |
| C | **Auth guard and access** (P1) | GAP-004, 005, 006, 007, 050, 030, 037 | Medium. Moving routes under `auth:sanctum` changes guest behaviour: keep templates, calculate and market data public. Older apps that call these signed out will now get 401 (they already fail). | `withToken()` tests for every moved route; guest and non-member get 401/404 on entitlements; free `lesson_id` + paid path → 403; guest readiness check either scores without saving or 401, never 500. |
| D | **Core flow breaks** (P1) | GAP-008, 013, 016, 011, 051 | Medium. Signed video URLs touch every lesson; deletion touches many tables — needs a dry run on a copy. | Signed stream URL plays, expired URL 403; dispute: participant can raise, others 403, notification sent; deletion removes personal files and anonymises trade rows, request stays pending on failure; upload succeeds when mail throws. |
| E | **App reliability** (P1) | GAP-014, 015, 033, 020 | Medium. One shared HTTP client changes every legacy service; do it service by service. | Unit tests for the client (timeout, 401 → sign-in, 5xx keeps token); widget tests for retry state; release build fails without keystore; min-version gate screen. |
| F | **Notifications** (P1) | GAP-012, 046, 024 | Medium. Push volume and preferences; FCM credentials must be set. | Each B2B event creates a database row and a push (faked FCM in tests); preference off → no push; tap routing per type; roles seeded. |
| G | **Localization and copy** (P1/P2) | GAP-018, 036, 047, 010 | Low per screen, large in total. Start with lot detail, auth, errors, export docs. | `localization_test.dart` for every new key; API returns Amharic messages with `Accept-Language: am`; dates shown in Africa/Addis_Ababa. |
| H | **Hygiene and performance** (P2/P3) | GAP-023, 031, 032, 038, 039, 040, 043, 045, 048, 049, 052, 053 | Low. Commit the working tree first (without the fake Search) so later slices diff cleanly. | Policy tests per new policy; payment verify race test with two concurrent calls; analyzer at 0 warnings. |

### Immediate actions before Slice A
1. **Decide D-1 (switch Chapa off)** and rotate the Chapa keys in the Chapa dashboard — this is the single action that protects money today.
2. **Commit or set aside the working tree (D-10)** so Slice A starts from a clean, reproducible state.


## 9. Decisions you must make

| # | Decision | Recommendation |
|---|---|---|
| D-1 | Switch Chapa off on the server until keys are rotated and GAP-001/009 are fixed? | **Yes.** Rotate today; add `CHAPA_ENABLED=false`; offer bank slip only. |
| D-2 | Rewrite git history to remove the leaked `.env` and `scratch/`? | Rotate first (that is the real fix). Rewrite later, only if the repo is or will be shared; it needs a force push and fresh clones. |
| D-3 | Search: build real search now, or hide it for launch? | Hide the Lots/Exporters/Courses results until the real endpoints are wired (1–2 days); never ship the hard-coded version. |
| D-4 | Export Docs Studio and toolkit readiness: fix for launch or hide? | Fix — it is a small route change (GAP-004/005) and the feature is already built. |
| D-5 | Remove the nine non-Ethiopian payment gateways (server routes and app checkout code)? | **Yes**, remove; keep Chapa and bank slip. |
| D-6 | Academy at launch: self-hosted paid videos need signed URLs (GAP-007/008). Ship academy, or launch with free/YouTube lessons only? | Fix the bypass now (S); ship paid self-hosted video only after signed URLs work on a phone. |
| D-7 | Account deletion: anonymise trade records (keep deal history for the other side) or delete them? | Anonymise trade records, delete personal files and contact data; write it into the privacy policy. |
| D-8 | Token lifetime (GAP-039) | 60-day expiry, extended on use; sign-out everywhere on password change (already done). |
| D-9 | Disputes: who resolves them and in what time? The flows don't design a dispute screen. | Admin resolves within 5 business days; reuse the existing dispute screen with localized copy. |
| D-10 | Commit the working tree? | Commit per slice after review; drop or rewrite `screens/search/search_screen.dart` first. |
