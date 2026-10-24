# Remediation Roadmap & Fix Prioritization Plan
**Project:** Fira Link Academy (Eduex LMS & Flutter App)  
**Target Release:** Production Staging & Live Launch  
**Standard:** OWASP ASVS L2 / INSA Guidelines  

---

## 1. Roadmap Overview

The remediation plan organizes the 17 identified security vulnerabilities into three structured, non-disruptive execution phases:
- **Phase 1: Fix Now (Pre-Condition for Any Network Exposure)**  
  Must be completely implemented and verified before the application is connected to any public staging server or production environment.
- **Phase 2: Fix Before Public Launch (Go-Live Gate)**  
  Required prior to commercial onboarding, accepting real payments via Chapa, or releasing the Flutter mobile app to app stores.
- **Phase 3: Post-Launch & Ongoing Hardening (Defense-in-Depth)**  
  Scheduled sprint items to strengthen monitoring, compliance posture, and resilience.

---

## 2. Phase 1: Fix Now (Blockers for Internet Exposure)

| Finding ID | Title | Component | Estimated Effort | Commit Strategy |
| :--- | :--- | :--- | :---: | :--- |
| **FL-CRIT-01** | Arbitrary File Read in `/storage/{path}` | Backend (`routes/web.php`) | 30 mins | Single isolated commit + test script proving path traversal (`../../.env`) returns 404 |
| **FL-CRIT-02** | Unauthenticated File Read in `/api/video/stream` | Backend (`FileController.php`) | 30 mins | Single isolated commit + API regression test |
| **FL-CRIT-03** | Global Disablement of TLS Verification (`CURLOPT_SSL_VERIFYPEER`) | Backend (Payments & SMS services) | 2–3 hours | Systematic refactor across all 35+ cURL instances |
| **FL-CRIT-04** | Plaintext Merchant Secret Key Leaked in SSLCommerz Response | Backend (`CourseEnrollmentController.php`) | 30 mins | Remove `'store_password'` property from response JSON |
| **FL-HIGH-01** | Missing `role:instructor` Middleware on Instructor API Group | Backend (`routes/api.php`) | 15 mins | Update route group definition in `routes/api.php` |
| **FL-MED-03** | Rotate & Revoke Exposed API Keys in `.env` | Environment & Cloud Consoles | 30 mins | Rotate Gemini and Claude keys; set `APP_DEBUG=false` |

### Total Effort for Phase 1: ~4.5 hours

---

## 3. Phase 2: Fix Before Public Launch (Go-Live Gate)

| Finding ID | Title | Component | Estimated Effort | Commit Strategy |
| :--- | :--- | :--- | :---: | :--- |
| **FL-HIGH-02** | BOLA / IDOR & Unmasked Phone Numbers in Student Profile API | Backend (`StudentsController.php`) | 30 mins | Enforce enrollment ownership check & mask phone |
| **FL-HIGH-03** | Stored XSS in Forum Discussions & Assignments | Backend (Blade Views) | 1 hour | Replace unescaped `{!! !!}` with `{!! nl2br(e(...)) !!}` |
| **FL-HIGH-04** | Arbitrary Unrestricted File Upload in Assignment Submission API | Backend (`StudentCourseController.php` & storage `.htaccess`) | 45 mins | Add MIME/extension validation + deploy `.htaccess` file upload execution blocker |
| **FL-HIGH-05** | Public AI Chat Defaults to Admin ID 1 & Missing Rate Limits | Backend (`AiChatController.php`) | 45 mins | Prevent unauthenticated fallback to user ID 1; add RateLimiter |
| **FL-HIGH-06** | Enforce Webhook Signature Verification on Chapa Callbacks | Backend (`CourseEnrollmentController.php`) | 30 mins | Reject requests missing or mismatching `Chapa-Signature` with 401/403 |
| **FL-MED-01** | Add Rate Limiting to Auth Endpoints (`/api/auth/login`, `/login`) | Backend (`routes/api.php`, `LoginController.php`) | 30 mins | Apply `throttle:10,1` to authentication routes |
| **FL-MED-02** | Migrate Flutter Auth Token Storage to `flutter_secure_storage` | Flutter (`base_service.dart`, `AndroidManifest.xml`) | 1.5 hours | Add `flutter_secure_storage`, disable `android:allowBackup` |
| **FL-MED-04** | Invalidate Tokens & Sessions on Password Reset | Backend (`AuthController.php`) | 15 mins | Call `$user->tokens()->delete()` on password reset |
| **FL-MED-05** | Supply Chain Dependency Fixes (`npm audit fix`, Composer update) | Backend Dependencies | 1 hour | Execute security patch updates for `shell-quote`, `vite`, `axios` |

### Total Effort for Phase 2: ~6.5 hours

---

## 4. Phase 3: Post-Launch & Ongoing Hardening (Defense-in-Depth)

| Finding ID | Title | Component | Estimated Effort |
| :--- | :--- | :--- | :---: |
| **FL-LOW-01** | Implement Content-Security-Policy (CSP) & Permissions-Policy | Backend (`EnforceCanonicalAndSecurityHeaders.php`) | 1.5 hours |
| **FL-LOW-02** | Enforce Mandatory Cron Secret Key Requirement | Backend (`CronController.php`) | 15 mins |
| **HARD-01** | Remove Default Demo Credentials (`AdminSeeder.php`, `LoginController.php`) | Backend & Database Seeders | 45 mins |
| **HARD-02** | Centralize cURL Handlers into Laravel HTTP Client (`Http::withHeaders`) | Backend Architecture | 3–4 hours |
| **HARD-03** | Establish Dedicated Security Audit Logging Channel (`storage/logs/security.log`) | Backend Logging Architecture | 2 hours |

---

## 5. Execution Rules (Phase 6 Protocol)

Once approval is granted by the system owner:
1. **Zero Downtime / Atomic Commits:** Each vulnerability will be fixed in an independent, single-purpose git commit.
2. **Regression Prevention:** Each fix will be paired with automated unit tests or local integration probes demonstrating:
   - Before: Vulnerability reproduction.
   - After: Safe rejection (403, 404, or validation failure) with no regression to legitimate users.
3. **No Direct Production Edits:** All changes will be made locally in the development repository and validated before requesting deployment.
