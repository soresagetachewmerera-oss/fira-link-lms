# Messaging audit and plan — Phase 0 (2026-11-21)

Report only. No code changed. Everything below was read from the code at `HEAD` (b861f7e). File paths are
relative to `firalink web/firalink/` unless they start with `firalink flutter/`.

## 0. Summary

- **What exists:** 11 Mailables with 10 hand-built HTML email files and 1 default Laravel markdown
  mail, 7 Notification classes, 6 SMS templates stored in the database, one generic FCM channel, the
  database `notifications` table as the in-app inbox, and 3 PDF views (certificate, event ticket, coffee
  offer). There's no shared layout, catalogue, delivery log or queue.
- **Biggest problems:**
  1. **Nothing looks like one product.** Every email builds its own header and footer, and none
     has a logo. The colours are off-brand: cyan `#22d3ee`, violet `#7c3aed`, indigo `#6558f5`,
     blue `#2563eb`, orange `#f97316` and yellow `#facc15`, but never `#6D1A95`. The sender name is
     "Fira Link Academy".
  2. **No Amharic in any email or SMS.** Notifications store English and Amharic text, but the
     app's inbox shows only the English text (`firalink flutter/lib/models/notification.dart:26-27`).
  3. **Lock screen leaks:** trade pushes show counterparty names ("Abyssinia Green PLC requested a
     sample of …"). Verification pushes show the rejection reason. The payment SMS shows the amount and
     the payment reference.
  4. **One OTP email for every purpose.** Sign-in, sign-up, password reset, step-up and email change
     all send `LoginOtpMail` with the subject "Your one-time login code" (`app/Services/OtpService.php:215`, `:398`).
  5. **Big events send nothing:** deal rooms, event and tour bookings, Chapa receipts for events,
     tours and plans, certificates issued, welcome after phone sign-up, and new device sign-ins.
  6. **Nothing is queued and nothing is logged** for email or push. Only SMS has a log (`sms_logs`). A slow mail
     server blocks the request, and there's no retry and no record of what was sent.
  7. **There's no unsubscribe:** the newsletter goes to every subscriber in one message by BCC, with no
     unsubscribe link and no `List-Unsubscribe` header.
- **What is already good:** OTP generation and checking (`OtpService`: `random_int`, hashed code, expiry,
  attempt limit, cooldown, lockout, same answer for unknown accounts). `SmsLog` masks codes in
  `message_preview`. `SecurityNotice` (added on 2026-11-20) never puts codes or full contact details in a message.

## 1. Inventory

Legend for the columns:
- **Lang:** `en` = English only; `en+am` = both texts are stored or sent.
- **Test:** whether a test asserts that the message is sent.

### 1.1 Email (Mailables)

| Name | Trigger | Channel | File | Subject today | Lang | Test |
|---|---|---|---|---|---|---|
| `LoginOtpMail` | Every emailed code: sign-in, sign-up, password reset, step-up, email change, SMS fallback | mail | `app/Mail/LoginOtpMail.php`, `emails/auth/login-otp.blade.php` | "Your one-time login code" (same for every purpose) | en | yes (4) |
| `VerifyEmailMail` | Web email verification (`User::sendEmailVerificationNotification`) | mail | `app/Models/User.php:44`, `emails/auth/verify-email.blade.php` | "Verify your Fira Link Academy email address" | en | no |
| `WelcomeMail` | Instructor sign-up (API) only | mail | `Api/Instructor/AuthController.php:114`, `emails/auth/welcome.blade.php` | "Welcome to Fira Link Academy" | en | no |
| `AdminNewRegistrationMail` | Instructor sign-up → admin | mail | `Api/Instructor/AuthController.php:120` | "New instructor registration: {name}" (name in the subject) | en | no |
| `StudentCourseEnrollmentMail` | Course enrollment (API and web) | mail | `Api/CourseEnrollmentController.php:1152`, `Front/CourseEnrollmentController.php:3164` | "You enrolled in {course}" | en | no |
| `InstructorCourseEnrollmentMail` | Same → instructor | mail | same, `:1156`, `:3168` | "New enrollment in {course}" | en | no |
| `InstructorReviewNotificationMail` | Review approved | mail | `Admin/ReviewController.php:240`, `Front/ReviewController.php:168` | "New review on {course}" | en | no |
| `CourseUpdatedMail` | Instructor publishes course changes | mail | `Instructor/CourseBuilderController.php:1231` | "Updates in {course}" | en | no |
| `EventBookingBulkMail` | Instructor emails event attendees (free text) | mail | `Instructor/EventBookingEmailController.php:60` | Free text from the instructor | en | no |
| `ContactFormSubmitted` | Contact form → support | mail | `Front/ContactController.php:134`, `emails/contact/submitted.blade.php` (Laravel `mail::message`) | "{prefix} {visitor subject}" | en | no |
| `AdminNewsletterBlast` | Admin sends newsletter | mail (all subscribers in BCC) | `Admin/NewsletterController.php:62` | Free text | en | no |
| Password reset (web) | Forgot password on the website | mail | Laravel default `ResetPassword` (no override) | "Reset Password Notification" (Laravel default look) | en | no |

### 1.2 Notifications (database = in-app, FCM = push)

| Name | Events | Channels | File | Text today | Lang | Test |
|---|---|---|---|---|---|---|
| `SecurityNotice` | password changed, email changed (old and new address), phone changed, deletion requested | database, FCM, mail (+ mail to the old address) | `app/Notifications/SecurityNotice.php` | `security.notice.*` | en+am | yes |
| `VerificationUpdate` | in_review, approved, needs_changes, rejected, expired, expiring, tier_changed | database, FCM | `app/Notifications/VerificationUpdate.php`; sent from `DocumentReviewService`, `TierService` | `verification.notifications.*` incl. business name, document, **reason** | en+am | no |
| `VerificationAdminAlert` | new submission, daily pending summary → `VERIFICATION_ADMIN_EMAIL` | mail | `app/Notifications/VerificationAdminAlert.php` | "New verification: {business}" | en | no |
| `LotEvidenceUpdate` | evidence approved, rejected, needs changes, level changed | database, FCM | `app/Notifications/LotEvidenceUpdate.php`, `Lots/LotEvidenceService.php:53` | `lots.notifications.*` | en+am | no |
| `TradeUpdate` | quote received/accepted/not selected/declined; sample requested/paid/dispatched/delivered/approved/rejected; message received; payout changed/rejected | database, FCM | `app/Notifications/TradeUpdate.php`; `Trade/TradeService`, `SampleService`, `TradeChatService`, `PayoutAccountService` | `trade.notifications.*` with **counterparty names** | en+am | yes (3) |
| `LiveClassNotification` | scheduled, reminder, starting now, cancelled, updated | database, mail, FCM | `app/Notifications/LiveClassNotification.php`, `LiveClassService.php:175` | Hard-coded English; subjects with a leading space (" Live Now: …") | en | no |
| `GeneralNotification` | Admin broadcast (free title and message, optional image and URL) | database, FCM | `Admin/NotificationController.php:86`, `:119` | Free text | en (whatever the admin types) | no |
| Test push | User taps "test push" | FCM direct | `Api/NotificationController.php:115` | Fixed test text | en | no |

### 1.3 SMS (SMS Ethiopia, templates in `sms_templates`, editable in admin)

| Key | Trigger | File | Text today | Lang | Test |
|---|---|---|---|---|---|
| `otp` | Every SMS code (sign-in, sign-up, phone verify, reset, step-up) | `OtpService.php:165`, `:408`, `:486` → `SmsEthiopiaService::sendOtp` | "Your {app_name} verification code is: {otp}. It expires in {expiry} minutes. Do not share this code with anyone." | en | yes |
| `password_reset` | Seeded but **not used**; reset codes use `otp` | `app/Models/SmsTemplate.php:74` | "Your {app_name} password reset code is: {otp}…" | en | — |
| `registration_welcome` | Phone verified at registration (API and web) | `Api/AuthController.php:445`, `Auth/PhoneVerificationController.php:138` | "Welcome to {app_name}, {name}! … Start learning today." | en | yes |
| `course_enrollment` | Course enrollment | `CourseEnrollmentController` (API `:1162`, web `:3174`) | "Congratulations {name}! You have been successfully enrolled…" | en | no |
| `payment_confirmation` | Course payment confirmed | same, `:1165`, `:3177` | "Dear {name}, your payment of {amount} ETB for {item_title} was confirmed successfully. Ref: {tx_ref}. Thank you…!" | en | no |
| `cohort_announcement` | Seeded, no caller found | — | "Notice from {app_name}: …" | en | — |
| `admin_test` | Admin SMS test | `Admin/SettingsController.php:1100` | Admin text | en | no |

### 1.4 PDF and printable views

| View | Trigger | File | Notes |
|---|---|---|---|
| Certificate | Download (student, admin, API signed URL) | `student.certificate` via `Admin/CertificateController`, `Api/CertificateController`, `Student/StudentCourseController` | Own design; check brand |
| Event ticket | Student downloads a booking ticket | `student.events.booking-ticket` (`Student/EventBookingController`) | Own design |
| Coffee offer | Toolkit offer PDF | `student.coffee-toolkit.offer-pdf` (`CoffeeExportService`) | Own design |
| Export documents | Export Docs Studio | `ExportDocumentGeneratorService` (template per document) | Trade documents, not messages; out of scope |
| **Payment receipt** | — | **None.** `Api/PaymentController@downloadReceipt` only returns the uploaded bank slip | Gap |

### 1.5 Payment callbacks (Chapa)

| Callback | What the person is told today |
|---|---|
| Course payment verified (`CourseEnrollmentController@chapaVerify`, web callbacks) | `StudentCourseEnrollmentMail` + `course_enrollment` SMS + `payment_confirmation` SMS |
| Event and tour payment verified (`EventController@chapaVerify`, `TourController@chapaVerify`) | Nothing (no email, SMS, push or in-app message) |
| Seller plan payment verified (`MarketplaceSellerController@chapaVerify`) | Nothing |
| Bank slip approved or rejected by finance (`Admin/PaymentController`) | Nothing |
| Payment failed or refunded | Nothing |

### 1.6 In-app inbox and push client

- **Server:** Laravel `notifications` table; each row stores `title`, `message`, `title_am` and `message_am`, localised when it is sent.
- **App:** `firalink flutter/lib/models/notification.dart` reads only `title` and `message`, so Amharic users see English.
- **Android:** one notification channel `high_importance_channel` for everything (`push_notification_service.dart:33`).
  No security, deals, learning or updates channels, no iOS thread grouping, no quiet hours.
- **Deep links from push** go through the router guard since 2026-11-20 (`AppRouter.onGenerateRoute`, `RequireSignIn`).

## 2. Gaps

### 2.1 Events that should send something but don't

| Group | Missing |
|---|---|
| A Security | New-device sign-in, password reset completed (API reset revokes sessions but sends no notice), 2FA set up or reset (staff), recovery codes viewed or used, account locked after wrong codes, admin step-up code (staff use TOTP, so no message is needed; confirm) |
| B Account | Welcome after phone sign-up or email sign-up (only instructors and SMS registrations get one), workspace created, member added or removed, role changed |
| C Verification | Documents received (only `in_review` exists), Tier 3 granted or revoked, suspended or unsuspended |
| D Marketplace | Lot published or paused, request (RFQ) posted to matching sellers, quote expired, new trade-chat message by email (push only today) |
| E Deals | Deal room opened, contract signed, milestone reached or confirmed, payment slip requested or confirmed, dispute opened or resolved, report received (to the reporter) |
| F Payments | Receipt for events, tours, plans and samples; payment failed; refund; bank slip approved or rejected; payout status |
| G Academy | Certificate issued (with verify link), course completed, lesson reminder |
| H Events and tours | Booking confirmed, reminders 24 h and 1 h, schedule changed, cancelled (incl. `bookings:release-unpaid` cancelling unpaid tours), balance due |
| I Market | Price alerts, digest, "source unavailable" admin notice |
| J System | Account deletion approved or rejected, data export ready, policy update |
| K Marketing | Unsubscribe link and header, consent timestamps, opt-in check for push broadcasts |

### 2.2 No Amharic

All 11 email templates, all 6 SMS templates, `LiveClassNotification`, `VerificationAdminAlert` (admin, English is fine), and the in-app inbox renderer (stores Amharic, shows English).

### 2.3 Data that leaks

| Where | What | Why it matters |
|---|---|---|
| `TradeUpdate` push body | Counterparty business names ("{buyer} requested a sample of {lot}"), lot titles | Visible on the lock screen |
| `VerificationUpdate` push body | Rejection reason and document name | Visible on the lock screen |
| `payment_confirmation` SMS | Amount, item and payment reference | SMS previews show on the lock screen |
| `LiveClassNotification` mail and push | Class title and time are fine, but the email links are built by hand | Check that links stay on the official domain |
| `AdminNewRegistrationMail` subject | The person's name in the subject | Admin only, low risk |
| `EventBookingBulkMail`, `GeneralNotification`, `AdminNewsletterBlast` | Free text from instructors or admins, with no check for codes or links to other domains | Phishing vector if an account is taken over |
| OTP email | The code is in the body only (good); preheader is missing, so clients may show the first line | Needs a safe preheader |

The OTP subject is safe: no code in the subject anywhere.

### 2.4 Inline raw HTML and duplicated templates

- 10 of 11 email views are standalone `<!doctype html>` documents with 15 to 18 inline `style=""` attributes each.
  Header, footer and button are copied 10 times. There's no logo image in any of them.
- `contact/submitted` uses Laravel's default `mail::message` look: a third design.
- The web password reset uses Laravel's default notification look.
- The three PDFs each have their own design.
- No Mailable has a plain-text part (`text()` view), so they are HTML only.

### 2.5 Other findings

- **The OTP email states the wrong expiry.** `login-otp.blade.php:34` uses `$expiresAt->diffInMinutes(now())`.
  With Carbon 3 this is negative and fractional, for example "−9.98 minutes". Needs a test to confirm.
- **Copy:** exclamation marks and hype in the SMS texts ("Congratulations …!", "Thank you for choosing …!"),
  and leading spaces in the live-class subjects.
- **Sender:** `MAIL_FROM_NAME="Fira Link Academy"`, one from-address for everything, `MAIL_MAILER=log` locally (no
  Mailpit). SPF, DKIM and DMARC are not documented.
- **Preferences:** `user_notification_settings` has email, course reminder, progress report and marketing
  flags. `NotificationPreferenceService::prefers()` is used for enrollment and review mails only. Push and
  in-app messages ignore preferences, and there are no per-class or per-channel switches and no quiet hours.
- **No queue:** only `GeneralNotification` imports `ShouldQueue` and it doesn't implement it. A failed mail
  server raises inside the request. Some callers catch the error, others don't.
- **Badges:** no template prints "Verified" from a caller variable today. `VerificationUpdate` texts
  come from the event key, and the tier comes from `TierService`. The new rule should be locked in by a test.

## 3. Architecture, adapted to what exists

| Piece | Build | Reuse or replace |
|---|---|---|
| **Catalogue** | `App\Notifications\Catalog\NotificationType` (PHP enum). Per case: key, class (A security, B transactional, C product update, D marketing), allowed channels, default channels, `canDisable`, priority, TTL, deep-link route name (app route + web route), auth level (guest, signed in, staff), translation key prefix, Android channel id | New. Every existing event key (`verification.*`, `trade.*`, `lots.*`, `security.*`) becomes a case |
| **One Notification class** | `App\Notifications\FiraNotification(NotificationType $type, array $params, ?string $idempotencyKey)`. `via()` = catalogue defaults ∩ user preferences (class A ignores opt-outs). `toMail`, `toArray` (stores **key and params, not localised text**), `toFcm` (lock-screen-safe title and body from `*.push_*` keys), `toSms`. `ShouldQueue`, except OTP | Replaces the 7 classes and 11 Mailables one group at a time. The old classes stay until their group is converted |
| **Channels** | mail, database, FCM (existing `FcmChannel`), SMS (new `SmsChannel` wrapping `SmsEthiopiaService::send`), Telegram later | Direct `Mail::to()->send` and `SmsEthiopiaService::send*` calls outside the channels are removed. A test scans for them |
| **OTP** | `OtpService` keeps generating and checking codes (already correct). The code is sent **synchronously** through `SmsChannel` and the mail channel with its own limiter (`otp-send` per phone and per IP), one template per purpose | `LoginOtpMail` becomes 5 catalogue types: sign-in, sign-up, password reset, step-up, email change |
| **Idempotency** | `notification_deliveries.idempotency_key` (unique): type, notifiable and subject id or event id. The send is skipped when the key exists with status `sent` | New |
| **Delivery log** | `notification_deliveries`: type, channel, notifiable, provider id, status (queued, sent, failed, skipped_pref, skipped_quiet), attempts, error code, timestamps. **No bodies, no codes, no full phone numbers or emails** | New. `sms_logs` stays for provider details, linked by id |
| **Preferences** | `notification_preferences` (user, class, channel, enabled, consented_at, source) + quiet hours on the user. The old `user_notification_settings` flags are mapped into it once | Extends the existing table and service |
| **Email layout** | `resources/views/mail/layout.blade.php` + `<x-mail.*>` components (header, eyebrow, heading, paragraph, button, fact-list, code-box, status-pill, notice, divider, footer). Table-based, 600 px, CSS inlined at render. Laravel's mail pipeline already inlines the markdown theme CSS through `CssToInlineStyles`, so Blade components run through the same inliner. A text view is generated from the same data | Replaces the 10 standalone HTML files, the default markdown look and the default reset email |
| **Translations** | `resources/lang/{en,am}/messages.php`: one key set per type (`subject`, `preheader`, `eyebrow`, `heading`, `body`, `cta`, `push_title`, `push_body`, `sms`). A missing Amharic key falls back to English and is logged once per key | New file; the existing `verification.notifications.*`, `trade.notifications.*`, `lots.notifications.*` and `security.notice.*` move into it |
| **In-app** | The row stores `type` key + safe params. The app renders `l10n` text at display time (locale switch works on old rows). The API also returns resolved `title` and `message` in the user's locale for old app versions | Changes `notification.dart` and `notifications_screen.dart` |
| **Push** | Lock-screen copy from `push_title` and `push_body` (generic: "Update on your sample"). Data payload: type, route name, id only. Android channels: `security`, `deals`, `learning`, `updates`. iOS `thread-id` = class | Replaces `high_importance_channel` |
| **Badges** | Templates get `BusinessSummary`, whose tier comes from `TierService`. Never a caller `verified` flag. A test fails if a template contains "Verified" outside a `@if($business['verification_tier'] >= 1)` block fed by `BusinessSummary` | — |
| **Preview tooling** | `GET /admin/messages/preview/{type}?locale=am&channel=mail` (staff 2FA, 404 in production for non-admins) + `php artisan messages:preview {type} --locale=am`. Output: email HTML, plain text, SMS text with GSM-7/UCS-2 segment count, push preview, in-app row | New |

## 4. Slice plan (each slice is one commit; no push; I stop after each)

| # | Slice | Contents | Tests |
|---|---|---|---|
| 1 | Catalogue, delivery log, preferences | `NotificationType` enum with **all** types A to K (keys, class, channels, routes); `notification_deliveries` and `notification_preferences` migrations and models; `FiraNotification` base with `via()` from catalogue + preferences; `SmsChannel`; idempotency check; mapping of old settings flags | Every type has a class, channels and route; class A ignores opt-out; idempotent resend sends once; no body or code stored in the log |
| 2 | Email layout, components, preview + 3 emails | Layout, `<x-mail.*>`, inliner step, text part, preview route and command; convert **OTP (5 purposes), password reset (web + API), welcome**; fix the expiry bug; Mailpit in `docker-compose`/docs; SPF/DKIM/DMARC doc | en + am render, no missing keys, subject ≤ 50 chars, no code outside code-box, official links only, alt text, text part present. **Screenshots** at 600 and 375 px (headless browser) |
| 3 | SMS OTP rewrite | `messages.sms.*` en + am per purpose, synchronous send, `otp-send` limiter, the DB templates become read-only display copies (or are retired); segment counter helper | GSM-7 ≤ 160, Amharic UCS-2 ≤ 70 per segment (OTP ≤ 2), no link in OTP SMS, no code in `sms_logs.message_preview` |
| 4 | Push + in-app inbox + deep links | Lock-screen copy, data payload, Android channels, iOS threads; inbox renders by key in the app locale, grouped by day, mark all read, empty state; tap → route → `RequireSignIn` | Flutter: renders en + am; tap as guest → sign-in → target. Server: push body has no names, amounts or reasons |
| 5a–5k | Convert by group, one commit per group A to K | Move every existing event to `FiraNotification`; add the missing events from 2.1; delete the old Mailables and views | Per group: render en + am, routes guarded, preferences respected (except A) |
| 6 | Settings + unsubscribe | Flutter settings per class and channel, quiet hours; web one-tap unsubscribe (signed URL) for C and D; `List-Unsubscribe` + `List-Unsubscribe-Post`; consent timestamps; newsletter sent per recipient (queued), opt-in only | Opt-out stops C/D, cannot stop A; unsubscribe link signed and expiring; header present |
| 7 | PDF receipt + certificate in the same style | Shared PDF layout (dompdf-safe CSS), payment receipt (new) for courses, events, tours, plans and samples; certificate, ticket and offer restyled | PDF renders en + am (font with Ethiopic glyphs embedded), amounts with currency code, Addis Ababa time |

## 5. Conflicts and decisions I need from you

| # | Question | My recommendation |
|---|---|---|
| D-1 | Your token list has **hairline `#E9E4F0`**, which exists in neither the app nor the web code. `#F9F7FA`, `#1E1B4B`, `#F3E8FF` and `#3B0764` exist only in the app theme. CLAUDE.md says "never add new colours". | Treat your list as the **email/SMS/PDF brand sheet** and use it there only. Confirm `#E9E4F0` is allowed (or use the app's existing hairline). |
| D-2 | Radii: you say button 12; CLAUDE.md says app input and button 16, web `--fira-radius` 6. | Email and PDF: card 16, button 12 as you wrote. App and web stay as they are. |
| D-3 | Fonts in email: "the site font" is Inter on the web; the app uses Montserrat. | Email stack: `Inter, Arial, "Noto Sans Ethiopic", sans-serif` (no web-font download). |
| D-4 | Logo: "white mark on purple gradient". The repo has `logo.png`, `fira_box_logo.png`, `fira_header_logo.png`, `logo-dark.png` and SVGs; I don't know which is the supplied mark. Emails need a hosted PNG @2x on the official domain. | Send me the exact files (mark 80×80 @2x, wordmark @2x), or tell me which repo file. I will not draw or recolour it. |
| D-5 | From addresses: `security@`, `no-reply@`, `support@`, and the sender name "Fira Link". Today it's "Fira Link Academy" and one address. These must exist and be authorised in SPF/DKIM. | Confirm the domain and that the three mailboxes exist; I document DNS records, you add them. |
| D-6 | Amharic copy: I can write good Amharic drafts, but you asked for a native-quality pass. | I write all am keys; you (or a translator) review a generated sheet before slice 5 is merged. |
| D-7 | Old SMS templates are editable by admins in the database today. A fixed en/am catalogue conflicts with that. | Catalogue texts are the source; admin page becomes read-only preview with segment count. |
| D-8 | Telegram/WhatsApp: no code exists. | Out of scope until you have a provider; the catalogue keeps a `telegram` channel slot. |
| D-9 | Mailpit and visual snapshots need Docker/Node tooling locally. | Mailpit via docker-compose (optional), snapshots via Playwright in a dev-only script, not in `php artisan test`. |
| D-10 | Web email verification and the web password reset are on the website, which CLAUDE.md limits to verify and admin pages. | Convert their **emails** (server side) in slice 2; don't touch the web pages. |
