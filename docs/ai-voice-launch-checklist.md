# Fira AI Voice & Real-Time Price Pipeline Launch Checklist

This document details the production operational readiness, security assurances, configuration profiles, and launch gates for the Fira AI Voice integration and Real-Time Coffee Price display system on Fira Link (web and Flutter mobile).

---

## 1. Core Architecture & Constraints

- **Single Master Key on Server:**
  - `GEMINI_API_KEY` resides strictly on the server (`.env`) and is never embedded in client binaries, JS bundles, or app builds.
  - Clients receive single-use, short-lived **ephemeral tokens** with a 2-minute provisioning window (`token_expires_at`).
  - Ephemeral tokens are minted via Google's OAuth2 / Generative AI endpoint and locked to `models/gemini-2.0-flash`.
- **Server-Side System Instructions & Tool Registry:**
  - Client connections to the Gemini Live WebSocket (`BidiGenerateContentConstrained`) cannot override the server's locked system prompt or inject unauthorized tools.
  - Tool calls are routed back to the authenticated server endpoint `POST /api/ai/voice/tool`, validated against schemas, authenticated via Sanctum/Session, and restricted to the caller's profile.
- **Brand Identity & Attributions:**
  - Assistant is branded exclusively as **Fira AI**.
  - All occurrences of third-party model names ("Gemini", "Google") are eliminated from user-facing screens, buttons, sounds, notifications, and metadata.
  - Legally required disclosures of AI subprocessors are maintained in the privacy policy for legal counsel review.

---

## 2. Voice Tool Safety & Schema Guarantees

| Tool Name | Scope & Authority | Schema & Parameter Limits | Display Card Type | Speech Output Guarantees |
|---|---|---|---|---|
| `get_market_snapshot` | Read-only | Array of symbols (`symbols[]`) | `market_snapshot` | Includes unit, status phrase ("daily close", "schedule", "stale"), as-of timestamp. |
| `get_ecta_schedule` | Read-only | `origin`, `grade`, `processing` | `ecta_schedule` | Reads official regulatory minimum price per lb and effective date. |
| `run_profit_calculator` | Computational | `bags` (1..10000), `fob_price_usd` (0.01..50), `purchase_price_etb` | `calculator_result` | Outputs gross revenue (USD), estimated net profit (ETB), margin %, break-even price. |
| `search_lots` | Read-only | `origin`, `grade`, `limit` (1..10) | `lot_list` | Only returns active lots; quotes indicative price with seller company. |
| `get_my_verification_status` | User-Scoped | None (bound to `Auth::user()`) | `verification_status` | Strictly reflects authenticated seller status; prevents cross-user sniffing. |
| `search_help` | Read-only | `query` string (max 120 chars) | `help_card` | FAQ and trade desk contact guidance. |
| `search_academy` | Read-only | `query` string (max 120 chars) | `academy_courses` | Recommends relevant coffee export logistics and trade training. |

> **Write Action Constraint:** No financial contracts, RFQ creation, or fund transfers are permitted via voice. Write tools are deliberately excluded from the voice tool dispatcher.

---

## 3. Real-Time Price Pipeline & Honest Freshness Policy

- **No AI Price Generation:** Prices shown on web and Flutter, as well as prices spoken by Fira AI, originate strictly from the PostgreSQL/MySQL market database (`coffee_market_instruments`, `coffee_market_quotes`).
- **Honest Status Rules:**
  - `Live`: Reserved exclusively for actively updating licensed streaming feeds within the freshness window (currently 0 instruments; placeholder for future licensed feed).
  - `Daily close`: Official daily composite indicator from the International Coffee Organization (ICO) or National Bank of Ethiopia (NBE) exchange rate.
  - `Schedule`: Official minimum FOB price matrix from the Ethiopian Coffee & Tea Authority (ECTA).
  - `Stale`: Automatically applied if quotes exceed `stale_after_hours` (48 hours for daily benchmarks).
  - `Unavailable`: Shown before initial data seed or if feed is discontinued.
- **Refresh Intervals:**
  - Web & App poll or receive SSE updates every 15 minutes for daily benchmarks.
  - Polling every 60 seconds is enabled only if an instrument is flagged `is_realtime_feed = true`.

---

## 4. Multi-Language Gate Matrix

| Language | Code | Voice Status | Fallback Action | Notes |
|---|---|---|---|---|
| **English** | `en` | **Enabled** | N/A | Full bilingual voice orb support with real-time speech synthesis. |
| **Amharic** | `am` | **Enabled** | N/A | Full Amharic audio capture, text-to-speech, and localized tool cards (`lang_am.dart`). |
| **Afan Oromo** | `om` | **Blocked** | **Text Chat Fallback Card** | Failed language gate spike (<90% understanding). Clean client-side & server-side intercept renders localized fallback notice card offering 1-tap jump to text chat or English/Amharic voice. |

---

## 5. Security, Rate Limits & Concurrency

- **Session Rate Limits:**
  - Maximum concurrent sessions: **1 active session per user**. New session requests gracefully terminate existing sessions.
  - Daily usage cap: **15 minutes (900 seconds)** per user account per calendar day.
  - Ephemeral token TTL: **120 seconds** to establish WebSocket connection; session terminates when quota expires.
- **Tool Rate Limiting:**
  - `POST /api/ai/voice/tool` throttled at 60 calls/minute per user.
- **Data Retention & Privacy:**
  - Raw audio buffers are processed in-memory and **never written to disk or logged**.
  - Transcripts are recorded only if user explicitly consents to session history in user preferences.

---

## 6. Pre-Launch Operations Checklist

- [x] Run database migrations: `php artisan migrate` (ensures `ai_voice_sessions`, `ai_voice_quotas`, `ai_voice_consents` tables exist).
- [x] Validate `.env` variables:
  - `GEMINI_API_KEY` set and verified in server environment.
  - `SERVICES_GEMINI_LIVE_MODEL=models/gemini-2.0-flash`
  - `SERVICES_GEMINI_VOICE_DAILY_SECONDS=900`
- [x] Verify mobile permissions:
  - Android: `android.permission.RECORD_AUDIO` and `MODIFY_AUDIO_SETTINGS` in `AndroidManifest.xml`.
  - iOS: `NSMicrophoneUsageDescription` in `ios/Runner/Info.plist`.
- [x] Execute automated test suite:
  - `php artisan test --filter Voice`
  - `php artisan test --filter RouteInventoryTest`
  - `flutter test test/fira_voice_test.dart`
  - `flutter test test/localization_test.dart`
- [x] Verify zero unbranded/leaked model references:
  - Confirmed: No instances of "Google Gemini" or "Powered by Gemini" in client views or translation files.
