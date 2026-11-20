# Auth gating, guest protection and verification badges — Phase 0 audit (2026-11-20)

Read-only analysis. No code was changed. Nothing here prints a secret value.

Appendices (generated from the live code, one row each):

- [auth-gating-routes-2026-11-20.csv](auth-gating-routes-2026-11-20.csv) — all **899 routes** (`php artisan route:list -v`):
  method, URI, controller method with `file:line`, `auth` yes/no, mutating, route middleware (role, verified.role,
  fresh.auth, staff.2fa, can:, signed, throttle), and controller signals found in the method body (policy/Gate call,
  explicit 403/404 check, ownership scope, reads the user, optional `auth('sanctum')`), plus a **proposed class**
  (A/B/C/D) and **flags**.
- [auth-gating-screens-2026-11-20.csv](auth-gating-screens-2026-11-20.csv) — all **146 Flutter screen/sheet files**:
  named route, router guard yes/no, in-screen sign-in check, mutates yes/no, endpoints reached through its services.

The controller signals and endpoint mapping are found by pattern-matching, not by running the code. Every finding in
this document was checked by hand. Rows in the CSVs that I did not open are marked by their flags only.

---

## 0. Summary

**The reported bug is real. The cause is in the app, not the server.** The server already rejects every profile and
password call without a valid token (401). The app breaks in four ways:

1. **"Guest" in the app is not "no token".** `isAuthenticated` means "a user object loaded"
   ([auth_provider.dart:18](../../firalink%20flutter/lib/providers/auth_provider.dart#L18)). On a slow or failed
   `/api/user` call, or a 5xx or 429, the token **stays in storage** and the user stays `null`
   ([auth_provider.dart:35-59](../../firalink%20flutter/lib/providers/auth_provider.dart#L35-L59)). Every service
   still sends that token ([base_service.dart:59-69](../../firalink%20flutter/lib/services/base_service.dart#L59-L69)).
   So someone the UI shows as a guest can really change the profile and the password on the server. The splash
   screen makes this likely: it decides after a fixed 2.6 s, whether or not `loadUser` has finished
   ([splash_wrapper.dart:21-38](../../firalink%20flutter/lib/screens/splash/splash_wrapper.dart#L21-L38)).
2. **Settings and the Profile tab have no guard.** `/settings` is not in `_protectedRoutes`
   ([app_router.dart:108-128](../../firalink%20flutter/lib/router/app_router.dart#L108-L128)). Settings opens Edit
   profile and Change password with a direct `MaterialPageRoute`, which skips the router guard entirely
   ([settings_screen.dart:352](../../firalink%20flutter/lib/screens/profile/settings_screen.dart#L352),
   [:364](../../firalink%20flutter/lib/screens/profile/settings_screen.dart#L364)). The Profile tab sits in the
   shell's `IndexedStack` with no guard
   ([main_shell_screen.dart:39-46](../../firalink%20flutter/lib/screens/shell/main_shell_screen.dart#L39-L46)).
3. **Logout clears the token and the user, nothing else.**
   ([auth_provider.dart:337-349](../../firalink%20flutter/lib/providers/auth_provider.dart#L337-L349)). The previous
   user's business workspace stays in `WorkspaceProvider` (loaded once at app start,
   [app_providers.dart:19](../../firalink%20flutter/lib/config/app_providers.dart#L19)). After logout, the guest
   Profile tab still shows that user's "My business lots", "Workspaces and team" and "Messages" rows.
4. **There is no global 401 handling.** Only `loadUser` reacts to 401, and it also signs out on **403**
   ([auth_provider.dart:50-54](../../firalink%20flutter/lib/providers/auth_provider.dart#L50-L54)). That can sign out
   a user who is only missing phone verification.

**The server is in better shape than the app.** Earlier passes already added a lot of protection: `fresh.auth`
step-up (10 min, per token), email change by code to the new address, phone change by code to the new number,
password reset revokes every session, staff 2FA, the Tier service as the only tier writer, fillable lists that
exclude every verification field, and policies for deal rooms, RFQs, samples, payouts, chats and reports. The server
gaps that remain are narrower (section 5.1). The worst are: a public payment-verify endpoint that returns
another user's payment row from a guessable reference (P1); admin money and settings actions without re-auth (P1);
impersonation without an audit entry, which inherits the admin's fresh-auth flag (P1); and no route-inventory test,
so a future route can ship unprotected.

**Badges:** the server computes tiers correctly. The app shows a **green verified tick on every lot's seller card,
whatever the tier** ([coffee_listing_detail_screen.dart:659](../../firalink%20flutter/lib/screens/marketplace/coffee_listing_detail_screen.dart#L659)),
next to a self-declared "ECTA License" line. Legacy `is_verified` / `verified_roles` are still sent raw by three
endpoints and by every embedded organization object. The web supplier pages show a badge from the old seller-profile
flag, not from the tier. `MarketplaceSeeder` writes that flag with no environment guard.

Counts: **P0 2 · P1 13 · P2 21 · P3 8**: 21 server, 14 mobile, 9 badge (section 5).

---

## 1. How guest mode works today (mobile)

| Question | Answer, with evidence |
|---|---|
| Does a guest hold a token? | **Often, yes.** "Explore as guest" only sets `has_seen_onboarding` and goes Home. It does not clear any token ([auth_navigation.dart:9-14](../../firalink%20flutter/lib/widgets/auth/auth_navigation.dart#L9-L14)). A user whose `/api/user` call failed for a non-401/403 reason, or threw (offline, timeout, DNS), keeps the token with `_user == null` ([auth_provider.dart:39-58](../../firalink%20flutter/lib/providers/auth_provider.dart#L39-L58)). No anonymous accounts and no shared token exist; the server has no guest account concept. |
| Old SharedPreferences copy? | `getToken` migrates a legacy plaintext `auth_token` from SharedPreferences into secure storage and deletes it ([base_service.dart:24-31](../../firalink%20flutter/lib/services/base_service.dart#L24-L31)). **But** if secure storage throws, both `getToken` ([:32-36](../../firalink%20flutter/lib/services/base_service.dart#L32-L36)) and `saveToken` ([:45-48](../../firalink%20flutter/lib/services/base_service.dart#L45-L48)) fall back to plaintext SharedPreferences. That writes a new plaintext copy. |
| Where is "isGuest" decided? | Nowhere by that name. The app uses `AuthProvider.isAuthenticated => _user != null` ([auth_provider.dart:18](../../firalink%20flutter/lib/providers/auth_provider.dart#L18)). Screens read it on their own: Home, Profile, Marketplace home, lot detail, checkout, Export docs studio, instructor profile and the workspace sheet (see the screens CSV, column `in_screen_auth_check`). |
| Which routes check it? | 19 named routes in `_protectedRoutes` ([app_router.dart:108-128](../../firalink%20flutter/lib/router/app_router.dart#L108-L128)) through `AuthGuard` ([:264-282](../../firalink%20flutter/lib/router/app_router.dart#L264-L282)). **Not protected:** `/settings`, `/notification-settings`, `/marketplace/rfqs`, `/marketplace/rfqs/create`, `/marketplace/seller`, `/marketplace/seller/add-lot`, `/marketplace/verification`, `/export-docs`, `/coffee-toolkit/calculator` and `/offer-generator` (they save), `/study-timer`, `/focus-progress`, `/weekly-review`, `/ai-suggestions`, `/onboarding/intent`, `/search`. About 40 screens are opened with a direct `MaterialPageRoute` and skip the router completely: deal rooms, samples, saved lots, trade chats, seller home, payout account, team seats, request sample, quote request, ship sample, lot evidence, tour bookings, ticket booking and more. |
| What does `AuthGuard` do? | It renders `PhoneSignInScreen` in place of the screen. After sign-in, `goAfterSignIn` resets the stack to Home or intent selection ([auth_navigation.dart:17-22](../../firalink%20flutter/lib/widgets/auth/auth_navigation.dart#L17-L22)), so the interrupted action is lost. |
| What happens on logout? | Delete the FCM token on the server, call `POST /api/logout`, clear the token, set user to null ([auth_provider.dart:337-349](../../firalink%20flutter/lib/providers/auth_provider.dart#L337-L349)). **Not cleared:** `WorkspaceProvider` (current business, list, intents), `ChatProvider`, `DashboardProvider`, `CoffeeToolkitProvider`, `AiChatProvider`, `CourseDiscussionProvider`, the local course wishlist (`wishlist_items` in SharedPreferences, [wishlist_service.dart:6-27](../../firalink%20flutter/lib/services/wishlist_service.dart#L6-L27)), recent searches ([search_screen.dart:146](../../firalink%20flutter/lib/screens/search/search_screen.dart#L146)), downloaded receipts in the app documents folder ([payment_service.dart:38-44](../../firalink%20flutter/lib/services/payment_service.dart#L38-L44)) and the image cache. The navigation stack is not reset either. |
| On a 401? | Only `loadUser` reacts, and it treats 403 like 401. Elsewhere: 12 services convert transport errors with `guardedRequest` ([api_exception.dart:76-84](../../firalink%20flutter/lib/services/api_exception.dart#L76-L84)), and screens show their own error. Three screens check `statusCode == 401` by hand (lot detail, export doc editor, pricing). Nothing signs the user out or clears state. The other 26 services don't use `guardedRequest` at all. |
| Fake success? | `_mockResponse` is gone ([marketplace_service.dart:7-12](../../firalink%20flutter/lib/services/marketplace_service.dart#L7-L12)). No service returns `success: true` without a server answer. **Silent failures remain:** `PaymentService.getMyPayments` returns `[]` with no token and on any error ([payment_service.dart:12](../../firalink%20flutter/lib/services/payment_service.dart#L12), [:26](../../firalink%20flutter/lib/services/payment_service.dart#L26)), so a failure looks like "no payments". The course wishlist is local only, so "saved" never reaches the server. |

**Reproduction of the reported bug:** a user with a token opens the app with poor connectivity, or `/api/user`
answers 5xx or 429. The splash screen routes to Home as a guest after 2.6 s. Profile shows "Guest User" and offers
Sign in. They tap Settings, then Edit profile and Save: the request goes out **with the old token** and the server
updates the account. Change password works the same way, because the form sends `current_password` and that
satisfies `fresh.auth` ([FreshAuth.php:110-115](../../firalink%20web/firalink/app/Services/Auth/FreshAuth.php#L110-L115)).
For a true guest with no token the server answers 401. The screen then shows a red "Error: Exception:
Unauthenticated." snack, which is not fake success but is still a broken experience.

---

## 2. Server inventory

### 2.1 Totals (899 routes)

| Group | Public read | Public write | Signed-in read | Signed-in write |
|---|---:|---:|---:|---:|
| `api` (`routes/api.php`) | 43 | 35 | 107 | 134 |
| `web` (`web`, `admin`, `auth`, `instructor`, `student`, `install`) | 71 | 32 | 243 | 234 |

Proposed class by the rules in section 4, per route in the CSV: **A** public 127 (plus 38 sign-in/OTP/reset and 16
payment callbacks), **B** signed-in 57, **C** role/owner/state 612, **D** sensitive 49 (38 of them without
`fresh.auth`; see 5.1).

Global middleware ([bootstrap/app.php:28-38](../../firalink%20web/firalink/bootstrap/app.php#L28-L38)): `api`
appends `SetLocale` and `EnsurePhoneIsVerified`. `web` appends canonical/security headers, `EnsureSetupComplete`,
`SetLocale` and `EnsurePhoneIsVerified`. The 401 body is the same `{"success":false,"message":"Unauthenticated."}`
for every API route ([:76-81](../../firalink%20web/firalink/bootstrap/app.php#L76-L81)). Sanctum tokens expire after
`SANCTUM_EXPIRATION_MINUTES`, default 43 200 (30 days) ([config/sanctum.php:50](../../firalink%20web/firalink/config/sanctum.php#L50)).

### 2.2 Public routes that write (35 api + 32 web)

| Route | What it does | Safe? |
|---|---|---|
| `POST /api/auth/*` (22), `POST /api/instructor/auth/*` (5), web `/login`, `/register`, `/forgot-password`, `/reset-password`, `/login/otp*`, `/become-instructor` | Sign-in, sign-up, OTP, reset | Yes, intended. All throttled 10/min/IP; reset answers are identical whether the account exists or not. |
| `POST /api/fira-ai/send`, `/api/ai-chat/send`, web `/ai-chat/send` | AI chat; signed-in users get stored history | Partly. Inline limit 5/min per IP for guests and 20/min per user ([AiChatController.php:107-119](../../firalink%20web/firalink/app/Http/Controllers/Student/AiChatController.php#L107-L119)), no daily cap. `GEMINI_ENABLED` is false by default, so no model cost today. **Decision D-1.** |
| `POST /api/coffee-toolkit/calculate`, `/assess`, `/api/export-docs/calculate`, web `/free-tools/calculate`, `/ai-analysis`, `/ask-advisor` | Stateless calculators; `/assess` scores without saving for guests | Yes. **No route throttle** (P3). `/free-tools/ai-analysis` and `/ask-advisor` can call Gemini when it is enabled (P2, same as D-1). |
| `POST /api/billing/webhook/{provider}`, web `/billing/webhook/{provider}`, `/payment/chapa/webhook` | Gateway webhooks | Covered by GAP-001 (signature, re-verify, amount check). Not re-audited here. |
| `POST /api/courses/payments/chapa/verify` | Verifies a Chapa course payment by `tx_ref` | Public duplicate of the signed-in route (`/api/payments/chapa/verify` at [api.php:106](../../firalink%20web/firalink/routes/api.php#L106) is overridden by the signed-in copy at [:241](../../firalink%20web/firalink/routes/api.php#L241); the `/courses/...` alias at [:107](../../firalink%20web/firalink/routes/api.php#L107) stays public). It re-checks with Chapa and checks the amount, so a guest cannot fake a payment. P3: make it signed-in like the others. |
| `GET` and `POST /api/marketplace/subscribe/chapa/{callback,verify}` | Seller plan payment check | **P1 leak.** For a completed payment it returns `$payment->load('marketplaceSubscription.plan')`, the whole payment row, to anyone with the `tx_ref` ([MarketplaceSellerController.php:388-393](../../firalink%20web/firalink/app/Http/Controllers/Api/MarketplaceSellerController.php#L388-L393)). The `tx_ref` is `MKT-SUB-{userId}-{unix time}` ([MarketplaceSubscriptionService.php:155](../../firalink%20web/firalink/app/Services/MarketplaceSubscriptionService.php#L155)), so it can be guessed. `Payment` has no `$hidden`. |
| `POST /api/verify-certificate`, `/api/instructor/verify-certificate`, web `/verify-certificate` | Certificate lookup by code | Yes (read-only). No throttle (P3). |
| web `POST /tours/{slug}/book`, `GET /tours/{slug}/book` | **Guest web tour booking** | **P1.** Bypasses every slice 8 rule: sign-in, host business, future dates, deposit ([Front/TourController.php:134-230](../../firalink%20web/firalink/app/Http/Controllers/Front/TourController.php#L134-L230)). Free tours are confirmed straight away. Outside the web launch scope but live. **Decision D-8.** |
| web `GET /tours/booking/confirmation/{reference}`, `/events/booking/confirmation/{reference}`, `/events/bookings/verify/{reference}` | Booking page by reference | **P2.** Shows name, email, phone and payment to anyone with the reference (`TR-` + 8 random characters), with no sign-in and no throttle ([Front/TourController.php:385-390](../../firalink%20web/firalink/app/Http/Controllers/Front/TourController.php#L385-L390), [Front/EventController.php:302-306](../../firalink%20web/firalink/app/Http/Controllers/Front/EventController.php#L302-L306)). |
| web `POST /blog/{slug}/comments` | Guest comment | **P2.** Auto-approved (`is_approved => true`, [BlogCommentController.php:51](../../firalink%20web/firalink/app/Http/Controllers/Front/BlogCommentController.php#L51)), no throttle, no captcha. |
| web `/contact`, `/newsletter/subscribe` | Contact form, newsletter | No route throttle (P3). |
| web `/free-tools/save-calculation` | Save | Refuses guests inline (`requires_auth`). OK. |
| web `/verify-phone`, `/verify-phone/resend`, `/verify-phone/change` | Phone verification for a signed-in session | Act on the session user; inline check. Throttle not on the route (P3). |
| web `/payment/{razorpay,sslcommerz,xpay,…}/callback` | Legacy gateway callbacks | Covered by GAP-035. Remove the unused ones (P3). |
| web `PUT /storage/{path}`, `GET /storage/{path}` | Laravel `local` disk serve/upload (`'serve' => true`, [config/filesystems.php:33-38](../../firalink%20web/firalink/config/filesystems.php#L33-L38)) | Signed URLs only (framework `ServeFile`/`ReceiveFile`). The `local` root is `storage/app/private`, which **contains `verification_vault`**. Anyone holding a signed `local`-disk URL could read a verification file. Nothing creates such URLs today; P2 to turn `serve` off. |
| web `/install/*` | Installer | Locked once installed ([SetupController.php:22-29](../../firalink%20web/firalink/app/Http/Controllers/Setup/SetupController.php#L22-L29)). OK. |
| web `GET /cron/run` | Runs the scheduler with a key | Key compared with `hash_equals`, but it travels in the query string, so it ends up in access logs (P3). |
| web `POST /subscriptions/{id}/checkout` | Redirect closure | Harmless. |

### 2.3 Signed-in routes without a policy or ownership signal

The scanner flagged 10 API routes with a path id and no visible check. I opened each one:

| Route | Result |
|---|---|
| `POST /api/marketplace/samples/{sample}/pay`, `/cancel` | OK: `Gate::allows('buyerAct')` in `act()` ([MarketplaceSampleController.php:143-155](../../firalink%20web/firalink/app/Http/Controllers/Api/MarketplaceSampleController.php#L143-L155)). |
| `DELETE /api/business-blocks/{organizationId}` | OK: acts only on the caller's active business. |
| `POST /api/events/{id}/book`, `/api/tours/{id}/book`, `/api/bundles/{id}/checkout` | OK for ownership: they create for the caller. Policies exist for bookings (`EventBookingPolicy`, `TourBookingPolicy`) but are applied in the service. |
| `POST /api/marketplace/listings/{id}/save`, `/inquire` | OK: caller-scoped; `/inquire` also has `verified.role`. P3: `/save` does not check that the lot is buyer-visible, so a hidden lot id can be saved. |
| `POST /api/community/questions/{id}/answers`, `/community/vote/{type}/{id}` | OK: caller-scoped. |

`GET /api/org/{id}/entitlements` is fixed: member or admin, otherwise 404
([OrganizationEntitlementApiController.php:24-36](../../firalink%20web/firalink/app/Http/Controllers/Api/OrganizationEntitlementApiController.php#L24-L36)).

**Not verified by hand:** the 426 web role-gated routes (`/admin/*`, `/instructor/*`, `/student/*`) count as class C
because of `role:` or `can:` middleware. Ownership inside each instructor and student controller method was only
pattern-checked (CSV columns `policy`, `abort_check`, `owner_scope`). Phase 1 tests will cover them.

### 2.4 Routes where auth is optional and the controller behaves differently

| Route | Behaviour | Safe? |
|---|---|---|
| `GET /api/marketplace/listings/{idOrSlug}` | `$request->user('sanctum')`: a member of the lot's business may open a hidden (draft) lot; others get 404 ([MarketplaceService.php:159-169](../../firalink%20web/firalink/app/Services/MarketplaceService.php#L159-L169)) | Yes. Hidden lots never leak to others. |
| `GET /api/courses/{id}` | Adds enrollment state for the user | Yes. |
| `GET /api/fira-ai`, `/api/ai-chat` | Returns the user's own history, empty for guests | Yes. |
| `POST /api/fira-ai/send` | Saves history only for a user; limit by user or IP | Yes (see D-1). |
| `GET /api/video/stream` | Authorizes from the lesson; **also accepts `?token=` in the URL** ([FileController.php:65-68](../../firalink%20web/firalink/app/Http/Controllers/Api/FileController.php#L65-L68)) | P2: the token ends up in access logs (GAP-051 still open). |
| `GET /api/export-docs/templates` | Adds trial info for a user | Yes. |
| `POST /api/auth/phone/verify/send` | Uses the user when present | Yes. |

### 2.5 Public routes that return personal or private data

- `GET /api/marketplace/listings`, `/{id}` embed the raw `organization` model
  ([MarketplaceService.php:26](../../firalink%20web/firalink/app/Services/MarketplaceService.php#L26), [:147](../../firalink%20web/firalink/app/Services/MarketplaceService.php#L147)).
  TIN, billing email, creator and the review flag are hidden ([Organization.php:33](../../firalink%20web/firalink/app/Models/Organization.php#L33)).
  Still exposed: internal `id`, `slug`, `status`, `is_verified`, `verified_roles`, `verification_tier`,
  `verification_valid_until`, `verification_reviewed_at`, `tier1_first_granted_at`, `fira_id`, `logo_path`,
  timestamps and `deleted_at`. The listing row also carries `seller_id` (a user id). That is P2: it allows
  enumeration, and the badge fields are raw (section 6).
  The seller user object itself is hidden ([CoffeeListing.php:75](../../firalink%20web/firalink/app/Models/CoffeeListing.php#L75)).
- `GET /api/sellers/{firaId}` and web `/verify/{firaId}`: by Fira ID only, throttled, built by `PublicSellerProfile`. OK.
- Seller plan verify (2.2) and booking confirmations (2.2): P1/P2 above.
- `EnsurePhoneIsVerified` answers 403 with the **full phone number** in `phone`
  ([EnsurePhoneIsVerified.php:35-38](../../firalink%20web/firalink/app/Http/Middleware/EnsurePhoneIsVerified.php#L35-L38)).
  It is only sent to the account owner, but it should be masked only (P3).

---

## 3. App inventory

The full list is in the screens CSV. Below are the **guest-reachable screens that mutate or show personal data**.
"Guest-reachable" means a guest can open the screen today: either it is not router-guarded, or it is pushed directly.

| Screen | How a guest gets there | Reads / mutates | Endpoints | Checks sign-in? | Sev |
|---|---|---|---|---|---|
| Settings ([settings_screen.dart](../../firalink%20flutter/lib/screens/profile/settings_screen.dart)) | Profile tab → Settings; `/settings` unguarded | Mutates | `POST /user/delete-request`; opens Edit profile and Change password | No | **P0** |
| Edit profile | Settings → direct push ([:352](../../firalink%20flutter/lib/screens/profile/settings_screen.dart#L352)) | Mutates | `POST /user/profile`, `/user/profile-photo` | No (router guard bypassed) | **P0** |
| Change password | Settings → direct push ([:364](../../firalink%20flutter/lib/screens/profile/settings_screen.dart#L364)) | Mutates | `POST /user/password` | No | **P0** |
| Profile tab | Dock tab, no guard | Shows the previous user's workspace rows after logout; "Guest User" header ([profile_screen.dart:315](../../firalink%20flutter/lib/screens/profile/profile_screen.dart#L315)) | `POST /logout` | Only for the Sign in / Logout row ([:943](../../firalink%20flutter/lib/screens/profile/profile_screen.dart#L943), [:980](../../firalink%20flutter/lib/screens/profile/profile_screen.dart#L980)) | P1 |
| Notification settings | Profile → Notifications; `/notification-settings` unguarded | Mutates | `GET`/`PUT /user/settings/notifications`, `POST /notifications/test-push` | No | P1 |
| Workspace switcher / Team seats / Create workspace | Profile rows (shown from stale workspace) | Mutates | `POST /user/organizations/switch`, `DELETE /organizations/{id}/members/{userId}` | Switcher only | P1 |
| Seller portal, Seller home, Create lot, Lot evidence, Lot photo capture | `/marketplace/seller*` unguarded; Profile rows | Mutates | `/marketplace/seller/*`, `/seller/lots/*` | No | P1 |
| Organization verification, Document capture | `/marketplace/verification` unguarded; push notification `verification` | Mutates, shows document state | `/marketplace/verification/*` | No | P1 |
| Payout account | Direct push | Mutates (bank details) | `GET`/`POST /payout-account` | No | P1 |
| RFQs, Create RFQ, Quote request, Compare quotes | `/marketplace/rfqs*` unguarded; push `rfq` / `quote` | Mutates | `/marketplace/rfqs*`, `/quotes/*`, `POST /deal-rooms` | No | P1 |
| Samples, Request sample, Ship sample, Sample tracking | Profile rows; push `sample` (no id → sample 1) | Mutates | `/marketplace/samples*` | Tracking only (router) | P1 |
| Deal rooms, Deal step, Payment milestones, Contract e-sign, Dispute | Profile rows (direct push); push `deal_room` | Mutates | `/deal-rooms/*` | Hub only (router) | P1 |
| Trade chats, Trade chat | Profile row (direct push) | Mutates | `/trade-chats/*` | No | P1 |
| Saved lots | Profile row (direct push) | Reads | `/marketplace/saved-listings` | No | P2 |
| Lot detail: Save, Request sample, Message | Marketplace (public) | Mutates | `POST /listings/{id}/save`, … | After the call: checks `401` ([coffee_listing_detail_screen.dart:258](../../firalink%20flutter/lib/screens/marketplace/coffee_listing_detail_screen.dart#L258)) | P2 |
| Ticket booking, Tour booking, My tour bookings, Tour booking confirmation | Event/tour detail (public) | Mutates | `POST /events/{id}/book`, `/tours/{id}/book`, `/user/tour-bookings*` | No (server 401) | P2 |
| Checkout, Offline payment, Bundle checkout | Course detail (public) | Mutates | `/courses/{id}/enroll`, `/bundles/{id}/checkout`, `/coupons/validate`, `/payments/chapa/verify` | Checkout only | P2 |
| Coffee calculator, Offer generator | `/coffee-toolkit/*` unguarded | Mutates (saves) | `POST /coffee-toolkit/calculations`, `/offers` | No | P2 |
| Export docs studio, editor | `/export-docs` unguarded | Mutates | `/export-docs/*` | Studio shows a sign-in card; editor checks 401 after | P2 |
| Study timer, Focus progress, Weekly review, AI suggestions | Unguarded routes | Mutates/reads | `/focus/*`, `/habits/*` | No | P2 |
| Q&A question detail | From public Q&A | Mutates | `POST /community/questions/{id}/answers`, `/answers/{id}/accept` | No (list is guarded, detail is not) | P2 |
| Course access, quiz, assignment, discussions | From My courses (guarded) or course detail | Mutates | `/user/courses/*`, `/courses/{id}/discussions/*` | No | P2 |
| Intent selection, Business workspace setup | `/onboarding/intent` unguarded | Mutates | `POST /user/onboarding-intent`, `POST /user/organizations` | No | P2 |
| Search | `/search` | Reads (server needs sign-in) | search endpoints | Throws 401 for guests ([search_screen.dart:50](../../firalink%20flutter/lib/screens/search/search_screen.dart#L50)) | P3 |
| Wishlist | `/wishlist` (guarded) | Local only, never on the server, not cleared on logout | none | Router | P2 |

**Push notification taps** ([push_notification_service.dart:249-279](../../firalink%20flutter/lib/services/push_notification_service.dart#L249-L279)):
the deal-room id is passed as a **String**, and the router uses `arguments is int ? arguments : 1`
([app_router.dart:247-252](../../firalink%20flutter/lib/router/app_router.dart#L247-L252)). **Every deal-room tap
opens deal room 1**, and every sample tap opens sample 1. The server's policy blocks access to rooms you're not
part of, so this is a wrong-screen bug, not a leak (P1). Verification and RFQ taps open unguarded screens.

---

## 4. Classification

The rules I applied to every route (column `proposed_class` in the routes CSV):

- **A — Public, read-only, guest OK.** Rate-limited per IP; no private fields.
- **B — Signed in.** Valid token; acts only on the caller's own data.
- **C — Signed in + right role / owner / workspace / state.** Policy + ownership + active workspace + tier where the rules say so.
- **D — Sensitive.** C + fresh re-auth (code or password within 10 minutes) on the server, throttled per user and IP, the user notified, audit entry.

| Action | Class | Server today | App today | Gap |
|---|---|---|---|---|
| Onboarding, language, about, terms, privacy, custom pages | A | Public | Public | — |
| Public seller check by Fira ID (`/api/sellers/{firaId}`, web `/verify/{firaId}`) | A | Throttled, Fira ID only | Public | — |
| Browse lots (list, detail, filters), exporters list | A (exporters: B today) | Public, Tier 1 sellers only; raw org fields | Public | S-11 |
| Academy catalog, courses, bundles, instructors, events, tours (list/detail) | A | Public, **no throttle** | Public | S-17 |
| Market data (`/api/market/*`) | A | Throttled `market-public` | Public | — |
| Coffee calculators (`calculate`, `assess`, export-docs `calculate`, templates) | A | Public, no throttle | Public | S-17 |
| Fira AI basic chat | A or B — **D-1** | Public, 5/min/IP | Public | D-1 |
| Certificate verify | A | Public, no throttle | Public | S-17 |
| Sign in, sign up, OTP, forgot/reset | A-auth | Throttled 10/min | — | — |
| View own profile `/api/user`, logout | B | ✔ | — | — |
| Update profile name, bio, photo | B | ✔ | Unguarded | M-1, M-2 |
| Notifications, notification settings, device tokens | B | ✔ | Settings unguarded | M-2 |
| Enrollments, my courses, lessons, quizzes, assignments, certificates | B/C | ✔ (role student for web) | Partly guarded | M-3 |
| Event/tour bookings (app) | B | ✔ slice 8 | Unguarded booking screens | M-3 |
| Web guest tour booking | should be B | **Public** | — | S-5 |
| Saved lots, course wishlist | B | Lots ✔; course wishlist not on server | Local wishlist | M-10 |
| Workspaces: list, create, switch, intent | B | ✔ | Unguarded intent | M-3 |
| AI history, focus, habits, toolkit saves | B | ✔ | Unguarded | M-3 |
| Team members add/remove | C (owner/manager) | ✔ inline | Stale workspace rows | M-4 |
| Seller lots, evidence, publish, pause | C (seller business, Tier 1 to publish) | ✔ | Unguarded | M-3 |
| RFQ create/close, quote submit/accept/decline, compare | C | ✔ `MarketplaceRfqPolicy` | Unguarded | M-3 |
| Samples request/pay/cancel/dispatch/delivered/evaluate | C | ✔ `MarketplaceSamplePolicy` | Unguarded, wrong id from push | M-3, M-7 |
| Deal rooms: open, sign, advance, dispute, complete, chat | C | ✔ `DealRoomPolicy` | Unguarded, wrong id from push | M-3, M-7 |
| Trade chats, blocks, reports | C | ✔ policies | Unguarded | M-3 |
| Verification status, delete document | C | ✔ | Unguarded | M-3 |
| **Upload verification document** | D | ✔ `fresh.auth` | No step-up UI found | M-6 |
| **Change password** | D | ✔ `fresh.auth` or current password; revokes other tokens. **No audit, no notification** | Unguarded | S-8, M-1 |
| **Change email** | D | ✔ fresh + code to new address + audit. **Old address not notified** | No UI found | S-8 |
| **Change phone** | D | ✔ `/user/phone`. **But `POST /user/profile` also changes or removes the phone without fresh auth** | Edit profile sends phone | S-6 |
| **Delete account (request)** | D | **No `fresh.auth`** (user and instructor routes) | Settings, unguarded | S-7 |
| **Payout account** | D | ✔ fresh + reviewer confirmation + 48 h | No step-up UI found | M-6 |
| **Confirm milestone (finance)** | D | ✔ fresh | — | — |
| **Instructor withdrawal** | D | ✔ fresh | — | — |
| **Admin: payments approve/reject/status, withdrawals status, offline subscription approve, user delete, account-deletion approve, gateway/SMTP/SMS/auth/AI/reCAPTCHA/custom-scripts settings, impersonation** | D | Role admin + staff 2FA. **No fresh re-auth** | — | S-2 |
| **Admin: verification review approve/reject/changes, Tier 3, suspend, payouts confirm, report decide, price approve** | D | Staff 2FA + `can:review-verifications`; step-up only for Tier 2/3 approval ([DocumentReviewService.php:261](../../firalink%20web/firalink/app/Services/Verification/DocumentReviewService.php#L261), [:379](../../firalink%20web/firalink/app/Services/Verification/DocumentReviewService.php#L379)) | — | S-2, D-5 |

---

## 5. Gap list

Severity: **P0** — a guest or the wrong user can change or see account data today. **P1** — real data exposure,
wrong-user access, or a missing server control on a money/identity action. **P2** — a defence gap or misleading UI
with no direct exploit. **P3** — hygiene.

### 5.1 Server

| ID | Sev | Gap | Proposed fix |
|---|---|---|---|
| S-1 | P1 | No route-inventory test. Nothing stops a new route from shipping without `auth:sanctum`. | `config/public_routes.php` allow-list + `RouteInventoryTest`: fail if a route is not on the list and has no `auth` middleware; fail if a public route mutates and is not on the auth/OTP/reset/webhook list. |
| S-2 | P1 | 36 admin and reviewer class-D actions have no fresh re-auth (list in the CSV, flag `NO_FRESH_AUTH`). Staff 2FA is checked once per session. | `fresh.auth` (password or TOTP within 10 min) on money, settings, user-delete, account-deletion-approve and impersonation routes. Verification review: see D-5. |
| S-3 | P1 | Public seller-plan verify returns another user's full payment row; `tx_ref` = `MKT-SUB-{userId}-{time}`. | Move into `auth:sanctum` + owner check; return only `{status}`; random `tx_ref`; `$hidden` on `Payment`. The GET callback should redirect, not return JSON. |
| S-4 | P1 | Impersonation: no audit entry, no session regenerate, and the admin's `fresh_auth_at` carries into the impersonated session. An admin acting as a business owner could pass `fresh.auth` (payout, documents). ([ImpersonationController.php:26-50](../../firalink%20web/firalink/app/Http/Controllers/Admin/ImpersonationController.php#L26-L50)) | Audit start and stop; clear the fresh flag; block every class D action while impersonating; or remove impersonation (D-6). |
| S-5 | P1 | Web guest tour booking bypasses slice 8 rules. | Require sign-in and route through `TourBookingService`, or close the web booking pages (D-8). |
| S-6 | P2 | `POST /user/profile` changes or **clears** the verified phone without fresh auth ([ProfileController.php:91-96](../../firalink%20web/firalink/app/Http/Controllers/Api/ProfileController.php#L91-L96), [User.php:125-152](../../firalink%20web/firalink/app/Models/User.php#L125-L152)). It removes the SMS step-up channel. The "linked to another account" message also reveals that a number is registered. | Ignore `phone` in the profile form; phone changes go only through `/user/phone`. Generic error. |
| S-7 | P2 | Delete-account request has no `fresh.auth` (`/api/user/delete-request`, `/api/instructor/settings/delete-request`). | Add `fresh.auth`; notify the user. |
| S-8 | P2 | Password change (API) writes no audit entry and sends no notice. Email change does not notify the old address. Phone change sends no notice. No sensitive action notifies the user. | `SecurityNotice` notification (database + email/SMS where available) for password, email, phone, payout, delete request; audit entries for each. |
| S-9 | P2 | Denied sensitive attempts are not logged: `EnsureFreshAuth` 403s, step-up failures, 403s from policies. | Audit `security.denied` (user, route, IP, reason) with per-user sampling. |
| S-10 | P2 | `fresh.auth` throttling is per route group (`throttle:10,1` per IP on step-up), not per user on the protected action. | Named limiter `sensitive` by user id + IP on every class D route. |
| S-11 | P2 | Public lot JSON embeds the raw organization (`is_verified`, `verified_roles`, raw `verification_tier`, `status`, internal ids) and `seller_id`. | Replace with `BusinessSummary` (tier only when active and not suspended); hide `seller_id`, `organization_id`. |
| S-12 | P2 | `GET /api/video/stream?token=` accepts a token in the URL (GAP-051). | Signed URLs only. |
| S-13 | P2 | Booking confirmation pages by reference show PII without sign-in or throttle. | Require sign-in as the booking owner, or show only status; throttle. |
| S-14 | P2 | Blog comments auto-approved, unthrottled. | Moderation queue + throttle + reCAPTCHA, or close (outside launch scope). |
| S-15 | P2 | `local` disk `serve: true` with the verification vault under its root. | `serve => false` (nothing uses it), or move the vault outside `app/private`. |
| S-16 | P2 | `User::$fillable` contains `role`, `is_approved`, `phone_verified_at` ([User.php:56-78](../../firalink%20web/firalink/app/Models/User.php#L56-L78)). No endpoint passes request data into it today, but one careless `update($request->all())` would allow self-promotion. | Remove from `$fillable`; set with `forceFill` in the few places that need it; add a test. |
| S-17 | P3 | Class A routes without a per-IP limit: settings, home, courses, bundles, instructors, events, tours, community, marketplace listings/filters/plans, coffee-toolkit, export-docs templates/calculate, certificate verify. | One `public` limiter (for example 120/min/IP) on the whole public group. |
| S-18 | P3 | Cron key in the query string. | Header only. |
| S-19 | P3 | `/api/courses/payments/chapa/verify` is still public; legacy gateway callbacks remain. | Move into the auth group; delete unused callbacks. |
| S-20 | P3 | `EnsurePhoneIsVerified` 403 body includes the full phone. | Masked only. |
| S-21 | P3 | `POST /marketplace/listings/{id}/save` accepts hidden lot ids. | Check `visibleToBuyers`. |

### 5.2 Mobile

| ID | Sev | Gap | Proposed fix |
|---|---|---|---|
| M-1 | **P0** | "Guest" can hold a valid token; every request sends it. The UI says guest while the server acts as the user. | One `AuthState` (`unknown`, `guest`, `signedIn`, `expired`). `guest` means no token in storage. The HTTP layer sends a token only in `signedIn`. Splash waits for `unknown` to resolve. On startup failure with a token, show "Can't reach Fira" with Retry, not guest mode. |
| M-2 | **P0** | Settings, Edit profile, Change password, Delete account and Notification settings are reachable by guests. Edit profile and Change password are pushed past the router guard. | Guest Settings shows only Language, About, Terms, Privacy, Help, and Sign in / Create account. Account sections are not built for guests. Every protected screen is wrapped by the guard, whatever way it is pushed. |
| M-3 | P1 | Route guard is an opt-in list of 19 routes; about 40 protected screens are pushed directly and never pass a guard. | Default deny: the router guards every route that is not on a class A allow-list. Protected screens get a `requiresSignIn` wrapper so direct pushes are covered too. A test walks all routes. |
| M-4 | P1 | Logout and 401 leave the previous user's workspace, chats, dashboard, toolkit data, wishlist, recent searches, receipts and image cache. After a second sign-in the first user's workspace is shown until the switcher is opened. | `SessionReset.wipe()` called on logout, 401, and before every sign-in: reset every provider, clear user-scoped SharedPreferences keys, delete receipts, clear the image cache, reset the navigation stack. `loadWorkspaces()` after sign-in. |
| M-5 | P1 | No global 401/403/429 handling; `loadUser` signs out on 403. | One API client (extend `guardedRequest`): 401 with a token → wipe + guest + message. 403 → "not allowed" state (with `FRESH_AUTH_REQUIRED` and `requires_phone_verification` handled). 429 → retry-after. 403 never signs out. |
| M-6 | P1 | No step-up UI. `fresh.auth` routes (verification upload, payout account, phone/email change) answer `FRESH_AUTH_REQUIRED` and the app shows a generic error. | `StepUpSheet` (code or password) on `FRESH_AUTH_REQUIRED`, then retry the call once. |
| M-7 | P1 | Push taps open deal room 1 / sample 1 (String id vs int check). | Parse ids; no default id; when parsing fails, show "not found". Guard the target. |
| M-8 | P1 | `AuthGuard` replaces the screen with sign-in and loses the interrupted action. Buttons (save, request sample, message, book, enroll, post RFQ) call the server first and react to 401 afterwards. | `requireSignIn(context, then: action)`: shows the sign-in sheet **before** any call, then resumes the action. |
| M-9 | P2 | Secure-storage failure falls back to plaintext SharedPreferences for the token. | No plaintext fallback: if secure storage fails, don't keep the session (sign in again). Delete any legacy copy on start. |
| M-10 | P2 | Course wishlist is device-local and survives logout and account switch. | Scope it by user id or clear on wipe; label it as on-this-device. |
| M-11 | P2 | `PaymentService` returns `[]` on no token and on errors (failure looks like "no payments"). | Throw `ApiException`; show the error state. |
| M-12 | P2 | Success snacks "Profile updated successfully" / "Password updated successfully" are hardcoded English ([edit_profile_screen.dart:135](../../firalink%20flutter/lib/screens/profile/edit_profile_screen.dart#L135), [change_password_screen.dart:42](../../firalink%20flutter/lib/screens/profile/change_password_screen.dart#L42)). Errors show raw `Error: Exception: …`. | Localized en + am strings; map `ApiException` to messages. |
| M-13 | P2 | Edit profile sends `email` and `phone` on every save; the server rejects a changed email (422) and silently unverifies a changed phone. | Send name, bio, photo only; separate Change email / Change phone flows with codes. |
| M-14 | P3 | Startup decision after a fixed 2.6 s delay. | Wait for `AuthState` (with a timeout that leads to the offline state). |

### 5.3 Verification badges (details in section 6)

| ID | Sev | Gap | Proposed fix |
|---|---|---|---|
| B-1 | P1 | Lot detail shows a green verified tick for **every** seller, next to a self-declared "ECTA License" line. The tier text reads the raw embedded organization. | Badge only from the server tier (`BusinessSummary`, active and not suspended); no tick otherwise; drop or label the license line as "stated by the seller". |
| B-2 | P1 | `MarketplaceSeeder` has no environment guard and marks seller profiles `verified`. | Refuse to run outside `APP_ENV=local`; test. |
| B-3 | P2 | `/api/user/organizations` and `/api/marketplace/seller/profile` send raw legacy `is_verified` / `verified_roles`. These stay true after expiry (until the daily job runs) and after suspension. | Derive them from `hasActiveTier(1)` and not suspended (D-9). |
| B-4 | P2 | Web suppliers list, supplier page and verification center show badges from legacy fields, not the tier. | Read the tier through `PublicSellerProfile` / `BusinessSummary` (D-10). |
| B-5 | P2 | Flutter keeps legacy flags (`OrganizationModel.isVerified/verifiedRoles`, `WorkspaceProvider.isVerified*`, `User.isVerified*`). | Remove; use the server `tier` and `visible_to_buyers` only. |
| B-6 | P2 | The Export docs "Pro Active" banner uses a green verified tick: a paid plan that looks like verification. | Plan state without a verified icon, from `fira` tokens, localized. |
| B-7 | P2 | No test proves that verification fields cannot be set through any endpoint, no scan for other writers, and no `verification:audit-badges` report. | Phase 3 deliverables. |
| B-8 | P3 | Verified-style icons on data labels (toolkit "ECTA Floor", chart "Data Source"). | Neutral info icon. |
| B-9 | P3 | Raw-tier reads that are safe today only because of an outer filter: exporters card, `min_tier` filter, admin seller labels. | Read through `hasActiveTier()` + status. |

### 5.4 User-visible text that promises something unprotected or unearned

| Where | Text | Problem |
|---|---|---|
| Settings (guest) | "Edit profile", "Change password", "Delete account — Permanently remove account" | Shown to guests; the actions fail with a raw error or, with a leftover token, really run. |
| Profile header (guest) | "Guest User" with avatar, plus business rows from the previous user | Looks like an account. |
| Lot detail seller card | green ✔ + "ECTA License: …" ([coffee_listing_detail_screen.dart:655-659](../../firalink%20flutter/lib/screens/marketplace/coffee_listing_detail_screen.dart#L655-L659)) | Reads as "verified" for any seller; the license number is self-declared and unreviewed. |
| Export docs studio | ✔ green "Fira Export Docs Studio Pro Active" ([export_docs_studio_screen.dart:816-821](../../firalink%20flutter/lib/screens/marketplace/export_docs_studio_screen.dart#L816-L821)) | A paid plan shown with a verified tick (plans must never look like verification). Also hardcoded English and `Colors.green`. |
| Web suppliers list and supplier page | badge "Verified Ethiopian Coffee Exporter" from `seller_profile.verification_status` ([suppliers.blade.php:1092-1093](../../firalink%20web/firalink/resources/views/marketplace/suppliers.blade.php#L1092-L1093), [supplier-show.blade.php:509](../../firalink%20web/firalink/resources/views/marketplace/supplier-show.blade.php#L509)) | Not the tier. |
| Demo seed | subscription note "Verified Ethiopian exporter tier." ([MarketplaceSeeder.php](../../firalink%20web/firalink/database/seeders/MarketplaceSeeder.php)) | Plan text that claims verification. |

---

## 6. Verification-badge inventory

**Rule to enforce:** a badge exists only when the server computed Tier ≥ 1 from approved, unexpired documents
(`TierService`) and the business is not suspended.

### 6.1 Where tier and trust values are stored and written

| Field | Stored on | Who writes it | Can a request or other code set it? | Can it show without approved, unexpired docs? |
|---|---|---|---|---|
| `verification_tier`, `verification_valid_until`, `verification_reviewed_at`, `tier1_first_granted_at`, `fira_id` | `organizations` | **Only** `TierService::recompute` ([TierService.php:100-160](../../firalink%20web/firalink/app/Services/Verification/TierService.php#L100-L160)), called from document review and the daily `verification:expire-and-remind` | Not fillable ([Organization.php:15-31](../../firalink%20web/firalink/app/Models/Organization.php#L15-L31)); plan overrides refuse the key ([SaasBillingAdminController.php:103](../../firalink%20web/firalink/app/Http/Controllers/Admin/SaasBillingAdminController.php#L103), [:161](../../firalink%20web/firalink/app/Http/Controllers/Admin/SaasBillingAdminController.php#L161)). `DropLegacyVerifications` only lowers it. | The raw column can be **stale up to a day** after expiry (until the job runs) and stays set on **suspension**. Readers must use `hasActiveTier()` + status. |
| `is_verified`, `verified_roles` (legacy) | `organizations` | `TierService::recompute` (derived: `tier >= 1`) | Not fillable | Stale the same way; **not cleared on suspension**. |
| `verification_status`, `verified_at`, `verification_notes` (legacy seller profile) | `marketplace_seller_profiles` | `VerificationReviewService` approve/reject seller profile ([:144-163](../../firalink%20web/firalink/app/Services/Verification/VerificationReviewService.php#L144-L163)); `DropLegacyVerifications` resets; **`MarketplaceSeeder` (no environment guard)** and `LocalDemoSellerSeeder` (local only) set `verified` | Not fillable ([MarketplaceSellerProfile.php:45](../../firalink%20web/firalink/app/Models/MarketplaceSellerProfile.php#L45)) | **Yes**: an admin profile approval or the seeder sets it without any document review. |
| `evidence_level` | `coffee_listings` | **Only** `LotLevelService::recompute`; create sets `claimed`; `ResetListingEvidenceLevels` lowers to `claimed` | Not fillable ([CoffeeListing.php:27](../../firalink%20web/firalink/app/Models/CoffeeListing.php#L27)) | No. |
| `verification_documents.status/tier` | `verification_documents` | `DocumentReviewService` (2FA reviewer, self-review block, step-up for Tier 2/3); **`qa:seed`** forces `approved` ([QaSeed.php:124](../../firalink%20web/firalink/app/Console/Commands/Qa/QaSeed.php#L124)) | `qa:seed` refuses to run in production and without `QA_TOOLS_ENABLED` (`GuardsQa`) | Only on staging through `qa:seed`. |
| `is_verified_seller`, `is_verified_exporter`, `is_verified_buyer` | Flutter `User` model only ([user_model.dart:63-65](../../firalink%20flutter/lib/models/user_model.dart#L63-L65)) | Server never sends them | — | Dead fields; remove. |
| `trust_score`, `dispute_rate` | Not present anywhere | — | — | — |
| Plans/payments | `user_marketplace_subscriptions`, `payments` | Billing services | Never touch tier fields (checked by grep) | No, but the Pro banner *looks* like verification (5.3). |

### 6.2 Where it is sent (API)

| Endpoint | Field | Source | OK? |
|---|---|---|---|
| `/api/sellers/{firaId}`, web `/verify/{firaId}` | `tier`, `vetted` | `PublicSellerProfile` (status-checked) | ✔ |
| `/api/marketplace/exporters`, `/{id}` | `verification_tier` | Raw column, but the list and the page only include approved, active, unsuspended sellers ([ExporterController.php:62-81](../../firalink%20web/firalink/app/Http/Controllers/Api/ExporterController.php#L62-L81)) | ✔ (P3: read through `hasActiveTier` for safety) |
| `/api/marketplace/seller/profile` | `verification_tier` (active-checked); **`is_verified` raw** ([MarketplaceSellerController.php:63-65](../../firalink%20web/firalink/app/Http/Controllers/Api/MarketplaceSellerController.php#L63-L65)) | | P2 |
| `/api/user/organizations` | **`is_verified`, `verified_roles` raw** ("legacy field for older app versions", [OrganizationController.php:42-43](../../firalink%20web/firalink/app/Http/Controllers/Api/OrganizationController.php#L42-L43)); `visible_to_buyers` correct | | P2 |
| `/api/marketplace/verification/status` | `tier` (active-checked, [OrganizationVerificationController.php:139](../../firalink%20web/firalink/app/Http/Controllers/Api/OrganizationVerificationController.php#L139)) | | ✔ |
| RFQ quotes, samples, deal rooms, events/tours | `BusinessSummary` → `verification_tier` (active-checked, [BusinessSummary.php:25](../../firalink%20web/firalink/app/Support/BusinessSummary.php#L25)); `EventTourPresenter` `verified` from it | | ✔ |
| Public lot list/detail | **raw embedded `organization`** with every tier and legacy field | | P2 (S-11); the app reads it (6.3, B-1) |
| Lot filter `min_tier` | raw `verification_tier` ([MarketplaceService.php:105](../../firalink%20web/firalink/app/Services/MarketplaceService.php#L105)) | Lots are already limited to active sellers, so the effect is small | P3 |

### 6.3 Where it is shown

| Place | Reads | OK? |
|---|---|---|
| Flutter lot detail seller card ([coffee_listing_detail_screen.dart:347](../../firalink%20flutter/lib/screens/marketplace/coffee_listing_detail_screen.dart#L347), [:648-659](../../firalink%20flutter/lib/screens/marketplace/coffee_listing_detail_screen.dart#L648-L659)) | Raw `organization.verification_tier` for the text; **green `Icons.verified` with no condition** | **P1**: tick for every seller, including the owner viewing a draft lot of an unverified business. |
| Flutter exporters list and page ([exporters_screen.dart:93](../../firalink%20flutter/lib/screens/marketplace/exporters_screen.dart#L93), [:165](../../firalink%20flutter/lib/screens/marketplace/exporters_screen.dart#L165)) | Server tier (approved only) | ✔ |
| Flutter `WorkspaceProvider.isVerified*` ([workspace_provider.dart:34-40](../../firalink%20flutter/lib/providers/workspace_provider.dart#L34-L40)), `OrganizationModel.isVerified/verifiedRoles` ([organization_model.dart:64-83](../../firalink%20flutter/lib/models/organization_model.dart#L64-L83)) | Legacy raw flags | P2: unused by screens today, but ready to be misused; remove. |
| Flutter trade models (`BusinessSummary.tier`) | Server tier | ✔ |
| Flutter `LotLevelChip` | Server `evidence_level` | ✔ |
| Flutter Export docs Pro banner, toolkit "ECTA Floor" tick, candlestick "Data Source" green tick | Not verification, but use verified icons | P2 / P3 (lookalike) |
| Web public verify page | `PublicSellerProfile` | ✔ |
| Web suppliers list and supplier page | Legacy `seller_profile.verification_status` | P2 (web outside launch scope, but live) |
| Web verification center ([center.blade.php:50-63](../../firalink%20web/firalink/resources/views/marketplace/verification/center.blade.php#L50-L63)) | Legacy `is_verified`, `verified_roles` | P2 |
| Admin sellers / seller detail | Raw `verification_tier` label | P3 (admin only; show "expired" when inactive) |
| Admin business review | `hasActiveTier(1)` | ✔ |

### 6.4 Seeders, factories, demo

- `DatabaseSeeder` calls no marketplace or verification seeder. The production seed path is clean today.
- `MarketplaceSeeder` (manual `db:seed --class`) has **no environment guard** and marks seller profiles `verified`. P1 for production safety: guard with `APP_ENV=local`.
- `LocalDemoSellerSeeder`: local only ✔.
- `UserFactory` is the only factory; no verification fields.
- `qa:seed`: staging only, guarded ✔ (must be on the allowed-writer list in the Phase 3 scan test).

---

## 7. Decisions I need from you

| # | Decision | Options | My recommendation |
|---|---|---|---|
| D-1 | Fira AI for guests | (a) public with a strict limit, (b) sign-in only | **(a)** for basic market and academy questions: 5/min **and 30/day** per IP, no history, no document analysis (already sign-in only). Gemini stays off until the key is rotated. |
| D-2 | Guest lot browsing | (a) list + detail view-only, (b) sign-in to see detail | **(a)**, as today: no contact data, no seller user, badge from `BusinessSummary` only. Save, sample, message, RFQ require sign-in. |
| D-3 | Exporters tab (`/marketplace/exporters`) | (a) stay signed-in only, (b) public | **(a)** for now (S-114 says signed-in). |
| D-4 | Delete-account request step-up | (a) fresh auth, (b) leave as admin-approved request | **(a)**. It is cheap and the request triggers account removal. |
| D-5 | Fresh re-auth for reviewer actions | (a) every review action, (b) only Tier 2/3 approval (today) + suspend/unsuspend + payout confirm | **(b)**. Reviewers work a queue under staff 2FA; payout confirm and suspension are the risky ones. Admin money and settings actions get fresh auth in every case (S-2). |
| D-6 | Admin impersonation | (a) remove before launch, (b) keep read-only with audit and no class C/D actions | **(a)** remove, or (b) if support needs it. Today it is a full log-in as the user. |
| D-7 | Token lifetime | (a) keep 30-day fixed expiry, (b) sliding expiry | **(a)**. Simpler and already in place; sign-in by phone code is quick. |
| D-8 | Web pages outside launch scope that write data as guest (tour booking, booking confirmation, blog comments, contact, newsletter) | (a) close or require sign-in now, (b) leave until web launch | **(a) for tour booking and booking confirmation** (money and PII); throttle the rest. This touches web outside CLAUDE.md's allowed screens, so I need your OK. |
| D-9 | Legacy `is_verified` / `verified_roles` in the API | (a) remove now, (b) keep, derived from active tier + not suspended | **(b)** now, remove in the next forced app update. |
| D-10 | Seller-profile "verified" (legacy admin approval) | (a) stop showing it as a badge anywhere, (b) keep as a separate label | **(a)**: tier is the only badge. Keep the admin data for history. |
| D-11 | Guest course wishlist | (a) keep on device, clear on account change, (b) sign-in only, saved on the server | **(a)** for now, labelled "on this device". |
| D-12 | 401 in the app | (a) any 401 from our API while signed in → sign out and wipe, (b) only 401 on `/api/user` | **(a)**. The server only answers 401 for a missing, invalid or expired token. |
| D-13 | Startup without network but with a token | (a) "Can't reach Fira" with Retry, keep the session, (b) continue as guest | **(a)**. (b) is how the reported bug happens. |
| D-14 | Commits | Phase 1 server, Phase 2 mobile, Phase 3 badges, docs: one commit each | As you wrote: separate server, mobile and docs commits. No push. |

## 8. Not verified (needs a test or your input)

- Whether `EnsurePhoneIsVerified` actually fires on API routes: it reads `$request->user()` from the global `api`
  group, which may run before `auth:sanctum` resolves the token. Phase 1 adds a test.
- The 426 web role-gated routes were checked for middleware, not for ownership inside each method (2.3).
- Behaviour on a real phone: secure-storage failure, image cache contents after logout, and deep links. No
  `app_links`/`uni_links` package is installed, so only push taps act as deep links today.
- Webhook and Chapa verify paths were not re-audited (GAP-001, GAP-032, GAP-034, GAP-035 in the 2026-11-19 analysis).
