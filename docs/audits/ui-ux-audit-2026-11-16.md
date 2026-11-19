# Fira Link UI/UX and professionalism audit — 2026-11-16

Read-only audit of the Flutter app (`firalink flutter/lib`) and the Laravel Blade web (`firalink web/firalink`).
No app code, config, data or git history was changed by this audit. Evidence is a file path and line, a command
result, or a metric from a static scan (scan scripts kept outside the repo).

> **Important — concurrent changes.** While this audit ran (2026-11-15 to 2026-11-16), another session changed
> many app files (see `git status`: AI chat, auth, bundles, categories, chat, coffee toolkit, `config.dart`,
> router …; `data/marketplace_demo_data.dart` deleted) and wrote its own report into
> `ui-ux-audit-2026-11-15.md`. Metrics below were taken during that period; the "Re-check" table shows the
> state of the key findings on the code as it is now. This report is separate from that one.
## Executive summary

1. The **trust core is genuinely strong**: tiers from reviewed documents, bank-confirmed deal steps, masked
   payout accounts, off-platform chat warnings, business-only identities, private files. Few trade apps do this.
2. The **slice 2–8 screens** (verification, trade, samples, deal room, chat, bookings) are calm, honest about
   states ("In review") and use the brand tokens.
3. The rest of the app is a **different, older visual language**: 400 hex colours, 1,326 `Colors.*`, 38 font
   sizes, radii from 2 to 999. Token use is about one in five colour references.
4. The **first screens a buyer sees** — market home, lot detail, seller portal — are among the most off-token
   and most English-only.
5. The **bilingual promise breaks**: English dock labels, English email sign-in errors, English profile menu,
   538 English text literals.
6. **Two P0s**: no way to raise a dispute in a deal room (regression), and the seller portal can show
   "VERIFIED" from a legacy flag, with amber "Compliance" styling.
7. **Overclaims** in copy: "cryptographic seal", "escrow", "immutable ledger", "certified guides".
8. **Accessibility**: green "Verified" text (2.54:1) and red error text (3.76:1) fail contrast; text size is
   capped at 1.2×; few semantics labels.
9. **Robustness gaps** on older screens: raw exception text in 35 places, no offline state, no image caching,
   about 128 HTTP calls without timeouts, no unsaved-input guard.
10. Code health is good (analyzer clean, 147 Flutter tests pass), but the Laravel suite needs a pre-migrated
    database and fails under `:memory:`.

## Verdict: **Not yet professional**

The product underneath is close; what a user sees first is not. The three deciding reasons:
1. **Two visual languages**: careful new screens next to loud, off-brand older ones, including the
   marketplace entry screens.
2. **Amharic users meet English** in navigation, sign-in errors, profile and market.
3. **Trust-UI slips on a trust product**: a missing dispute path, a "VERIFIED" badge from a legacy flag,
   overclaimed copy, and failing contrast on the "Verified" label itself.

## Scores (0–5)

| # | Dimension | Score | Why (evidence count) |
|---|---|---|---|
| 1 | Visual consistency (brand tokens) | **2** | 400 hex, 1,326 `Colors.*`, 1,816 legacy helpers vs 594 token uses (B.1) |
| 2 | Component quality and reuse | **2** | 5 status-badge implementations, no LotCard / EmptyState / OfflineBanner (B.4) |
| 3 | Typography and spacing | **2** | 38 font sizes, ~300 uses under 12 pt, radii 2–999, card radius 22 used 11 times (B.2–B.3) |
| 4 | Navigation and information architecture | **3** | 6-item dock, English labels, no deep links, 8 orphan screens, dispute dead end (A.1, B.6) |
| 5 | Core flows (usability) | **3** | 10 flows average 3.2; strong slice flows, weak market/home/profile (C) |
| 6 | States and feedback | **2** | 30 screens without error handling, 5 with offline, 35 raw errors, 0 unsaved-input guards (A.1, C) |
| 7 | Accessibility | **1** | Contrast fails on Verified, errors and dark pending; text cap 1.2×; 40 semantics for 280 tap targets; 106 web images without alt (E) |
| 8 | Responsiveness | **2** | One phone size tested; no tablet or landscape layout; 133 fixed sizes (D) |
| 9 | Localization and content quality | **2** | 538 literals, 99 literal snackbars, English dock, 4 overclaims, UTC server dates (G) |
| 10 | Trust and safety UI | **4** | Real mechanics and good warnings; minus legacy verified flag, amber, dead scam sheet (H) |
| 11 | Performance | **2** | No image caching, ~128 calls without timeout, page-1-only lists (I) |
| 12 | Web frontend polish | **2** | 898 `!important` override layer, 2,046 inline styles, second font, English error pages; static audit only (F) |
| 13 | Overall perceived professionalism | **2** | Strong core, inconsistent surface (all of the above) |

## Top 10 fixes (priority order)

1. **UI-001** Put "Raise a dispute" back in the deal room (P0, S).
2. **UI-002** Seller portal: show the tier from the server, no legacy flag, no amber (P0, S).
3. **UI-007** Remove overclaims: cryptographic seal, escrow, immutable ledger, certified guides (P1, S).
4. **UI-003 / UI-004** Localize the dock, orb actions and email sign-in errors (P1, S).
5. **UI-006** Replace raw exception text with the app's error states (P1, M).
6. **UI-005** Rebuild market home and lot detail on tokens and l10n with a shared LotCard (P1, M).
7. **UI-008** Token request for readable success and danger text; fix the dark-mode pending pill (P1, S).
8. **UI-012** One StatusChip for every status (P1, M).
9. **UI-015 / UI-014 / UI-013** Offline banner, timeouts on old services, image caching (P1, M).
10. **UI-010** Server time zone to Africa/Addis_Ababa (P1, S).


## Re-check of key findings on the current code (2026-11-16)

| ID | Finding | Now |
|---|---|---|
| UI-001 | No dispute entry in the deal room | **Still open** (0 references to `DisputeResolutionScreen`) |
| UI-002 | Seller portal legacy verified flag and amber | **Still open** (13 matches for `profile['is_verified']` / `Colors.amber`) |
| UI-003 | English dock labels, orb matched by label | **Still open** (3 literal labels, label match present) |
| UI-004 | English email sign-in errors | **Still open** |
| UI-005 | Market title literal, green verified on lot detail | **Still open** |
| UI-006 | Raw exception text | **Partly changed**: 35 → 31 places |
| UI-007 | Overclaims | **Partly changed**: "escrow" removed from the contract text; "cryptographic" still in 2 places |
| UI-009 | Text scale capped at 1.2× | **Still open** |
| UI-010 | Server time zone UTC | **Still open** |
| UI-013 | No image caching | **Still open** |
| UI-016 | No unsaved-input guard | **Partly changed**: `PopScope` 0 → 3 uses |
| UI-018 | Dead demo data | **Changed**: `marketplace_demo_data.dart` deleted |

## A. Inventory and coverage

### A.1 Flutter

- 273 Dart files outside `l10n/`; **117 `*_screen.dart` files, 119 screen classes**; 49 named routes in
  `router/app_router.dart`. Many screens open with `MaterialPageRoute`, so the router is not the full map.
- **8 screen classes are not opened from anywhere** (no reference outside their own file):

| Screen | File | Note |
|---|---|---|
| `DisputeResolutionScreen` | `screens/deal_room/dispute_resolution_screen.dart` | Lost its entry when the deal room hub was rebuilt in slice 7 (the old AppBar "Raise dispute" icon). The server endpoint `POST /deal-rooms/{id}/dispute` still works. **Regression.** |
| `LotPhotoCaptureScreen` | `screens/marketplace/lot_photo_capture_screen.dart` | Slice 4 evidence capture; confirm evidence photos still go through another path. |
| `LotLifecycleScreen` | `screens/marketplace/lot_lifecycle_screen.dart` | Orphan. |
| `ResetPasswordScreen` | `screens/auth/reset_password_screen.dart` | Older reset UI; the code-based reset lives elsewhere. |
| `BundleCheckoutScreen` | `screens/bundles/bundle_checkout_screen.dart` | Orphan. |
| `AllInstructorsScreen` | `screens/instructor/all_instructors_screen.dart` | Orphan. |
| `PaymentDetailScreen` | `screens/profile/payment_detail_screen.dart` | Orphan. |
| `SuspendedLicenseScreen` | `screens/trust_safety/suspended_license_screen.dart` | Orphan: suspended businesses never see it. |

- **States** (keyword scan per screen, a heuristic): 30 of 117 screens had **no error handling at all**
  (no `catch`, no error state), including `profile_screen.dart`, `dashboard_screen.dart`,
  `event_detail_screen.dart`, `ai_chat_screen.dart`, `onboarding_screen.dart`, the five `coffee_toolkit/`
  screens, `payments_screen.dart`, `wishlist_screen.dart`. **Only 5 of 117 screens mention offline or
  connectivity**; there is no connectivity package in `pubspec.yaml` and no app-wide offline banner.

### A.2 Flows vs built screens

`docs/design/flows/canvas.json` lists 54 screens. All 26 "NEW" flow screens of slices 0–8 have an app screen.
Built but not in the flows: AI learning path, AI suggestions, habit tracker, study timer, community Q&A,
wishlist, bundles, export docs studio and editor, coffee market chart, instructor area — older LMS features
that carry most of the visual inconsistency below.

Weak or partial coverage: **Message host** (TourConfirmed) not built; **Lesson "Files" tab** has no data model;
**Home, Search, Notifications, Market, Toolkit, FOB, Fira AI, Settings, Splash, Onboarding** ("match to your
app") were never reconciled with the flows.

## B. Mobile UI consistency (Flutter)

### B.1 Token fidelity — the biggest visual problem

| Pattern (whole `lib/`, minus `l10n/`) | Count |
|---|---|
| Hard-coded `Color(0x…)` | **400** |
| `Colors.*` (not transparent) | **1,326** |
| Legacy `AppTheme.*` colour helpers (`getPrimaryColor`, `mint*`, `getTextColor` …) | **1,816** |
| `context.fira` / `fira.*` token uses | 594 |
| Literal `fontSize:` | **1,433** |
| Literal radii `circular(n)` | 884 |
| `BoxShadow(` | 66 |

Roughly **one token use for every five hard-coded colour references**. Slice 2–8 screens use tokens; most
older screens do not.

Worst 15 files (hex + `Colors.*` + font sizes + radii + shadows; token uses in brackets):
`marketplace/export_docs_studio_screen.dart` 256 (0), `marketplace/export_doc_editor_screen.dart` 189 (0),
`coffee_toolkit/coffee_market_screen.dart` 136 (0), `course_detail/course_detail_screen.dart` 108 (0),
`marketplace/marketplace_home_screen.dart` 107 (4), `home/home2_screen.dart` 94 (0),
`coffee_toolkit/widgets/coffee_candlestick_chart.dart` 88 (0), `marketplace/coffee_listing_detail_screen.dart`
87 (2), `marketplace/seller_portal_screen.dart` 86 (0), `auth/auth_screen.dart` 72 (0),
`coffee_toolkit/coffee_profit_calculator_screen.dart` 71 (0), `marketplace/marketplace_pricing_screen.dart`
69 (0), `events/event_detail_screen.dart` 63 (0), `tours/tour_detail_screen.dart` 63 (0),
`coffee_toolkit/coffee_toolkit_hub_screen.dart` 61 (0). The market home and lot detail — the two screens a
buyer sees first — are both in the top 8.

Off-brand colour in trust-relevant places:
- `marketplace/coffee_listing_detail_screen.dart:646` — verified seller as `Icons.verified, color: Colors.green`.
- `dashboard/ticket_view_screen.dart:500` — confirmed in `Colors.green.shade100`; completed (line 522) in
  `Colors.blue` (not a brand colour); cancelled in `Colors.red`.
- `deal_room/export_contract_esign_screen.dart:81` — `const primaryColor = Color(0xFF6D1A95)`;
  `dispute_resolution_screen.dart:70,110,176` — `Colors.redAccent`.

### B.2 Typography

- **38 distinct font sizes**, including half sizes (8, 9, 9.5, 10, 10.5, 11, 11.5, 12, 12.5, 13, 13.5, 14,
  14.5, 15 … 32). No scale: 11 / 11.5 / 12 / 12.5 / 13 are used side by side.
- Weights: `w700` 396 and `bold` 372 (the same weight written two ways), `w600` 279, `w500` 198, `w800` 43,
  `w900` 5.
- `AppTheme` defines a `TextTheme`, but most older screens set `TextStyle` by hand.
- About 300 uses below 12 pt (8–11.5); Ethiopic script at 9–10 pt is hard to read.
- Amharic: the fallback font is set app-wide (right), but fixed-height containers can clip Ethiopic glyphs
  (see D).

### B.3 Radii and spacing

- Radii: 12 (220), 16 (185), **20 (112)**, **8 (67)**, **10 (54)**, **14 (45)**, **6 (42)**, 24 (40), 4 (31),
  18, **22 (only 11)**, 32, 28 (6), 25, 30, 100, 999. The brand card radius 22 is almost never used.
- Side padding: 16 (174) and 20 (165) used about equally; the brand rule is 20. Off-rhythm values 10, 14, 6, 7,
  18 appear about 130 times.

### B.4 Component reuse

- 130 `IconButton`s and 150 `GestureDetector`/`InkWell` taps; many hand-built pill buttons
  (`_buildActionPill` in `tour_booking_confirmation_screen.dart`, `_FilterChipButton` in `events_screen.dart`,
  `_SearchBar` per screen).
- Near-duplicates that should be one widget:
  - **Status badge**: `WorkspaceStatusPill`, `InReviewPill`, `_buildStatusBadge` in `ticket_view_screen.dart`,
    booking status pills in `my_ticket_bookings_screen.dart` and `my_tour_bookings_screen.dart`, the deal room
    status — five implementations.
  - **Verified mark**: `BusinessTierLabel`, `VerifiedBusinessLine`, `LotLevelChip`, plus hand-made
    `Icons.verified` + `Colors.green` rows.
  - **Empty state**: every list builds its own.
  - **Lot card**: still a private `_buildLotCard()` in `marketplace_home_screen.dart`.
  - **Price row**: `PriceLine`, `_row` in `request_sample_screen.dart`, `_amountRow` in the payment step.
- Missing shared widgets: **empty state**, **offline banner**, one **status chip**, **lot card**, **section
  header**. Good existing ones: `FiraErrorState`, `HomeSkeletons` / `BaseShimmer`, `ReportModal`.

### B.5 Icons and emoji

- Three icon families: Material `Icons.*` 599, `HugeIcons` 251, `CupertinoIcons` 6.
- Emoji as UI in 4 files: `quiz_attempt_screen.dart` (6), `coffee_candlestick_chart.dart` (2),
  `coffee_listing_detail_screen.dart` (1), `marketplace_home_screen.dart` (1).

### B.6 Dock and navigation

- `widgets/fira_bottom_dock.dart`: **6 dock items** plus the Fira AI orb — dense for 390 pt.
- **Hard-coded English dock labels**: `'B2B'` (line 322), `'Tours'` (332), `'Profile'` (337).
- **Orb actions are English and matched by label text**: `_OrbActionData(label: 'Ask Fira AI' …)` (175–178);
  `_handleActionTap` compares `label == 'Ask Fira AI'` (270–280), so localizing would break the actions.
- `extendBody: true` (`shell/main_shell_screen.dart:72`) relies on each screen adding bottom padding.
- **No deep links**: no `app_links` / `uni_links` in `pubspec.yaml`; only `onGenerateRoute`.
- `PopScope` was used **0 times** in `screens/` at scan time (3 after the concurrent changes).

## C. Mobile UX, flow by flow (code walk; not run on a device)

Ratings are 0–5. "Steps" counts screens from entry to done for the main path.

| # | Flow | Steps | Rating | Decisive evidence |
|---|---|---|---|---|
| 1 | First launch: language, onboarding, guest | 3–4 | **3** | Good: splash → language → onboarding with Skip and "Explore as guest" (`onboarding/onboarding_screen.dart:74,275`). Weak: the post-setup welcome is English-only and hex-coloured (`onboarding/intent_welcome_screen.dart:14,18,117`: `const primaryColor = Color(0xFF6D1A95)`, `'Welcome to Fira Trade'`, `'RECOMMENDED NEXT STEP'`). |
| 2 | Phone OTP, email sign-in, forgot/reset | 3–5 | **3** | Phone sign-in, code screen and reset are localized and handle lockout and cooldown. The **email sign-in screen is English-only**: `auth/auth_screen.dart:249–650` (`'Invalid Input'`, `'Login Failed'`, `'Login Successful'`, `'Password Mismatch'`, `'Please enter a valid Ethiopian mobile number …'`), and it is live (`router/app_router.dart:156`, linked from `phone_sign_in_screen.dart:152`). |
| 3 | Intent, business setup, workspace switch | 3 | **4** | Localized, TIN validation, limits; verification opens after creation. Minor: the switcher only switches; "Team and roles" is a separate screen. |
| 4 | Verification dossier, scan, Fira ID, public check | 3–4 | **4** | Server states per document, reasons, camera capture, public page. Minor: staff are not told why they cannot upload (the button is just absent). |
| 5 | Seller: lot, evidence, levels | 4–5 | **3** | Create lot and evidence are localized. **Seller Portal is English and hex**: `marketplace/seller_portal_screen.dart:211,222` (`'Exporter Portal'`, `'Plans & Quotas'`), score 86 in B.1. `LotPhotoCaptureScreen` is orphaned (A.1). |
| 6 | Buyer: market, filters, RFQ, quotes, compare | 4–6 | **3** | RFQ, compare and exporters are good. **The market home itself is English and off-token**: `marketplace/marketplace_home_screen.dart:146` (`'Coffee Marketplace'`), `:176,358` (`'Seller Portal'`). Lot detail shows "verified" with `Colors.green` (`coffee_listing_detail_screen.dart:646`) and sign-in prompts in English (`:82,129,258`, `actionDescription: 'bookmark lots …'`). |
| 7 | Samples: request, seal, tracking | 3 | **4** | Clear steps, seal ID, photos, delivery-contact rule, report. |
| 8 | Report, block, chat warning, deal room | 3–6 | **3** | Report sheet, chat warning and steps are strong. **Dead end: a party cannot raise a dispute** (`DisputeResolutionScreen` unreachable, A.1). The contract e-sign screen is English and hex (`deal_room/export_contract_esign_screen.dart:81`). |
| 9 | Events, tours, academy, bookings, payments | 3–5 | **3** | New register, booking and payment panels are localized and honest about "In review". The old screens around them are not: event detail is token-free (63 hard-coded, B.1); course checkout shows **27 English snackbars** (`course_detail/checkout_screen.dart`); course discussion shows raw errors (`course_access/course_discussion_detail_screen.dart:70`: `'Failed to submit reply: $e'`). |
| 10 | Home, search, notifications, Fira AI, profile, settings | — | **2** | `home/home2_screen.dart` has 18 English strings; **Profile menu subtitles are English** (`profile/profile_screen.dart:547,567,577,597`); `ai_chat/ai_chat_screen.dart` has no error state; dock labels and orb actions are English (B.6). |

Cross-flow problems:
- **Raw errors shown to users: 35 places** use `e.toString()` or `'Error: $e'` in screens, e.g.
  `bundles/bundle_checkout_screen.dart:117`, `community/qa_room_screen.dart:73`,
  `community/question_detail_screen.dart:50,70,88`, `course_access/assignment_submit_screen.dart:166`,
  `categories/categories_screen.dart:107`, `course_access/course_access_screen.dart:136`. Users can see
  `SocketException`, `FormatException` or the HTML of a 500. The slice screens use `showFiraActionError` /
  `FiraErrorState`, which is the right pattern.
- **Lost input**: `PopScope` is used 0 times. Long forms (create lot, RFQ, register for event, tour booking,
  business setup, document capture) discard everything on back or swipe without asking.
- **Literal SnackBars**: 99 `SnackBar(content: Text('English…'))` across screens.
- **Guest walls**: `VerificationPromptDialog.showAuthRequired` is good, but its `actionDescription` strings are
  English sentences passed by callers (flow 6), so the explanation stays English in Amharic.

## D. Responsiveness and device safety

- **Text size is capped at 1.2×** for the whole app: `lib/app.dart:68–70`
  (`textScaler.clamp(minScaleFactor: 0.8, maxScaleFactor: 1.2)`). A user who sets 200% text gets 120%. This
  hides overflow bugs instead of fixing them, and fails users with low vision (see E).
- Fixed sizes: 48 widths ≥ 100 and 85 heights ≥ 100 are hard-coded (scan), e.g. image boxes and cards with a
  fixed height that will clip Amharic or large text.
- **Widget tests cover one size only**: 11 test files set a 390 pt-wide tall view (`Size(1170, 7000)` at 3×).
  No test runs at 320 pt, on a tablet, in landscape, or at text scale 1.3 or 2.0. Amharic is rendered only in
  the slice 2–8 tests.
- No tablet or landscape layout anywhere (no breakpoints in screens); on a tablet the phone layout stretches.
- Keyboard: `resizeToAvoidBottomInset: false` is never used (good). Bottom sheets with forms add
  `viewInsets.bottom` in the slice screens; older sheets (e.g. `trust_safety/scam_warning_sheet.dart`) do not.
- Safe areas: new screens wrap in `SafeArea`; the shell uses `extendBody: true` and relies on bottom padding
  per screen (B.6).

Overflow tests to add (small, high value):
1. Market home, lot detail, deal room hub, payment step, register for event, tour booking, profile — each at
   320×640, 390×844 and 768×1024, text scale 1.0 and 2.0, English and Amharic, asserting no exception.
2. The dock at 320 pt in Amharic.
3. Seller home with three long Amharic to-do items.

## E. Accessibility

### E.1 Contrast (WCAG 2.1, computed from the tokens in `theme/fira_colors.dart` and `theme/app_theme.dart`)

| Pair | Ratio | Result |
|---|---|---|
| onSurface #1E1B4B on surface #FFFFFF | 15.99 | Pass |
| mutedText #6B7280 on white | 4.83 | Pass |
| mutedText on brandTint #F3E8FF | 4.10 | **Fail** for body text (large text only) |
| pending #6D1A95 on brandTint | 7.97 | Pass |
| white on brandPrimary (buttons, selected chips) | 9.41 | Pass |
| **success #10B981 on white** | **2.54** | **Fail** — used for every "Verified" / "Tier n" label (`BusinessTierLabel`, `VerifiedBusinessLine`, "Matches the seller's verified bank account") |
| success on brandTint | 2.15 | **Fail** |
| **danger #EF4444 on white** | **3.76** | **Fail** for small text (errors, "below minimum", warnings) |
| Dark: pending #8B2BB8 on brandTint #2E1065 | 2.28 | **Fail** — the "In review" pill in dark mode |
| Dark: brandPrimary #8B2BB8 on surface #19132B | 2.68 | **Fail** — purple text and links in dark mode |
| Dark: onSurface, mutedText, success, danger on surface | 4.77–17.96 | Pass |
| Web `--fira-primary` on tint #f7f0fa | 8.42 | Pass |

Success and danger are frozen brand tokens, so this is a **token request**: a darker success/danger *text*
token, or a rule that status text uses `onSurface` with a success/danger *icon*. Nothing was changed.

### E.2 Semantics and tap targets (Flutter)

- 40 `Semantics` / `semanticLabel` / `tooltip` uses for 130 `IconButton`s and 150 `GestureDetector`/`InkWell`
  taps. Hand-built tap areas (pills, dock items, chips built on `GestureDetector`) have no semantics label and
  are often under 44×44 (18–20 px icons with small padding).
- Images: 47 network images; product photos have no `semanticLabel`.
- Colour-only status: mostly avoided in the slice screens (icon + text); older booking and ticket badges rely
  on colour plus an English word.
- Reduce motion: shimmer and the animated dock and orb do not check `MediaQuery.disableAnimations`.
- Screen reader order on the top screens: **not run** (needs a device with TalkBack / VoiceOver).

### E.3 Web

- **106 of 230 `<img>` have no `alt`** (6 more have `alt=""`).
- 638 text inputs and 840 `<label>` tags (labels exist; field-by-field pairing not verified).
- 94 inline `onclick=` handlers; 252 `aria-*` attributes.
- `<html lang="{{ app()->getLocale() }}">` is set (`layouts/app.blade.php:2`) — good.

## F. Web frontend (Blade) — static audit

Not run in a headless browser (no Playwright here), so there are no screenshots and layout at 390 / 768 /
1440 px is **not verified**. Earlier live HTTP runs confirmed `/`, `/login`, `/forgot-password`, `/events`,
`/tours` and `/verify/{firaId}` return 200.

- **341 Blade views**: admin 151, instructor 42, student 40, partials 19, emails 11, export-docs 10, events 7,
  marketplace 7, setup 7, auth 7, tours 4, courses 4, other public pages about 25.
- **Hard-coded styling**: about 2,037 hex colours and 2,046 inline `style="…"` attributes in views.
  `public/assets/front/css/fira_pixel_perfect.css` (88 KB) has **898 `!important`** and 306 hex colours — an
  override layer fighting `main.css` (208 KB, 81 `!important`). `fira-design-system.css` tokens exist, but the
  override layer wins.
- **Fonts**: Inter is the web font (`fira-design-system.css:13`), but **Plus Jakarta Sans** is loaded by
  `resources/views/export-docs/layout.blade.php` — a second display font on the web (drift).
- **82 `<script src>` without `defer` / `async`**, 5 from CDNs — render-blocking on public pages.
- **Error pages are English-only**: `errors/404.blade.php` and `errors/500.blade.php` have no `__()`.
- **The public verify page is right**: noindex (`partials/seo.blade.php:7,35–36`), share description, OG title
  and description (`seo.blade.php:58–59`).
- Locale: `SetLocale` supports `en` and `am` for public pages (`app/Http/Middleware/SetLocale.php:85,101`), but
  `resources/lang/am/` only has `bookings`, `lots`, `trade` and `verification`; the rest of the web falls back
  to English.
- English-heavy public views (text outside `__()`): `free-tools.blade.php`, `home.blade.php`,
  `checkout.blade.php`, `programs/index.blade.php`, `pricing/index.blade.php`, `partials/floating-ai.blade.php`,
  `market-prices/*`, `export-docs/studio.blade.php`.
- Performance: page weight and caching headers **not measured** (needs a browser run).

## G. Content, localization and tone

- **English-only strings in Flutter**: 538 `Text('…')` literals and 99 literal SnackBars (scan). Worst:
  `marketplace/export_docs_studio_screen.dart` (45 + 5), `export_doc_editor_screen.dart` (40), course
  `checkout_screen.dart` (27 SnackBars), `coffee_toolkit/coffee_offer_generator_screen.dart` (21),
  `home/home2_screen.dart` (18), `marketplace/coffee_listing_detail_screen.dart` (17 + 2), `auth/auth_screen.dart`
  (15), `marketplace/marketplace_home_screen.dart` (14), `document_scanner/fira_document_scanner_screen.dart`
  (13). An Amharic user meets English on the market home, lot detail, seller portal, email sign-in, profile
  menu and dock.
- Keys: `test/localization_test.dart` enforces en and am for every key, so keys are complete; the gap is text
  that never became a key.
- Mixed language on one screen: profile (Amharic headings from l10n, English subtitles —
  `profile/profile_screen.dart:547–597`), dock (`fira_bottom_dock.dart:322,332,337`), market home.
- Amharic quality: **not judged** — needs a native speaker review (see "Not verified").
- **Overpromising or inaccurate claims**:
  - `onboarding/intent_welcome_screen.dart:33`: "Request 250g type samples with **cryptographic seal
    verification**" — the seal is a physical tamper seal with a typed ID, nothing cryptographic.
  - `trust_safety/scam_warning_sheet.dart:67`: "Every verified sample includes a **cryptographic seal number**"
    (the sheet is never opened anywhere, but the claim is in the code).
  - `deal_room/export_contract_esign_screen.dart:150`: contract text offers "milestone **escrow** deposit". Fira
    Link does not hold money; it records bank-confirmed steps. The server's legacy deal-room messages also say
    "Trade records archived into immutable ledger" (`DealRoomController::completeDeal`) — there is no ledger.
  - `onboarding/intent_welcome_screen.dart:42`: "certified guides" — hosts are business-verified, not certified.
- Terms: "Lot" is consistent in new screens; old screens use "listing", "product" and "lot". "Business" vs
  "Workspace" vs "Organization" vs "Exporter Portal" vary (`seller_portal_screen.dart:211`). "Tier" and "Level"
  are used consistently in slice screens.
- **Time zone**: `config/app.php:68` is `'timezone' => 'UTC'`, not `Africa/Addis_Ababa`. Server-side "today"
  (balance due dates, `ship_by`, document expiry, the 06:00 daily job, the 15-minute release job) is computed in
  UTC, three hours behind Addis Ababa. Flutter converts ISO dates to device local time.
- Money formats differ: `TradeLabels.money` groups thousands (`$100,000`), `bookingMoney` does not
  (`ETB 6000.00`, `widgets/booking_flow_widgets.dart`), older screens use `'\$${x.toStringAsFixed(2)}'`.

## H. Trust and safety UI

- **Verified look is not one thing.** Slice screens show tier with `BusinessTierLabel` / `VerifiedBusinessLine`
  (success colour text); lot detail shows `Icons.verified` in `Colors.green` (`coffee_listing_detail_screen.dart:646`);
  the seller portal shows a green "VERIFIED" or **amber "GET VERIFIED"** badge and an **amber "Compliance
  Verification Required"** banner (`seller_portal_screen.dart:320–330, 384–401`). The amber banner breaks the
  "pending is purple on lavender, never amber" rule, and "Compliance" overstates what Tier 1 means.
- **Two sources of truth for "verified"** on the seller portal: `isVerified = org['is_verified'] ||
  profile['is_verified']` (`seller_portal_screen.dart:194`). The organization flag is kept in sync with the tier
  (`app/Services/Verification/TierService.php:111`), but the **seller profile flag is legacy** and is not tied
  to tiers, so the portal can show VERIFIED for a business at Tier 0. (Risk; not reproduced with data.)
- Verification is not tied to a plan in any screen found (good). `QuotaLimitSheet` and the pricing screen only
  talk about quotas.
- Warnings: the off-platform warning in chat (slice 7) appears on the message, with "Keep chatting in Fira Link"
  and "Report this message" — right moment, easy to act on. **`ScamWarningSheet` is never opened** (dead
  widget). Pay-only-inside-Fira-Link notes appear on tour detail, booking and confirmation. The payment step
  shows the "different account" warning before the upload button.
- Things the UI shows that the server does not back: `data/marketplace_demo_data.dart` (fake lots,
  "Escrow milestone payment protection", "Certified Organic") is **not referenced** anywhere (dead code, safe
  but should be deleted). No `[bracket]` placeholders found in Dart. No lorem ipsum. The fake-success
  `_mockResponse` from CLAUDE.md is gone.
- Leaks: slice 5–8 responses show businesses only (covered by tests that assert no email/phone/name). Lot detail
  prints `'ECTA License: $license'` to buyers (`coffee_listing_detail_screen.dart` near line 641) — check that a
  licence number is meant to be public.
- Error text leaks internals in 35 places (C).

## I. Performance and robustness (mobile)

- **No image caching**: 47 `Image.network`/`NetworkImage`, 0 `CachedNetworkImage` (package not in
  `pubspec.yaml`). Lot photos, course covers and event images re-download on every scroll.
- 38 `ListView(children: …)` (builds all items) vs 55 `ListView.builder/separated`. Most lists load page 1 only
  (no "load more"); e.g. deals list, saved lots, chats, samples take only what the server returns
  (server pages are 15–100).
- **HTTP without timeout**: about 128 `http.get/post` calls without `.timeout` (scan heuristic), worst
  `services/export_docs_service.dart` (13), `auth_service.dart` (13), `student_course_service.dart` (10),
  `notification_service.dart` (9). The slice services (trade, samples, deal room, chat, bookings) use
  `guardedRequest` with timeouts.
- No offline handling (A.1): a dropped connection shows the raw error or a spinner forever on older screens.
- `setState` 654 uses; no state management for shared data (each screen refetches).
- `flutter analyze lib`: **0 errors, 0 warnings, 25 info** — 17 `deprecated_member_use` (Share API, push
  notification token API), 8 `use_null_aware_elements`. Clean.
- **Tests**:
  - Flutter: **147 passed, 0 failed, 0 skipped** (`flutter test`).
  - Laravel with `DB_CONNECTION=sqlite DB_DATABASE=:memory:` as required: **232 failed, 10 passed**. Every
    failure is `no such table` (`users`, `platform_settings`): 23 test files use `DatabaseTransactions` and
    none use `RefreshDatabase`, so the suite depends on a pre-migrated database file and **cannot run in a clean
    CI database**. (On the migrated local test database the same suite passed 242/242 on 2026-11-15.)

## J. Competitive bar (general premium B2B / fintech principles)

Meets the bar:
- **Trust mechanics are real**, not decoration: tiers from reviewed documents, sequential bank-confirmed
  steps, masked payout accounts with a 48-hour change pause, off-platform warnings, business-only identities,
  private files with signed links. This is stronger than many trade apps.
- New screens (slices 2–8) are calm: one primary action, honest "In review" states, clear rules text.

Falls short:
- **Two visual languages in one app.** Slice screens look like a careful product; the old LMS, toolkit, market
  home, seller portal and export studio look like a different, louder template (hex colours, 38 font sizes,
  many radii, amber and blue status). Users notice this as "unfinished".
- **First impressions are the weakest screens**: market home, lot detail and seller portal are among the most
  off-token and most English-only screens.
- **Bilingual promise not kept**: an Amharic user sees English on the dock, sign-in errors, profile menu and
  market.
- Feedback and safety nets: raw error strings, no offline state, no unsaved-input guard, no image caching.
- Accessibility: green "Verified" text and red error text fail contrast; text scale capped at 1.2×.

## Findings (all)

| ID | Sev | Area | Platform | Where | What is wrong | Suggested fix | Effort |
|---|---|---|---|---|---|---|---|
| UI-001 | P0 | C/H | Mobile | screens/deal_room/deal_room_hub_screen.dart; dispute_resolution_screen.dart | No way to raise a dispute: DisputeResolutionScreen is not opened anywhere since the slice 7 hub rebuild. | Add "Raise a dispute" to the deal room hub (next to Report) and rebuild the dispute screen on tokens and l10n. | S |
| UI-002 | P0 | H | Mobile | screens/marketplace/seller_portal_screen.dart:194,320-330,384-401 | Seller portal decides VERIFIED from legacy is_verified flags (org OR seller profile) and shows unverified as amber "GET VERIFIED" / "Compliance Verification Required". | Read the tier and workspace status from the server (same as Profile verification row) and use WorkspaceStatusPill; drop the profile flag. | S |
| UI-003 | P1 | G | Mobile | widgets/fira_bottom_dock.dart:175-178,270-280,322,332,337 | Dock labels B2B/Tours/Profile and the Fira AI orb actions are hard-coded English; orb actions are matched by their English label. | Use l10n keys and dispatch orb actions by an enum, not by label text. | S |
| UI-004 | P1 | G | Mobile | screens/auth/auth_screen.dart:249-650 | Email sign-in and sign-up dialogs are English-only (Login Failed, Invalid Input, Password Mismatch …). | Move every string to l10n en/am; reuse FiraErrorText for API errors. | S |
| UI-005 | P1 | B/G | Mobile | screens/marketplace/marketplace_home_screen.dart:146,176,358; coffee_listing_detail_screen.dart:82,129,258,646 | Market home and lot detail (first screens for buyers) are English-only in places and off-token (107 and 87 hard-coded style values); verified shown with Colors.green; emoji in UI. | Rebuild header and cards on context.fira, Theme text styles and l10n; use a shared LotCard and VerifiedBadge. | M |
| UI-006 | P1 | C | Mobile | 35 places, e.g. bundles/bundle_checkout_screen.dart:117, community/question_detail_screen.dart:50,70,88, course_access/course_access_screen.dart:136 | Raw exception text shown to users ('Error: $e', e.toString()). | Use showFiraActionError / FiraErrorState everywhere; log the exception. | M |
| UI-007 | P1 | G/H | Both | onboarding/intent_welcome_screen.dart:33,42; trust_safety/scam_warning_sheet.dart:67; deal_room/export_contract_esign_screen.dart:150; DealRoomController.php:600 | Claims the product does not back: cryptographic seal verification, escrow deposit, immutable ledger, certified guides. | Rewrite to what is true: tamper seal with a seal ID; payments confirmed with the bank step by step; deal records kept; hosts are verified businesses. | S |
| UI-008 | P1 | E | Mobile | theme/fira_colors.dart (success #10B981, danger #EF4444; dark pending/brandPrimary) | Contrast fails: success text on white 2.54:1, danger 3.76:1, dark pending on tint 2.28:1, dark brand text 2.68:1, muted on tint 4.10:1. | Token request: a darker success and danger text token (or status text in onSurface with a coloured icon); for dark mode a lighter pending text token. | S |
| UI-009 | P1 | D/E | Mobile | lib/app.dart:68-70 | Text scale clamped to 0.8–1.2 for the whole app. | Add overflow tests at 1.3 and 2.0, fix the overflows, then raise or remove the cap. | M |
| UI-010 | P1 | G | Web | config/app.php:68 | Server time zone is UTC, not Africa/Addis_Ababa. | Set Africa/Addis_Ababa (or compute business dates in that zone) and re-check scheduled jobs. | S |
| UI-011 | P1 | B | Mobile | whole app; worst files in B.1 | Token fidelity: 400 hex colours, 1,326 Colors.*, 1,816 legacy AppTheme helpers vs 594 token uses; 38 font sizes; radii 2–999 (card 22 used 11 times). | Migrate screen by screen starting with first-impression screens; add a lint list of banned patterns for new code. | L |
| UI-012 | P1 | B | Mobile | status pills in WorkspaceStatusPill, InReviewPill, ticket_view _buildStatusBadge, booking lists, deal room | Five implementations of a status badge; ticket statuses use green/red/blue (ticket_view_screen.dart:500,522). | One StatusChip widget (pending purple on lavender, success, danger, neutral) used everywhere. | M |
| UI-013 | P1 | I | Mobile | pubspec.yaml; 47 Image.network | No image caching (0 CachedNetworkImage); images re-download on scroll. | Use a cached image widget with placeholder and error builder in CourseThumbnailImage and lot photos. | S |
| UI-014 | P1 | I | Mobile | services/export_docs_service.dart, auth_service.dart, student_course_service.dart, notification_service.dart … | About 128 HTTP calls without timeout in older services. | Route old services through guardedRequest with timeouts, like the slice services. | M |
| UI-015 | P1 | A/C | Mobile | whole app; 5 of 117 screens | No offline state or banner; 30 screens have no error handling at all. | App-wide connectivity banner and FiraErrorState on every network screen. | M |
| UI-016 | P2 | C | Mobile | create lot, RFQ, register for event, tour booking, business setup, document capture | No unsaved-input guard (PopScope used 0 times). | Confirm on leave when the form is dirty. | S |
| UI-017 | P2 | B | Mobile | screens/marketplace/marketplace_home_screen.dart _buildLotCard; empty states per list | Missing shared widgets: LotCard, EmptyState, SectionHeader, OfflineBanner; duplicated price rows and pills. | Build the shared widgets from existing tokens and replace duplicates. | M |
| UI-018 | P2 | A | Mobile | LotPhotoCaptureScreen, LotLifecycleScreen, ResetPasswordScreen, BundleCheckoutScreen, AllInstructorsScreen, PaymentDetailScreen, SuspendedLicenseScreen; data/marketplace_demo_data.dart; ScamWarningSheet | Orphaned screens and dead demo data. | Wire the ones that matter (suspended state, lot photo capture if still the path), delete the rest. | S |
| UI-019 | P2 | B | Mobile | Navigation | No deep links (no app_links/uni_links); a Fira ID link or a notification cannot open the right screen. | Add app links for /verify/{firaId}, deal rooms, samples and chats. | M |
| UI-020 | P2 | D | Mobile | test/* | Widget tests only at 390 pt and normal text; no 320 pt, tablet, landscape, or 2.0 text tests. | Add the overflow matrix in section D. | S |
| UI-021 | P2 | E | Mobile | 130 IconButtons, 150 GestureDetector/InkWell | Only 40 semantics labels/tooltips; hand-built tap areas below 44x44 and unlabelled. | Tooltips on every icon button, Semantics on custom taps, minimum 44x44 hit area. | M |
| UI-022 | P2 | B | Mobile | whole app | Three icon families (Material 599, HugeIcons 251, Cupertino 6) and emoji in 4 files. | Pick one family per surface (HugeIcons stroke for navigation, Material outlined inside screens) and remove emoji. | M |
| UI-023 | P2 | G | Mobile | profile/profile_screen.dart:547-597; home/home2_screen.dart; course_detail/checkout_screen.dart | 538 Text literals and 99 literal SnackBars remain English. | Move to l10n en/am, worst files first. | L |
| UI-024 | P2 | G | Mobile | widgets/booking_flow_widgets.dart bookingMoney; TradeLabels.money; older screens | Three money formats (with and without thousands separators, $ vs ETB position). | One money formatter per currency, used everywhere. | S |
| UI-025 | P2 | F | Web | resources/views (2,037 hex, 2,046 style=); public/assets/front/css/fira_pixel_perfect.css (898 !important) | Web styling bypasses fira-design-system.css through an !important override layer and inline styles. | Move public page styles to the design system variables; shrink the override file. | L |
| UI-026 | P2 | E | Web | resources/views (106 of 230 img without alt) | Images without alt text. | Alt text on every content image; alt="" for decoration. | S |
| UI-027 | P2 | F/G | Web | resources/views/errors/404.blade.php, 500.blade.php | 404 and 500 pages are English-only. | Localize both pages in en and am. | S |
| UI-028 | P2 | F | Web | resources/views/export-docs/layout.blade.php | Loads Plus Jakarta Sans in addition to Inter. | Use Inter (web token) only. | S |
| UI-029 | P2 | F | Web | resources/views (82 script src without defer/async, 5 CDN) | Render-blocking scripts. | Add defer to non-critical scripts; self-host CDN scripts. | S |
| UI-030 | P2 | I | Both | tests/ (23 files DatabaseTransactions, 0 RefreshDatabase) | Laravel suite only runs on a pre-migrated database; with :memory: 232 of 242 fail (no such table). | Use RefreshDatabase (or migrate once per run) in the base TestCase. | M |
| UI-031 | P3 | B | Mobile | whole app | Font weight w700 written two ways (w700 396, bold 372) and w800/w900 used for emphasis. | Use the TextTheme weights only. | S |
| UI-032 | P3 | H | Mobile | coffee_listing_detail_screen.dart:655 | Shows 'ECTA License: <number>' to buyers. | Show "ECTA licence checked" from the tier instead of the number, or confirm the policy. | S |

Full rows (why it matters, evidence, re-check status) are in `ui-ux-issues-2026-11-16.csv`.


## Fix plan (four slices, in this order)

Each slice follows the CLAUDE.md workflow (name the files, backend if any, Flutter, Blade, tests, report) and
uses only existing tokens. Anything needing a new token is raised as a **token request** first.

**Slice UI-1 — Trust and dead ends (do first; small, high value).** UI-001 dispute entry, UI-002 seller
portal verified source and amber, UI-007 overclaims (app copy and server messages), UI-018 wire the suspended
screen and delete dead screens and demo data, UI-032 licence number. Risk: **low**; mostly copy and wiring.
Tests: deal room hub shows dispute; seller portal shows the tier from the server.

**Slice UI-2 — States, feedback and robustness.** UI-006 raw errors, UI-015 offline banner and error states,
UI-014 timeouts, UI-013 image caching, UI-016 unsaved-input guard, UI-010 time zone (server), UI-030 test
database setup. Risk: **medium** — touching many old screens; do it screen family by screen family
(courses, community, toolkit, export docs). Tests: error state per screen family; time-zone test for due dates.

**Slice UI-3 — Tokens, shared widgets, typography (first-impression screens).** UI-012 StatusChip, UI-017
LotCard / EmptyState / SectionHeader, UI-005 market home and lot detail, then seller portal, event detail, tour
detail, profile; UI-011 and UI-031 on those files; UI-022 icon family; UI-024 money format. Risk: **medium to
high** — visual regressions; needs screenshots before and after (see "Screens I need from you").

**Slice UI-4 — Accessibility, localization and web polish.** UI-008 contrast (after the token decision),
UI-009 text scale with UI-020 overflow tests, UI-021 semantics and tap targets, UI-003, UI-004 and UI-023
strings to l10n with a native Amharic review, UI-019 deep links; web UI-025 to UI-029 (alt text, localized
error pages, one font, deferred scripts, shrink the override CSS). Risk: **medium**; copy changes need review.

## Not verified (what I need from you)

- **Real devices**: no emulator or phone was run. Visual polish, animation, scrolling performance, keyboard
  behaviour, notch and Dynamic Island, and TalkBack / VoiceOver order are not verified.
- **Web in a browser**: no headless browser here, so no screenshots at 390 / 768 / 1440 px, no console errors,
  no page weight or caching headers.
- **Amharic quality**: needs a native speaker to judge tone and machine-sounding phrases.
- **Production**: no production URL, analytics or crash reports were available.
- **Data-driven states**: the seller-profile `is_verified` drift (UI-002) is inferred from code, not reproduced
  with real data.
- **Laravel tests under `:memory:`** cannot pass without migrations (UI-030); the pass count on a migrated test
  database comes from the 2026-11-15 run, not this audit.

## Screens I need from you (one page)

Screenshot on **one small Android (≈360×740 dp)**, **one large iPhone (≈430×932 pt)** and **one tablet**, in
**English and Amharic**, light mode, at **normal text and the largest system text**. Add dark mode for the
starred (★) ones. Name files `<screen>-<state>-<device>-<lang>.png`.

1. ★ Splash, language picker, the three onboarding pages, "Explore as guest" landing.
2. Phone sign-in, code screen (normal, wrong code, locked), email sign-in with an error, forgot password,
   "password updated".
3. ★ Home (signed in, guest), Fira AI orb open, notifications (empty and with items), search.
4. ★ Market home (lots loaded, empty filter result, offline), lot detail (verified seller, hidden-lot owner
   view), filters sheet.
5. Seller home (unverified banner, with to-dos), Seller Portal, create lot (empty, validation errors), lot
   evidence.
6. ★ Profile (personal and business workspace), workspace switcher, verification dossier (in review, needs
   changes, verified), document capture with a rejected file.
7. RFQ form, compare quotes (with a below-minimum quote), exporter profile, public seller page in the browser.
8. Sample request, seal and ship, sample tracking (each status).
9. ★ Deal room hub (step 2 of 6, paused), payment step (ready, paused after a bank change), chat with the
   warning box, report sheet, deals list.
10. Events list (each tab), event detail, register (General and Member disabled), ticket in review and
    confirmed.
11. Tours list, tour detail, tour booking, booking confirmed with "Pay the balance", my tour bookings.
12. Academy list, course detail, lesson player with progress, quiz, certificates.
13. Any screen that shows a raw error or a spinner that never ends — with the steps that led there.
14. Web: `/`, `/events`, `/tours`, `/verify/<Fira ID>`, `/login`, 404, and the admin review queue tabs, at 390,
    768 and 1440 px.
