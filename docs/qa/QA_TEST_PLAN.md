# Fira Link launch QA plan

For the QA team testing Fira Link before launch: the mobile app (Android and iOS), the API, and the
web pages that ship now (public seller page, admin review screens, admin payments).

Two parts:

1. **Automated run** (`qa/fira_qa.py`): drives the real server over HTTP and checks sign-in, roles,
   forgot password, trade, payments, events and tours. Run it on every new build of the server.
2. **Manual cases** (section 5): what the script cannot see — the app screens, Amharic, camera and
   files, notifications, real SMS and email.

Report every failure with the template in section 7.

---

## 1. Where to test

| Environment | Use | QA tools |
|---|---|---|
| Local (`APP_ENV=local`) | Developers | On. With `AUTH_TEST_OTP_CODE` set, every SMS code is that fixed code. |
| Staging (`APP_ENV=staging`) | QA team | On only with `QA_TOOLS_ENABLED=true` in the staging `.env`. |
| Production | Real users | **Never.** Every `qa:*` command refuses to run. Do not test with real money or real documents. |

Staging must use its own database, its own storage and **test payment keys**. Never point staging at
the production database.

## 2. Test accounts

The server makes them; nobody types them by hand.

```bash
# On the staging server (or locally):
php artisan qa:seed --password='Choose-A-Strong-QA-Pass1'
```

This prints one JSON line (`QA_JSON:…`) with every account. All emails end in `@qa.firalink.test`
and look like `<run>.<name>@qa.firalink.test`. The password is the one you gave (or a random one, shown
once). Keep the line private; nothing else stores it.

| Account (`<name>`) | System role | Business | Can do |
|---|---|---|---|
| `student` | student | — | Academy, events, tours, personal profile |
| `instructor` | instructor (approved) | — | Instructor area and API |
| `instructor_pending` | instructor (not approved yet) | — | Sees "Your account is pending approval" |
| `admin` | admin (+ authenticator app) | — | Admin dashboard, review queue, bank slips, event and tour details |
| `reviewer` | student + reviewer (+ authenticator app) | — | Review queue only (not the admin dashboard) |
| `finance` | student + finance | — | Confirms deal room steps after the bank |
| `exporter_owner` | student | QA Exporter PLC (Tier 1), **owner** | Everything for the business, payout bank account |
| `exporter_manager` | student | same, **manager** | Lots, quotes, documents; not the payout account |
| `exporter_staff` | student | same, **staff** | Views only; no documents, no payout account |
| `buyer_owner` | student | QA Roasters GmbH (Tier 1, buyer), owner | Requests, quotes, samples, deal rooms |
| `unverified_owner` | student | QA New Exporter (not verified), owner | Shows the "hidden from buyers" states |

Staff sign in to admin with an authenticator app. The JSON has `staff_totp_secrets` for `admin` and
`reviewer`: add the secret to Google Authenticator or 1Password ("enter a setup key", time based, 6
digits) to get the codes.

The seed also makes a published lot, a hidden lot (unverified seller), an event (General 500, Member
300, service fee 25, agenda, refund policy) and a farm tour (two dates, 30% deposit, balance 14 days
before) — all named `QA …`.

**Codes in SMS and email.** On staging, real SMS and email go out. QA accounts use addresses that
cannot receive mail, so get the code from the server instead, right after the app asks for it:

```bash
php artisan qa:code <email | phone | user id> <purpose>
# purposes: login, password_reset, email_signup, phone_signin, registration, step_up
```

It only works for QA accounts (or phones listed in `QA_PHONES`) and only for a code the app just
asked for. For phone sign-in on a real SIM you own, add the number to `QA_PHONES` in the staging
`.env` and read the real SMS.

**Bank slips.** A slip stays "In review" until finance confirms it. Confirm it on the web admin
(Payments → Offline pending, signed in as `admin`) or with `php artisan qa:confirm-payment <payment id>`
(`--reject="reason"` to reject).

**Clean up** when a test round ends:

```bash
php artisan qa:cleanup --run=<run> --dry-run   # counts only
php artisan qa:cleanup --run=<run>             # or --all for every QA run
```

It removes only QA accounts and what they made. Audit log entries stay.

## 3. Automated run

Needs Python 3.10+ (no extra packages) and access to `php artisan` on the server.

```bash
# Local server:
python qa/fira_qa.py --base-url http://127.0.0.1:8000

# Staging, artisan over ssh:
python qa/fira_qa.py --base-url https://staging.firalink.example \
  --artisan "ssh deploy@staging.firalink.example cd /var/www/firalink && php artisan"
```

Options:

| Option | Meaning |
|---|---|
| `--suites health,auth,roles,web,trade,events_tours` | Run only some parts (default: all). |
| `--phone +2519XXXXXXXX` | Also test phone sign-in with a SIM you own (must be in `QA_PHONES`). |
| `--keep` | Keep the QA accounts afterwards for manual testing (prints the run name). |
| `--password …` | Use a known password for the QA accounts (handy with `--keep`). |

It waits on its own to stay under the sign-in rate limits (10 per minute on the API, 6 on the web), so
a full run takes several minutes. It writes `qa/reports/qa-report-<date>.md` and exits with code 1 if
anything failed (use it in CI). It never prints passwords or tokens.

What it covers:

| Suite | Checks |
|---|---|
| health | API up; web `/`, `/login`, `/forgot-password`, `/events`, `/tours`; public seller page and API by Fira ID |
| auth | Wrong password refused; unknown email gets the same answer; sign in for each role; `/api/user` role; sign out ends the token; email sign-up with a code (wrong code refused); weak password refused; forgot password end to end (same answer for unknown email, code, ticket used once, old password stops working, other devices signed out); step-up; phone sign-in (with `--phone`) |
| roles | Student blocked from the instructor API, approved instructor allowed, pending instructor told it is pending; business manager cannot change the payout account; staff cannot see it; owner sees it masked; manager manages documents, staff only views; seller home only for seller businesses |
| web | Admin needs sign-in; wrong password; student signs in but cannot open admin; admin and reviewer need the authenticator code; admin pages load (dashboard, review queue, reports, payout accounts, bank slips, event and tour details); reviewer cannot open the dashboard; web and instructor forgot password give the same answer for unknown emails; sign out |
| trade | Verified lot listed, unverified lot hidden; message seller; flagged message with warning; chat shows businesses only; report + block; blocked business cannot message; unblock; free sample; deal room opened, both sign, step 2 of 6, pay-to masked, later step locked, deposit slip sent |
| events_tours | Organizer verified; fake slip refused; ticket In review with no QR; finance confirms, QR appears; tour needs sign-in; 30% deposit and balance; balance locked until the deposit is confirmed; balance paid |

A FAIL line shows what came back. Copy it into the bug report.

## 4. Roles: who can do what

Use this to check that each account sees only what it should. "—" means it must be refused or hidden.

| Action | student | instructor | admin | reviewer | finance | owner | manager | staff | buyer owner |
|---|---|---|---|---|---|---|---|---|---|
| Courses, events, tours | yes | yes | yes | yes | yes | yes | yes | yes | yes |
| Instructor area | — | yes | — | — | — | — | — | — | — |
| Admin dashboard (web) | — | — | yes (+code) | — | — | — | — | — | — |
| Review queue (web) | — | — | yes (+code) | yes (+code) | — | — | — | — | — |
| Bank slips: confirm | — | — | yes | — | — | — | — | — | — |
| Deal room step: confirm with bank | — | — | yes | — | yes | — | — | — | — |
| Upload verification documents | — | — | — | — | — | yes | yes | — | yes |
| Payout bank account: change | — | — | — | — | — | yes (confirm it is you) | — | — | — |
| Payout bank account: see (masked) | — | — | — | — | — | yes | yes | — | — |
| Create and publish lots | — | — | — | — | — | yes | yes | — | — |
| Quote on requests | — | — | — | — | — | yes (Tier 1) | yes (Tier 1) | — | — |
| Post requests, ask samples | — | — | — | — | — | — | — | — | yes (Tier 1) |
| Block a business | — | — | — | — | — | yes | yes | — | yes |

Admins and reviewers never approve their own business (they get "You cannot review your own
business").

## 5. Manual cases

Priority: **P1** blocks launch, **P2** must be fixed soon after, **P3** cosmetic.
Run every P1 case on one Android phone and one iPhone, in **English and in Amharic**, at normal and
at large text size.

### 5.1 Sign in and accounts (P1)

| # | Steps | Expected |
|---|---|---|
| A1 | Open the app fresh. | Splash, then the language picker (English / አማርኛ), then onboarding. |
| A2 | Sign in with phone → enter a QA_PHONES number → code. | Code screen with masked number and a resend timer; right code signs in; wrong code shows "wrong code"; too many wrong codes lock sign-in for a while (the limit is set in admin SMS settings). |
| A3 | Sign in with email and password (QA `student`). | Home opens. Wrong password: one message for wrong password and unknown email. |
| A4 | Create an account with email: name, email, password, terms. | Code by email (use `qa:code <email> email_signup`), then signed in. Weak password (no capital or digit, under 8) is refused with a clear message. |
| A5 | Forgot password → email → code → new password. | Same message whether the email exists or not. After saving, the old password fails and other devices are signed out. |
| A6 | Forgot password by phone. | Code by SMS to the verified phone; same flow as A5. |
| A7 | Sign out, then use the back button. | Back does not reopen signed-in screens. |
| A8 | Change phone or email in Profile. | Asks to confirm it is you (code or password) first. |

### 5.2 Workspaces and verification (P1)

| # | Steps | Expected |
|---|---|---|
| W1 | Profile → workspace switcher → Add a business: name, role, entity type, TIN. | Business created and active; the verification screen opens next. Ethiopian TIN must be 10 digits. |
| W2 | Profile shows the Verification row. | "Tier 1 · n documents left" with the status pill (In review in **purple**, never orange). |
| W3 | Upload each Tier 1 document with the camera and from files. | Blurry, empty or tiny files are refused with a reason; a real document goes to "In review". |
| W4 | As admin (web), approve the documents in the review queue. | Business becomes Tier 1; the app shows Verified; the public page `/verify/<Fira ID>` shows it. |
| W5 | Request changes on one document. | The owner sees the reason and can upload again. |
| W6 | As `exporter_staff`, try to upload a document. | Not allowed. |
| W7 | Switch between Personal and a business. | Home, Market and Profile change; courses and certificates stay in Personal. |

### 5.3 Selling (P1)

| # | Steps | Expected |
|---|---|---|
| S1 | As `unverified_owner`, create a lot. | Draft saves; Seller home says "Your lots are hidden from buyers" until Tier 1. |
| S2 | As `exporter_owner`, create a lot with photos (camera) and publish. | Lot code shown; lot visible in the market to buyers. |
| S3 | Add evidence photos and a lab report. | Lot level goes from Claimed to Documented / Lab-backed only after admin review. |
| S4 | Seller home. | Counts (active lots, open requests, samples to send) and the to-do list. |
| S5 | Payout bank account: add (owner). | Asks to confirm it is you; shows "ending 1234", "Waiting for Fira Link to confirm". Change it: 48 hour pause warning. |

### 5.4 Buying, quotes and samples (P1)

| # | Steps | Expected |
|---|---|---|
| B1 | As `buyer_owner`, post a request (RFQ). | Request listed under My requests. |
| B2 | As `exporter_owner`, answer the request with a lot. | Phone numbers, emails or links in the note are refused. Below the ECTA minimum price: shown and cannot be accepted. |
| B3 | Buyer compares quotes and accepts one. | Others marked not selected; "Create deal room" appears. |
| B4 | Request a sample of a lot. | Delivery details and courier account; free or paid. Paid samples say payments are not available yet (unless the mock is on). |
| B5 | Seller seals and ships: seal ID, two photos, tracking number. | Buyer sees tracking and the seal; confirms delivery and cups; approved seal ID shows in the deal room. |

### 5.5 Deal room, payments, chat, reports (P1)

| # | Steps | Expected |
|---|---|---|
| D1 | Open the deal room; both sides sign. | "Step 2 of 6 · Waiting for deposit"; both sides shown as businesses, never a person's name or phone. |
| D2 | Payment step: Pay to. | Bank and "account ending ####", "Matches the seller's verified bank account" only after a reviewer confirmed it. |
| D3 | Upload the deposit slip. | "Submitted"; the next steps stay locked until finance confirms with the bank. |
| D4 | Finance confirms the step (admin). | Step done; inspection opens for the seller. |
| D5 | Seller changes the payout account during a deal. | Buyer sees "Payments are paused until …"; slip upload is blocked. |
| D6 | In the deal chat, the seller sends "WhatsApp +251…". | The buyer sees the warning box with "Keep chatting in Fira Link" and "Report this message". |
| D7 | Report the message with a screenshot and "Also block". | Sent; the seller can no longer message, quote or send samples to the buyer. The seller is never told who reported. |
| D8 | Admin suspends the reported business (web, Reports tab). | Its open deal rooms show "paused"; unsuspend resumes them. |
| D9 | Profile → Deals and payments. | Every deal room is listed with its step. |

### 5.6 Events, tours, academy (P1)

| # | Steps | Expected |
|---|---|---|
| E1 | Events: Upcoming / Online / My tickets and the categories. | Lists change; cards show "Organizer verified" only for a verified organizer. |
| E2 | Register: General ticket, 2 tickets, bank transfer with a slip. | Total includes the service fee; the ticket shows "In review" in purple, no QR. |
| E3 | Admin confirms the slip (web, Payments → Offline pending). | The ticket shows Confirmed with the QR code. |
| E4 | Register a Member ticket as a student without any course. | Member ticket is disabled ("For Academy members"). |
| E5 | Tours: region chips, a tour. | Host verified, not included, cancellation, safety, "Never pay the host directly", next dates with places left. |
| E6 | Book: date, 2 guests, bank slip. | Pay now 30%, balance due date; after finance confirms: "You are booked", "Pay the balance". |
| E7 | Pay the balance with a slip. | In review, then paid after finance confirms. |
| E8 | Chapa payment (test keys on staging only). | Checkout opens; after paying, the ticket or booking is confirmed only when the server confirms with Chapa. |
| E9 | Academy: open a course, play a lesson, Mark complete. | "Lesson n of m", progress % rises, up next updates; Ask Fira AI opens. |

### 5.7 Admin web (P1)

Sign in at `/login` as `admin`, then the authenticator code.

| # | Steps | Expected |
|---|---|---|
| M1 | Open admin without the code. | Sent to the authenticator page. |
| M2 | Review queue tabs: Business, Lots, Reports, Flagged messages, Payout accounts. | Each loads; counts match. |
| M3 | Open a document page link after 10 minutes. | The link has expired (private files use 10 minute signed links). |
| M4 | Payout accounts: confirm a change without ticking "called the number on file". | Refused. |
| M5 | Event and tour pages: App details form; link the organizer by Fira ID. | Unknown Fira ID refused; badge appears in the app. |
| M6 | Sign in as `reviewer`. | Review queue yes, admin dashboard no. |

### 5.8 Security spot checks (P1)

| # | Steps | Expected |
|---|---|---|
| X1 | Open a receipt or document URL from the browser history after signing out. | Refused or expired. |
| X2 | Upload an empty file or a renamed text file as a document or slip. | Refused ("file is empty / not a real image or PDF"). |
| X3 | Ask the API for another user's ticket or booking id. | 403 or 404. |
| X4 | Try 11 sign-ins in one minute. | Rate limited (429), then works again after a minute. |
| X5 | Look at any screen showing the other trading party. | Business name and tier only — no person's name, email or phone (except a sample's delivery contact for its seller). |

### 5.9 Language and layout (P2)

- Every screen in Amharic: no English left, no text cut off, no overflow at 390 pt width.
- Large text size (accessibility): buttons still reachable.
- Dark mode: text readable.

## 6. Go / no-go for launch

Launch only when every item is checked.

- [ ] Automated run on staging: **0 FAIL** (report attached).
- [ ] All P1 manual cases pass on Android and iPhone, English and Amharic.
- [ ] No open P1 bugs.
- [ ] Leaked secrets rotated (CLAUDE.md, "Secrets and rotation"): Chapa keys, Gemini key, admin and server passwords; `APP_KEY` last.
- [ ] `ID_HASH_KEY`, `VERIFICATION_ADMIN_EMAIL`, `SMS_ETHIOPIA_API_KEY` set in production; `AUTH_TEST_OTP_CODE` **not** set; `QA_TOOLS_ENABLED` **not** set; `SAMPLE_PAYMENTS_MOCK` off.
- [ ] One-time production steps 1–13 in CLAUDE.md run in order, dry runs read first, database backup taken.
- [ ] Scheduler running (`* * * * * php artisan schedule:run`).
- [ ] PHP `gd` and `exif` installed on the server.
- [ ] Every admin and reviewer has an authenticator app and saved recovery codes.
- [ ] Mail sending works (send yourself a password reset).
- [ ] A real SMS sign-in works on a real SIM.
- [ ] `php artisan qa:cleanup --all` run on staging; no QA data in production (QA tools refuse to run there).

## 7. Bug report template

```
Title: [area] short description            (e.g. [Deal room] slip upload allowed while paused)
Priority: P1 / P2 / P3
Build: app version + server commit        Device: model, OS, language (en / am)
Account: <run>.<name>@qa.firalink.test (never paste the password)
Steps:
1.
2.
Expected:
Actual:
Evidence: screenshot or screen recording; FAIL line from the QA report if any
```
