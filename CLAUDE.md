# CLAUDE.md — Fira Link

Laravel 12 backend and Blade web, Flutter mobile app, MySQL 8, Sanctum, Spatie permissions.
Languages: English and Amharic.

## 1. Stack and folder map

| Layer | Path |
|---|---|
| Backend + web | `firalink web/firalink/` |
| Mobile | `firalink flutter/` (Dart package name `eduex_app`) |
| Screen flows (reference) | `docs/design/flows/` — one `.dc.html` per screen, `canvas.json` lists titles, tags and order |
| Product docs | `docs/product/01–06_*.md`, `docs/saas-*.md`, `docs/b2b-audit.md` |

`docs/design/flows/` is the only copy of the flows.

**Launch priority: mobile.** On the web, build only the public seller verify page and the admin review screens for now.
Other flow screens are Flutter-only until the user says otherwise.

### Laravel (`firalink web/firalink/`)
- Routes: `routes/api.php` (the API for web and mobile), `web.php`, `admin.php`, `instructor.php`, `student.php`, `auth.php`
- `api.php`: public `auth` (throttle 10/min), public `marketplace`, `coffee-toolkit`, `verify-seller`;
  `auth:sanctum` + `throttle:api` for signed-in routes (`marketplace`, `deal-rooms`, `messages`, …); `instructor` adds `role:instructor`
- Controllers: `app/Http/Controllers/Api/` (+ `Api/Instructor/`), `Admin/`, `Front/`, `Auth/`
- Services: `app/Services/` (`Billing/`, `EntitlementService`, `MarketplaceService`, `CertificateVerificationService`, `OtpService`, …)
- Policies: `app/Policies/` (Course, Enrollment, Event only — most marketplace and deal-room checks are inline today)
- Middleware aliases (`bootstrap/app.php`): `role`, `approved.instructor`, `setup.complete`, `phone.verified`,
  `entitlement`, `verified.role`
- Blade: layouts `resources/views/layouts/{app,admin,instructor,student}.blade.php`, partials `resources/views/partials/`,
  marketplace views `resources/views/marketplace/`
- Web CSS tokens: `public/assets/front/css/fira-design-system.css` (`--fira-primary`, `--fira-primary-tint`,
  `--fira-neutral-*`, `--fira-radius`, `--fira-space-*`, font Inter) and `partials/dynamic-styles.blade.php` (`--theme`, `--text-*`)
- Tests: `tests/Feature/`, `tests/Unit/` — run `php artisan test`

### Flutter (`firalink flutter/lib/`)
- Entry `main.dart`, `app.dart`; routes in `router/app_router.dart` (`AppRouter`); API base URL in `config/config.dart`
- `services/` (HTTP to the API), `providers/`, `models/`, `screens/<feature>/`, `widgets/`
- Theme: `theme/app_theme.dart` (`AppTheme`), `theme/fira_colors.dart` (`FiraColors`, read with `context.fira`)
- Localization: `l10n/`
- Tests: `test/` — run `flutter test`

## 2. Design system and branding

`docs/design/flows/` gives **content and flow only**: screen purpose and order, which button goes where, titles, labels,
helper text, warnings, empty and error messages, form fields (order, required, hints, validation), screen states
(pending, approved, needs changes, locked, expired, empty, error), business rules written on the screens, and the data
each screen needs.

Never take from the flows: colors, gradients, shadows, fonts, font sizes, radii, spacing, icon drawings, layout details,
card shapes, pixel sizes, the drawn bottom dock, or `[bracket]` placeholder text (replace with real data or localization keys).

If a drawn screen and an existing app screen cover the same thing, **the existing app screen wins**. Only add the
missing fields, states and rules.

**Never add new colors, fonts or radii.** Pending status is **purple on lavender** (`fira.pending` on `fira.brandTint`),
never amber or green. **Verification badges never depend on a billing plan.**

### Flutter tokens (`context.fira`)
`brandPrimary` #6D1A95 · `brandDeep` #3B0764 · `brandTint` #F3E8FF · `surface` / `card` · `onSurface` #1E1B4B ·
`mutedText` · `success` · `pending` · `danger` · `chipSelectedBg` / `chipUnselectedBg` · dock, glass and shadow tokens.
Use these, not hex literals or `Colors.*`. Legacy `AppTheme.mint*` / `softOrange*` / `softBlue*` are purples — do not add more.

Fonts: Montserrat, fallback NotoSansEthiopic (set in `AppTheme`).
Radii from the theme: input and button 16, chip 12, card 22, dialog and bottom sheet 28.
Home: `screens/home/widgets/home_design_constants.dart` (`HomeDesignConstants`: radii, horizontal padding 20,
dock clearance 140). There is no global spacing scale; reuse existing values.

### Flutter shared widgets
`FiraBottomDock` (`widgets/fira_bottom_dock.dart`), `CourseCard`, `CourseThumbnailImage`, `FiraGlass`, `LiquidGlass`,
`FiraAiFab`, `HomeSkeletons` / `BaseShimmer`, `ImageModal`, `LiveClassBanner`, `VoteWidget`,
`QuotaLimitSheet` and `VerificationPromptDialog` (`screens/marketplace/widgets/`),
`ReportModal` and `ScamWarningSheet` (`screens/trust_safety/`), `WorkspaceSwitcherSheet` (`screens/profile/`).
`MarketplaceLotCard` does not exist yet: lot cards are a private `_buildLotCard()` in `marketplace_home_screen.dart`.
If a needed widget does not exist, build it from existing tokens and say so in the slice report.

### Web
Use the existing Blade layouts, partials and the CSS variables above. Web tokens differ from mobile
(Inter, `--fira-radius: 6px`, tint `#f7f0fa`); keep each platform on its own existing tokens.

## 3. Rules for every feature

- **One Laravel API is the source of truth.** Web and mobile both call it (or the same service class). No business logic in the UI.
- **Verification, payment and tier rules are enforced on the server with policies.** Tier and evidence-level changes
  happen only through an admin review action, never from a client field.
- **Uploaded documents and photos must be real files.** Check the actual file content, not the extension. Reject empty,
  tiny or placeholder files. Store private documents on the private `verification` disk, never on `public`.
- **Every user-facing string goes through localization in English and Amharic.**
  - Flutter: add the key to `l10n/lang_en.dart` (`kLangEn`) and `l10n/lang_am.dart` (`kLangAm`), add a getter in
    `l10n/app_localizations.dart`, keep `app_en.arb` / `app_am.arb` in sync. `test/localization_test.dart` enforces en + am.
  - Laravel: `resources/lang/<locale>/*.php` via `__()`. There is no `am` folder yet, and `SetLocale` forces the public
    site to English — fix this when a slice adds web strings.
- **Every endpoint gets a policy and a test** (`app/Policies/`, `tests/Feature/`).
- **Where the backend is not ready, use a mock repository behind a flag** and list it as mocked. Never fall back to a
  fake success response when a real call fails (`_mockResponse` in `services/marketplace_service.dart` does this today).
- Never commit secrets: `scratch/` scripts, `android/key.properties`, `*.jks`, `google-services.json`.

## Secrets and rotation

Never commit `.env`, keystores, `key.properties`, `google-services.json` or `scratch/`. Never print secret values in
logs, reports or chat — refer to them by name only.

The Laravel `.env` was committed to GitHub from 2026-10-10 (commit `1ee9a7c`) until `1fb6925`. It is still in the
history. These had real values and must be treated as leaked and rotated:

| Service | Name | What rotating it affects |
|---|---|---|
| Laravel | `APP_KEY` | Logs out every web session (session and remember-me cookies become invalid). Invalidates every signed URL still in use: email verification links, signed certificate downloads, admin private-file links. Platform settings saved with `SettingsRepository::set(..., encrypted: true)` can no longer be decrypted — check `platform_settings` rows with `is_encrypted = 1` and re-save them after rotating, or set the old key in `APP_PREVIOUS_KEYS` so Laravel can still decrypt them during the switch. Does **not** affect password hashes or Sanctum API tokens (mobile users stay signed in). |
| Chapa | `CHAPA_SECRET_KEY` | Server-side payment initialize and verify calls. Rotate in the Chapa dashboard, update the server `.env`, then `php artisan config:clear`. Checkouts started just before the switch may fail verification and need a manual check. |
| Chapa | `CHAPA_PUBLIC_KEY` | Client checkout. Rotate together with the secret key. |
| Google Gemini | `GEMINI_API_KEY` | Fira AI chat, the export documents AI assistant and coffee market synthesis. Revoke the old key in Google AI Studio, since a leaked key can run up quota and billing. |

`REDIS_PASSWORD` was the `null` placeholder and `SMS_ETHIOPIA_API_KEY` was empty. The `scratch/` scripts
(credentials for admin accounts and the server) are also in the history and those passwords should be changed too.

Removing these from git history needs a history rewrite and a force push. That is the user's decision; rotation
is the fix that matters.

**Rotate `APP_KEY` before real verification data is stored in production, because the old key was committed to GitHub.**
Document numbers on verification documents are encrypted with it.

Other auth secrets and settings:

- `ID_HASH_KEY`: dedicated secret for the keyed hash of owner ID numbers (finds one owner behind several
  businesses, blocks self-review). Never derived from `APP_KEY`, never committed. Generate a long random value
  once and keep it: changing it makes old hashes unmatchable. Without it, owner ID uploads are refused (fail closed).
- `VERIFICATION_ADMIN_EMAIL`: receives an email for each new verification submission and the daily pending summary.

- `AUTH_TEST_OTP_CODE` (fixed sign-in code) must **never** be set in production. It is honoured only when
  `APP_ENV=local`, but keep it out of every non-local `.env` anyway.
- `SMS_ETHIOPIA_API_KEY`: without it, production refuses phone sign-in and phone verification ("SMS not
  available") instead of skipping them. Rotating it affects only SMS delivery; codes already sent stay valid.

## One-time production tasks (not yet run)

Run on the production server **in this order**. Always run the dry run first and read the counts before using
`--force`. Take a database backup before step 1.

1. `php artisan migrate`
   - slice 0: milestone proof and bank-confirmation columns
   - slice 1: `users.must_reset_password`, nullable `users.email` (phone-only and email-only accounts)
   - slice 2: `organizations.business_role`, `entity_type`, `tin_number`, `needs_manual_review`, `created_by`;
     `users.onboarding_intents`. After this deploy, lots and supplier pages are hidden from buyers unless the
     seller's business has an approved, unexpired seller verification.
2. `php artisan files:move-evidence-private --dry-run`, then without `--dry-run` — moves bank slips, contracts,
   dispute and report evidence, lot evidence, seller licenses and lab reports off the public disk.
3. `php artisan marketplace:reset-evidence-levels`, then with `--force` — sets every lot back to `claimed`
   (sellers could set the level themselves before slice 0), one audit-log entry per lot. Admins re-review afterwards.
4. `php artisan auth:report-email-aliases` — report only, nothing changes. Decide each pair by hand; never merge
   automatically.
5. `php artisan auth:flag-weak-passwords` (**dry run first**, read the count), then with `--force`.

   **Warning before `--force`:** it flags *every* account whose password is `12345678` or `password`,
   **admins included**, and signs them out. A flagged account cannot sign in with a password until it sets a new one:
   - Web login sends it to Forgot password, which emails a reset link; completing the reset clears the flag.
   - Mobile phone sign-in sends it straight to "choose a new password" (only if the phone is verified).

   So before `--force`, check that **each admin account has a working reset path**: a real email address it can
   receive mail at (and mail sending works on the server — send yourself a test reset first), or a verified phone
   with SMS configured. An admin with a placeholder email and no verified phone would be locked out. Fix those
   accounts first (change their password while still signed in, or correct their email), then run `--force`.
6. `php artisan businesses:report-auto-created` — report only, nothing changes. Lists empty businesses that the
   old code created automatically (named after the person or "… Trade Entity"). Decide by hand; never delete
   automatically.
7. Rotate the leaked secrets listed above. Rotate `APP_KEY` last, after confirming everything else works: it signs
   every web user out and voids signed links.
8. Slice 3 (verification tiers). `php artisan migrate` adds document review columns, document pages, Fira IDs and
   Tier 3 grants; existing verification documents become `legacy` and no longer count.
   - Set `ID_HASH_KEY` and `VERIFICATION_ADMIN_EMAIL` in the server `.env`, then `php artisan config:clear`.
   - `php artisan verification:drop-legacy-approvals` (dry run, read the counts), then with `--force`: every
     business approved before slice 3 drops to Tier 0 and its lots are hidden until its documents are reviewed.
   - Every admin and reviewer must set up an authenticator app at next sign-in (`/staff/two-factor/setup`) and save
     the recovery codes. If one loses both: `php artisan admin:reset-2fa <email>`.
   - The scheduler must run (`* * * * * php artisan schedule:run`): `verification:expire-and-remind` runs daily at 06:00.
9. Slice 4 (lots and evidence). `php artisan migrate` adds lot codes (back-filled for existing lots), lot evidence
   and evidence pages. Check that the PHP `gd` and `exif` extensions are installed on the server: without `exif`,
   evidence photos are refused; without `gd`, approving a photo fails (both fail closed).
   - `php artisan marketplace:report-lots-without-business` — report only. Lots without a business are hidden and
     nobody can edit them; decide each by hand.
   - `php artisan marketplace:move-lot-reports-private` (dry run, read the count), then with `--force`: lab and
     cupping reports leave the public disk and wait in the review queue as unconfirmed lab reports.
   - Lot levels are now computed from reviewed evidence only; the old admin "set level" form is gone.
10. Slice 5 (requests and quotes). `php artisan migrate` adds the business (`organization_id`) to requests and
    quotes, the request volume unit and "sample first". Requests are no longer public, and the web request pages
    are removed.
    - `php artisan marketplace:report-trade-without-business` — report only. Requests made before slice 5 have no
      business and are hidden from sellers; quotes without a lot cannot be accepted. Decide each by hand.
    - The ECTA minimum price check uses only price lists with status `published`. Keep the current list published
      in admin; with none published, quotes show "No reference price" and are not blocked.
11. Slice 6 (samples). `php artisan migrate` adds the buyer business, encrypted delivery contact and courier account,
    the unique seal ID with seal photos, and `deal_rooms.sample_seal_id`.
    - Leave `SAMPLE_PAYMENTS_MOCK` unset (false) in production. Paid samples then show "Payments are not available
      yet" until the real Chapa payment is built after the Chapa keys are rotated; free samples work. The mock is
      refused in production even if the flag is set.
    - Samples requested before slice 6 have no buyer business; only the person who requested them can act on them.
12. Slice 7 (report, block, chat warning, deal room). `php artisan migrate` adds business chat (`trade_conversations`,
    `trade_messages`), `business_blocks`, report reason codes and targets, `payout_accounts` (number encrypted with
    `APP_KEY`), and `deal_rooms.buyer_organization_id` / `deposit_percent` / `paused_at`.
    - Rotate `APP_KEY` before sellers add real payout accounts: account numbers are encrypted with it, and after a
      rotation without `APP_PREVIOUS_KEYS` they can no longer be read.
    - Deal rooms opened before slice 7 have no buyer business (the buyer person stands in) and keep their five steps;
      new deal rooms have six. Steps are now confirmed in order, so check open deal rooms before finance confirms
      a later step.
    - Sellers must add a payout account and a reviewer must confirm it (review queue, Payout accounts tab) before any
      buyer can upload a payment slip. A change needs a call to the owner's phone on file and waits 48 hours.
    - Optional `DEAL_DEPOSIT_PERCENT` (default 30) sets the deposit share of new deal rooms.
13. Slice 8 (events, tours, academy). `php artisan migrate` adds event category, online, agenda, member ticket,
    service fee, refund policy and organizer business; tour host business, deposit percent, balance due days and
    safety notes; tour booking deposit and balance; and `payments.purpose` / `confirmed_by`. Booking emails become
    optional (phone-only accounts).
    - Run `php artisan files:move-evidence-private --dry-run`, then without `--dry-run`: bank receipts
      (`payments.receipt_file`, courses and plans) now count as private files and leave the public disk. Until
      moved, old receipt links in admin show "not found".
    - The scheduler runs `bookings:release-unpaid` every 15 minutes: unpaid Chapa checkouts free their seats after
      an hour, and tours whose balance is unpaid after the due date are cancelled. Bank slips with finance are kept.
    - Bank slips for events and tours are confirmed or rejected on the existing admin page Payments → Offline
      pending (admin role). Chapa is still the live gateway with the leaked keys: rotate them (see above).
    - Link each event organizer and tour host by Fira ID on the admin event and tour pages, and set tour dates;
      a tour without future dates cannot be booked. "Verified" shows only while that business has an active Tier 1.
    - Tour booking and the event/tour Chapa verify routes now need sign-in; older app versions that book tours
      without signing in will get 401.

## 4. Workflow per slice

The user names one slice at a time. Do not start the next slice without their OK.

1. Name the design files for the slice and list the fields, states and rules to implement.
2. Backend first: migrations, models, policy, API, tests.
3. Flutter screens using existing widgets and tokens.
4. Blade screens using the existing layout and tokens — only the public seller verify page and admin review screens for now.
5. Run the tests. Report what is done, mocked and missing, and any place where the flow conflicts with the current app design.

Do not touch unrelated files.

## 5. Slice order

0. Real document upload fix
1. Auth completion: OTP, email login, forgot and reset password
2. Workspaces, intent, business setup
3. Verification tiers, document review, public seller page
4. Seller: create lot, evidence photos, lot levels
5. Buyer: marketplace, filters, RFQ, quote, compare
6. Samples: request, seal ID, tracking
7. Report, block, chat warning, deal room
8. Events, tours, academy
