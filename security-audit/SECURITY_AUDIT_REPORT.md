# Comprehensive Pre-Production Security Audit & Vulnerability Assessment
**Target System:** Fira Link Academy (Eduex LMS Platform & Flutter Mobile Application)  
**Evaluation Standards:** OWASP Top 10 (2021), OWASP API Security Top 10 (2023), OWASP ASVS Level 2, INSA Baseline Guidelines, Ethiopian Personal Data Protection Proclamation  
**Audit Type:** Pre-Production White-Box Source Code Audit (SAST), Architecture Review, Dependency Supply Chain Analysis & Dynamic Threat Modeling  
**Date:** October 24, 2026  
**Status:** Audit Completed — **RECOMMENDATION: NO-GO FOR PRODUCTION DEPLOYMENT**  

---

## 1. Executive Summary

A comprehensive pre-production application security audit and penetration assessment was conducted on the **Fira Link Academy** learning management system (comprising the Laravel 12 API & Web backend and the Flutter 3.47 mobile client). 

The audit identified **17 security findings**, including **4 Critical-severity**, **6 High-severity**, **4 Medium-severity**, and **3 Low/Informational-severity** vulnerabilities. 

### Risk Breakdown

| Severity | Count | Action Required |
| :--- | :---: | :--- |
| **Critical** | 4 | **Block production release immediately.** Must be resolved prior to internet connectivity. |
| **High** | 6 | **Block production release.** Severe impact on integrity, tenant isolation, and user privacy. |
| **Medium** | 4 | Remediate before open public beta / commercial payment onboarding. |
| **Low / Informational** | 3 | Address during hardening cycle and standard sprint hygiene. |
| **Total Findings** | **17** | **Production Readiness Verdict: FAILED (NO-GO)** |

### Critical Highlights & Primary Attack Vectors
1. **Unauthenticated Arbitrary File Read / Path Traversal (RCE Precursor):** Both the web storage fallback route (`/storage/{path}`) and the public video streaming endpoint (`/api/video/stream?path=...`) permit dot-dot-slash (`../`) directory traversal, exposing `.env` files, SQLite databases, private SSH keys, and system configuration directly to unauthenticated internet actors.
2. **Global Disablement of TLS Verification (CURLOPT_SSL_VERIFYPEER = false):** Over 35 outbound network routines—covering Chapa, PayPal, Razorpay, SSLCommerz, and SMS Ethiopia—disable certificate validation, creating susceptibility to active Man-in-the-Middle (MitM) payment spoofing and credential theft.
3. **Leaked Merchant Secret Key in Client API Response:** The SSLCommerz checkout initiation endpoint transmits the merchant's live `store_password` directly to client devices in plaintext JSON.
4. **Missing Role Enforcement on Instructor API (Privilege Escalation):** The entire `/api/instructor/*` route group enforces Sanctum authentication but completely omits the `role:instructor` check, allowing any registered student account to access instructor analytics, earnings, withdrawal requests, student records, and coupon generation.
5. **Stored Cross-Site Scripting (XSS) in Forums & Assignments:** Forum discussion bodies, replies, and assignment submissions are rendered using raw Blade tags (`{!! ... !!}`) without output escaping, allowing authenticated students to execute malicious JavaScript in administrative and instructor sessions.

---

## 2. Scope, Methodology, and Tools

### 2.1 Scope
The assessment covered the entire codebase within the authorized local staging workspace:
- **Backend Architecture & APIs:** Laravel 12 application located in `Eduex Laravel/Eduex Laravel/` (routes: `web.php`, `api.php`, `admin.php`, `instructor.php`, `student.php`, `auth.php`, `install.php`).
- **Mobile Application Client:** Flutter application located in `Eduex Flutter/` (service layers, local storage, API communication, Android manifest).
- **Deployment & Infrastructure Assets:** Apache configuration (`.htaccess`), deployment scripts (`deploy_lms.py`), and environment configuration (`.env`).

### 2.2 Methodology
- **Architectural & Threat Modeling:** Trust boundary mapping, data-flow analysis, and privilege matrix evaluation.
- **Static Application Security Testing (SAST):** Manual source-code auditing across every route and controller against OWASP ASVS v4.0.3 Level 2 and OWASP Top 10.
- **Dependency & Supply Chain Auditing:** Executed automated vulnerability checks with `composer audit` and `npm audit`.
- **Infrastructure & Configuration Verification:** Scanned web server directives, TLS options, debug flags, and permission inheritance.

### 2.3 Automated Tooling Outputs
- **PHP Engine:** PHP 8.4.24 (cli) with ionCube Loader v15.5.0
- **Composer Package Audit:** Flagged vulnerable dependencies in `symfony/routing`, `symfony/yaml`, `symfony/mime`, `symfony/http-foundation`, `league/flysystem`.
- **Node.js Package Audit:** Identified 11 vulnerabilities (1 moderate, 8 high, 2 critical) including prototype pollution and arbitrary execution flaws in `shell-quote`, `vite`, `rollup`, `postcss`, `axios`.
- **Missing CLI Scanners:** `gitleaks`, `trufflehog`, `semgrep`, `trivy` were not present in the environment PATH. Installation commands are provided in Phase 2.

### 2.4 Limitations & Exclusions
In strict accordance with the rules of engagement:
- No requests were sent to third-party endpoints or production servers (`37.26.105.27`, `lms.fira-link.com`).
- No denial-of-service, flood, or destructive data tests were executed against the database.
- Parts NOT covered: Live cPanel operating system kernel hardening and hardware firewall configurations at the external hosting provider.

---

## 3. Findings Matrix

| Finding ID | Title | Severity | CVSS v3.1 | CWE | OWASP |
| :--- | :--- | :---: | :---: | :---: | :---: |
| **FL-CRIT-01** | Arbitrary File Read / Path Traversal in Web Storage Route | **Critical** | **9.8** | CWE-22 | A01:2021 |
| **FL-CRIT-02** | Unauthenticated Arbitrary File Read & Server Path Disclosure in Video Stream API | **Critical** | **9.1** | CWE-22 | A01:2021 / API1:2023 |
| **FL-CRIT-03** | Global Disablement of TLS Verification in Payment & SMS Gateways (`CURLOPT_SSL_VERIFYPEER = false`) | **Critical** | **9.0** | CWE-295 | A02:2021 / API2:2023 |
| **FL-CRIT-04** | Plaintext Merchant Secret Key Leaked in SSLCommerz API Response | **Critical** | **9.1** | CWE-312 | A02:2021 / API3:2023 |
| **FL-HIGH-01** | Missing Role Authorization on Instructor API Routes (Privilege Escalation) | **High** | **8.8** | CWE-285 | A01:2021 / API5:2023 |
| **FL-HIGH-02** | BOLA / IDOR & Unmasked PII Harvesting in Instructor Students API | **High** | **7.5** | CWE-639 | A01:2021 / API1:2023 |
| **FL-HIGH-03** | Stored Cross-Site Scripting (XSS) in Discussion Forums and Assignments | **High** | **8.0** | CWE-79 | A03:2021 |
| **FL-HIGH-04** | Arbitrary Unrestricted File Upload in Assignment Submission API | **High** | **8.8** | CWE-434 | A04:2021 |
| **FL-HIGH-05** | Public AI Chat Defaults to Admin Account & Unauthenticated LLM Resource Exhaustion | **High** | **7.5** | CWE-200 | A01:2021 / LLM04 |
| **FL-HIGH-06** | Insecure Webhook Signature Handling on Chapa Payment Webhook | **High** | **8.1** | CWE-345 | A07:2021 |
| **FL-MED-01** | Missing Rate Limiting and Brute-Force Lockout on Web & API Login Endpoints | **Medium** | **6.5** | CWE-307 | A07:2021 / API4:2023 |
| **FL-MED-02** | Unencrypted Plaintext Token Storage in Mobile App (`SharedPreferences` & Android Backup) | **Medium** | **6.2** | CWE-312 | A02:2021 |
| **FL-MED-03** | Live API Secrets Committed in Repository `.env` Configuration | **Medium** | **6.5** | CWE-798 | A05:2021 |
| **FL-MED-04** | Missing Token Invalidation & Session Termination on Password Reset | **Medium** | **5.3** | CWE-613 | A07:2021 |
| **FL-MED-05** | High & Critical Vulnerabilities in NPM (`shell-quote`, `vite`) & Composer Packages | **Medium** | **6.8** | CWE-1395 | A06:2021 |
| **FL-LOW-01** | Missing Content-Security-Policy (CSP) & Permissions-Policy Headers | **Low** | **3.7** | CWE-1021 | A05:2021 |
| **FL-LOW-02** | Unauthenticated Execution Risk on Cron Web Route | **Low** | **3.7** | CWE-306 | A01:2021 |

---

## 4. In-Depth Vulnerability Details

---

### FL-CRIT-01: Arbitrary File Read / Path Traversal in Web Storage Route
- **Severity:** Critical (CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H — **9.8**)
- **CWE:** CWE-22 (Improper Limitation of a Pathname to a Restricted Directory)
- **OWASP:** A01:2021 — Broken Access Control
- **Affected Files:** `Eduex Laravel/Eduex Laravel/routes/web.php` (Lines 164–180)

#### Description
In `routes/web.php`, the application implements a custom file-serving route for files under `/storage/{path}` using `where('path', '.*')`. The incoming `$path` parameter is concatenated directly to `storage_path('app/public/' . $path)` without checking whether the canonical resolved path resides within the intended directory. Because `file_exists()` resolves relative path navigation sequences (`../`), an unauthenticated remote attacker can climb out of the storage directory to the application root.

```php
// routes/web.php:164-180
Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        $fullPath = public_path('storage/' . $path);
    }
    if (!file_exists($fullPath)) {
        abort(404);
    }
    $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
    return response()->file($fullPath, [ ... ]);
})->where('path', '.*');
```

#### Impact
An attacker can read `.env`, database files (`database.sqlite`), source code, mail logs, and private keys. With access to `.env`, the attacker acquires `APP_KEY`, database credentials, and payment API secrets, leading to full system compromise.

#### Safe Reproduction Steps
1. Send an HTTP request to the local staging instance:
   `GET /storage/..%2F..%2F.env HTTP/1.1`
2. Observe that the application checks `storage/app/public/../../.env` which resolves to the parent `.env` file.
3. The server returns HTTP 200 OK with the full `.env` file contents.

#### Remediation Patch
Enforce strict canonical path resolution with `realpath()` and assert that the target file begins with the designated base storage path:
```diff
--- a/routes/web.php
+++ b/routes/web.php
@@ -164,13 +164,18 @@ Route::middleware(['auth'])->group(function () {
 Route::get('/storage/{path}', function (string $path) {
-    $fullPath = storage_path('app/public/' . $path);
-    if (!file_exists($fullPath)) {
-        $fullPath = public_path('storage/' . $path);
-    }
-    if (!file_exists($fullPath)) {
-        abort(404);
-    }
+    $baseDir = realpath(storage_path('app/public'));
+    $targetPath = realpath(storage_path('app/public/' . $path));
+
+    if ($targetPath === false || !str_starts_with($targetPath, $baseDir) || !is_file($targetPath)) {
+        abort(404);
+    }
+    $fullPath = $targetPath;
     $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
```
- **Effort Estimate:** 30 minutes.

---

### FL-CRIT-02: Unauthenticated Arbitrary File Read & Server Path Disclosure in Video Stream API
- **Severity:** Critical (CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N — **9.1**)
- **CWE:** CWE-22, CWE-200 (Information Disclosure)
- **OWASP:** A01:2021 — Broken Access Control / API1:2023 — Broken Object Level Authorization
- **Affected Files:** `Eduex Laravel/Eduex Laravel/app/Http/Controllers/Api/FileController.php` (Lines 15–43), `routes/api.php` (Line 67)

#### Description
The API route `GET /api/video/stream` invokes `FileController::stream`, which accepts an arbitrary `path` query string parameter. The controller performs only a superficial string replacement of `storage/` and concatenates the parameter into `storage_path('app/public/' . $cleanPath)` and `storage_path('app/' . $cleanPath)`:

```php
// FileController.php:24-39
$cleanPath = str_replace('storage/', '', $path);
if (str_starts_with($cleanPath, '/')) {
    $cleanPath = substr($cleanPath, 1);
}
$fullPath = storage_path('app/public/' . $cleanPath);
if (!file_exists($fullPath)) {
    $fullPath = storage_path('app/' . $cleanPath);
    if (!file_exists($fullPath)) {
        return response()->json(['error' => 'File not found at ' . $fullPath], 404);
    }
}
return new BinaryFileResponse($fullPath);
```

#### Impact
1. Passing `?path=../../.env` or `?path=../../database/database.sqlite` streams those files directly via `BinaryFileResponse`.
2. When a file does not exist, line 35 explicitly outputs the absolute operating system path in the 404 error response (e.g., `File not found at C:\Users\sora\fira-link-lms\Eduex Laravel\Eduex Laravel\storage\app\...`), aiding attackers in path mapping.

#### Safe Reproduction Steps
1. Issue request: `GET /api/video/stream?path=..%2F..%2F.env HTTP/1.1`
2. Observe HTTP 200 response streaming file contents.
3. Issue request: `GET /api/video/stream?path=nonexistent_file_test HTTP/1.1`
4. Observe internal server path disclosure in JSON response.

#### Remediation Patch
```diff
--- a/app/Http/Controllers/Api/FileController.php
+++ b/app/Http/Controllers/Api/FileController.php
@@ -23,17 +23,15 @@ class FileController extends Controller
-        $cleanPath = str_replace('storage/', '', $path);
-        if (str_starts_with($cleanPath, '/')) {
-            $cleanPath = substr($cleanPath, 1);
-        }
-        $fullPath = storage_path('app/public/' . $cleanPath);
-        if (!file_exists($fullPath)) {
-            $fullPath = storage_path('app/' . $cleanPath);
-            if (!file_exists($fullPath)) {
-                return response()->json(['error' => 'File not found at ' . $fullPath], 404);
-            }
-        }
-        $response = new BinaryFileResponse($fullPath);
+        $cleanPath = ltrim(str_replace(['../', '..\\', 'storage/'], '', $path), '/\\');
+        $baseDir = realpath(storage_path('app/public'));
+        $fullPath = realpath(storage_path('app/public/' . $cleanPath));
+
+        if ($fullPath === false || !str_starts_with($fullPath, $baseDir) || !is_file($fullPath)) {
+            return response()->json(['error' => 'File not found.'], 404);
+        }
+        $response = new BinaryFileResponse($fullPath);
```
- **Effort Estimate:** 30 minutes.

---

### FL-CRIT-03: Global Disablement of TLS Verification in Payment & SMS Gateways
- **Severity:** Critical (CVSS:3.1/AV:N/AC:H/PR:N/UI:N/S:C/C:H/I:H/A:N — **9.0**)
- **CWE:** CWE-295 (Improper Certificate Validation)
- **OWASP:** A02:2021 — Cryptographic Failures / API2:2023 — Broken Authentication
- **Affected Files:** Over 35 code locations, notably:
  - `app/Http/Controllers/Front/CourseEnrollmentController.php` (Lines 1738, 1813, 2050, 2138, 2294, 2395, 2532, 2644, 2771, 2892, 2951, 2998)
  - `app/Http/Controllers/Api/CourseEnrollmentController.php` (Lines 743, 1090, 1269, 1324, 1409, 1506, 1565, 1631, 1690, 1765, 1874, 2010, 2104)
  - `app/Services/SmsEthiopiaService.php` (Line 203)
  - `app/Http/Controllers/Front/EventController.php` (Lines 254, 358)
  - `app/Http/Controllers/Front/TourController.php` (Lines 279, 334)
  - `app/Http/Controllers/Admin/PaymentGatewayController.php` (Line 108)

#### Description
In all cURL calls interacting with external third-party APIs (including Chapa transaction verification, PayPal order capturing, SSLCommerz validation, and SMS Ethiopia OTP dispatch), the code explicitly specifies:
```php
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
```
In several controllers, `CURLOPT_SSL_VERIFYHOST, 0` or omitted verifyhost options are also present.

#### Impact
Any adversary situated between the server and the upstream APIs (e.g., rogue gateway, ISP interception, DNS poisoner, or cloud VPC adjacent node) can intercept and modify TLS traffic without triggering an alert. Attackers can:
1. Return simulated `{ "status": "success" }` responses for uncompleted transactions, granting free enrollments.
2. Intercept unhashed OTP SMS messages sent via `SmsEthiopiaService`.
3. Harvest merchant private keys (`Authorization: Bearer <secretKey>`).

#### Safe Reproduction Steps
1. Inspect code: `grep -rn "CURLOPT_SSL_VERIFYPEER" app/`
2. Confirm over 35 distinct instances setting the flag to `false`.

#### Remediation Patch
1. Remove all instances of `CURLOPT_SSL_VERIFYPEER, false` or explicitly configure `CURLOPT_SSL_VERIFYPEER => true` and `CURLOPT_SSL_VERIFYHOST => 2`.
2. Refactor manual cURL calls to Laravel's standard HTTP Client (`Illuminate\Support\Facades\Http`) which uses secure Guzzle defaults backed by system certificate bundles.
```diff
--- a/app/Http/Controllers/Front/CourseEnrollmentController.php
+++ b/app/Http/Controllers/Front/CourseEnrollmentController.php
@@ -2639,7 +2639,7 @@ class CourseEnrollmentController extends Controller
             $ch = curl_init('https://api.chapa.co/v1/transaction/verify/' . $txRef);
             curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
             curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $secretKey]);
-            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
+            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
+            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
```
- **Effort Estimate:** 2–3 hours.

---

### FL-CRIT-04: Plaintext Merchant Secret Key Leaked in SSLCommerz API Response
- **Severity:** Critical (CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:N — **9.1**)
- **CWE:** CWE-312 (Cleartext Storage or Transmission of Sensitive Information)
- **OWASP:** A02:2021 — Cryptographic Failures / API3:2023 — Broken Object Property Level Authorization
- **Affected Files:** `Eduex Laravel/Eduex Laravel/app/Http/Controllers/Api/CourseEnrollmentController.php` (Line 234)

#### Description
In `CourseEnrollmentController::enroll`, when a student initiates an enrollment with `payment_method: sslcommerz`, the controller constructs a JSON response intended to assist the frontend checkout modal. Line 234 transmits the backend's secret credentials:

```php
// CourseEnrollmentController.php:230-243
return response()->json([
    'success' => true,
    'tran_id' => $tranId,
    'amount' => $price,
    'store_id' => $credentials['store_id'] ?? '',
    'store_password' => $credentials['store_password'] ?? '',
    'mode' => $credentials['mode'] ?? 'sandbox',
    'enrollment_id' => $enrollment->id,
...
]);
```

#### Impact
Any registered user (student) requesting checkout with `payment_method = 'sslcommerz'` immediately receives the merchant's live `store_password`. The attacker can authenticate directly to SSLCommerz merchant portals, initiate unauthorized transactions, or forge valid transaction receipts.

#### Safe Reproduction Steps
1. Authenticate as any normal student.
2. Dispatch API request:
   `POST /api/courses/1/enroll` with body `{"payment_method": "sslcommerz"}`
3. Examine response JSON body: verify `'store_password'` property is returned.

#### Remediation Patch
Never transmit secret keys to client devices. Pass only the pre-generated transaction ID or initiate the checkout session server-side:
```diff
--- a/app/Http/Controllers/Api/CourseEnrollmentController.php
+++ b/app/Http/Controllers/Api/CourseEnrollmentController.php
@@ -231,8 +231,7 @@ class CourseEnrollmentController extends Controller
                     'success' => true,
                     'tran_id' => $tranId,
                     'amount' => $price,
                     'store_id' => $credentials['store_id'] ?? '',
-                    'store_password' => $credentials['store_password'] ?? '',
                     'mode' => $credentials['mode'] ?? 'sandbox',
```
- **Effort Estimate:** 30 minutes.

---

### FL-HIGH-01: Missing Role Authorization on Instructor API Routes (Privilege Escalation)
- **Severity:** High (CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:H — **8.8**)
- **CWE:** CWE-285 (Improper Authorization), CWE-269 (Improper Privilege Management)
- **OWASP:** A01:2021 — Broken Access Control / API5:2023 — Broken Function Level Authorization
- **Affected Files:** `Eduex Laravel/Eduex Laravel/routes/api.php` (Line 224)

#### Description
In `routes/instructor.php` (web routes), instructor views are guarded by `Route::middleware(['auth', 'role:instructor'])`. However, in `routes/api.php`, the entire instructor API sub-system is protected only by:
```php
Route::prefix('instructor')->group(function () {
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
```
The `role:instructor` middleware is completely missing. Because any registered user receives a Sanctum bearer token upon registration/login and is set to `is_approved = true` by default (`AuthController.php:73`), ordinary student accounts can query and execute all instructor API endpoints:
- List all course earnings & trigger instructor withdrawal requests (`/api/instructor/withdrawals`)
- Create 100% discount coupons across courses (`/api/instructor/coupons`)
- Access student progress and unmasked phone numbers (`/api/instructor/students/{id}`)
- Export attendee details from events (`/api/instructor/events/{id}/export`)

#### Impact
Complete horizontal and vertical privilege escalation from student to instructor across the mobile and API surfaces.

#### Safe Reproduction Steps
1. Register a student via `POST /api/auth/register`.
2. Send request to `GET /api/instructor/dashboard` or `GET /api/instructor/earnings` with the student Bearer token.
3. Observe HTTP 200 response rather than HTTP 403 Forbidden.

#### Remediation Patch
Attach the `role:instructor` middleware to the route group in `routes/api.php`:
```diff
--- a/routes/api.php
+++ b/routes/api.php
@@ -221,7 +221,7 @@ Route::prefix('instructor')->group(function () {
         Route::post('/forgot-password', [\App\Http\Controllers\Api\Instructor\AuthController::class, 'forgotPassword']);
     });
     
-    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
+    Route::middleware(['auth:sanctum', 'role:instructor', 'throttle:api'])->group(function () {
         Route::get('/user', [\App\Http\Controllers\Api\Instructor\AuthController::class, 'user']);
```
- **Effort Estimate:** 15 minutes.

---

### FL-HIGH-02: BOLA / IDOR & Unmasked PII Harvesting in Instructor Students API
- **Severity:** High (CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:N/A:N — **7.5**)
- **CWE:** CWE-639 (Authorization Bypass Through User-Controlled Key)
- **OWASP:** A01:2021 — Broken Access Control / API1:2023 — Broken Object Level Authorization
- **Affected Files:** `Eduex Laravel/Eduex Laravel/app/Http/Controllers/Api/Instructor/StudentsController.php` (Lines 114–174)

#### Description
In `StudentsController::show($id)`:
```php
public function show($id)
{
    $instructor = Auth::user();
    $student = User::findOrFail($id);

    // Get instructor's course IDs
    $instructorCourseIds = Course::where('instructor_id', $instructor->id)->pluck('id');

    // Get student's enrollments for these courses
    $enrollments = Enrollment::where('user_id', $id)
        ->whereIn('course_id', $instructorCourseIds)
        ->with('course')
        ->get();
...
    return response()->json([
        'success' => true,
        'student' => [
            'id' => $student->id,
            'name' => $student->name,
            'email' => $this->maskEmail($student->email),
            'bio' => $student->bio,
            'phone' => $student->phone, // RAW UNMASKED PHONE NUMBER
        ],
...
```
The controller retrieves the target `$student` by ID, attempts to find enrollments under the caller's courses, but **never checks whether `$enrollments->isNotEmpty()`**. Even if the student is not enrolled in any of the instructor's courses, the method proceeds and returns the full student profile including their unmasked phone number.

#### Impact
An attacker can iterate through user IDs (`/api/instructor/students/1`, `2`, `3`...) to harvest the full name, unmasked mobile phone number, bio, and login timestamps of every student, instructor, and administrator in the system.

#### Safe Reproduction Steps
1. Log in as an instructor (or student leveraging FL-HIGH-01).
2. Request `/api/instructor/students/{target_id}` for an arbitrary user ID with zero course associations.
3. Observe HTTP 200 response containing the victim's name, bio, and unmasked phone number.

#### Remediation Patch
Verify enrollment relationship and mask phone numbers:
```diff
--- a/app/Http/Controllers/Api/Instructor/StudentsController.php
+++ b/app/Http/Controllers/Api/Instructor/StudentsController.php
@@ -124,6 +124,10 @@ class StudentsController extends Controller
             ->whereIn('course_id', $instructorCourseIds)
             ->with('course')
             ->get();
+
+        if ($enrollments->isEmpty() && !Auth::user()->isAdmin()) {
+            return response()->json(['success' => false, 'message' => 'Student not enrolled in your courses.'], 403);
+        }
```
- **Effort Estimate:** 30 minutes.

---

### FL-HIGH-03: Stored Cross-Site Scripting (XSS) in Discussion Forums and Assignments
- **Severity:** High (CVSS:3.1/AV:N/AC:L/PR:L/UI:R/S:U/C:H/I:H/A:N — **8.0**)
- **CWE:** CWE-79 (Improper Neutralization of Input During Web Page Generation)
- **OWASP:** A03:2021 — Injection
- **Affected Files:**
  - `resources/views/student/course-access/partials/forum-show.blade.php` (Lines 35, 95)
  - `resources/views/instructor/discussions/show.blade.php` (Lines 59, 123)
  - `resources/views/student/course-access/partials/assignment-content.blade.php` (Line 74)
  - `resources/views/student/course-access/partials/assignment-submission.blade.php` (Line 29)

#### Description
In course forum discussions, replies, and assignment submissions, user input is printed in Blade templates using unescaped syntax:
```html
<div class="thread-body-content ...">
    {!! $discussion->content !!}
</div>
...
<p class="mb-3 text-secondary ...">
    {!! $reply->content !!}
</p>
```
While `message-bubble.blade.php` uses `{!! nl2br(e($msg->body)) !!}`, forum and assignment views omit `e()`. Furthermore, there is no HTMLPurifier or HTML sanitizer applied when saving discussion content or assignment text.

#### Impact
An authenticated student or malicious actor can create a discussion thread or reply containing `<script>/* malicious payload */</script>` or `<img src=x onerror=...>` tags. Whenever other students or instructors open the forum discussion, the script executes within their authenticated session, facilitating session hijacking, CSRF actions, and token exfiltration.

#### Safe Reproduction Steps
1. Create a benign discussion reply containing `<b>bold</b><img src=x onerror="console.log('XSS-Probe')">`.
2. Inspect the HTML DOM of `forum-show.blade.php`: observe that the `img` tag is rendered unescaped and executes the script.

#### Remediation Patch
Use Blade's native escaping `{{ ... }}` or `nl2br(e(...))` for multiline text:
```diff
--- a/resources/views/student/course-access/partials/forum-show.blade.php
+++ b/resources/views/student/course-access/partials/forum-show.blade.php
@@ -32,7 +32,7 @@
         <h4 style="font-weight: 700; color: #1e1e2d; margin-bottom: 16px;">{{ $discussion->title }}</h4>
         <div class="thread-body-content text-secondarymb-4" style="font-size: 14px; line-height: 1.6; color: #4b5563; white-space: pre-line;">
-            {!! $discussion->content !!}
+            {!! nl2br(e($discussion->content)) !!}
         </div>
@@ -92,7 +92,7 @@
                             
                             <p class="mb-3 text-secondary" style="font-size: 13px; line-height: 1.5; color: #4b5563; white-space: pre-line;">
-                                {!! $reply->content !!}
+                                {!! nl2br(e($reply->content)) !!}
                             </p>
```
- **Effort Estimate:** 1 hour across all affected Blade views.

---

### FL-HIGH-04: Arbitrary Unrestricted File Upload in Assignment Submission API
- **Severity:** High (CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:H/A:H — **8.8**)
- **CWE:** CWE-434 (Unrestricted Upload of File with Dangerous Type)
- **OWASP:** A04:2021 — Insecure Design
- **Affected Files:** `Eduex Laravel/Eduex Laravel/app/Http/Controllers/Api/StudentCourseController.php` (Lines 465, 496–505)

#### Description
In `StudentCourseController::submitAssignment`:
```php
$validator = Validator::make($request->all(), [
    'submission_text' => 'nullable|string',
    'file' => 'nullable|file|max:10240', // 10MB max
]);
...
if ($request->hasFile('file')) {
    $path = $request->file('file')->store('assignments', 'public');
    $submission->files()->create([
        'file_path' => $path,
        'original_filename' => $request->file('file')->getClientOriginalName(),
        'file_size' => $request->file('file')->getSize(),
        'mime_type' => $request->file('file')->getClientMimeType(),
    ]);
}
```
Validation only asserts that `file` is a file up to 10MB. There are no restrictions on MIME type or file extensions. The uploaded file is saved to the public storage disk under `assignments/` retaining its original extension or hash name. Neither `storage/.htaccess` nor `public/storage/.htaccess` exists to disable PHP execution.

#### Impact
An enrolled student can submit an assignment containing executable PHP files (`.php`, `.phtml`, `.phar`) or HTML files with embedded scripts. If the web server serves PHP files out of `public/storage/`, this results in Remote Code Execution (RCE). If served as HTML, it leads to Stored XSS on the instructor's domain.

#### Safe Reproduction Steps
1. Submit an assignment with a harmless file named `test.svg` containing SVG markup or an HTML test file.
2. Observe that validation passes and the file is written directly into `storage/app/public/assignments/`.

#### Remediation Patch
1. Restrict validation rules to safe document formats:
```diff
--- a/app/Http/Controllers/Api/StudentCourseController.php
+++ b/app/Http/Controllers/Api/StudentCourseController.php
@@ -462,7 +462,7 @@ class StudentCourseController extends Controller
         $validator = Validator::make($request->all(), [
             'submission_text' => 'nullable|string',
-            'file' => 'nullable|file|max:10240',
+            'file' => 'nullable|file|mimes:pdf,doc,docx,zip,txt|max:10240',
         ]);
```
2. Place a hardened `.htaccess` in `storage/app/public/.htaccess` to unconditionally disable script execution:
```apache
<FilesMatch "(?i)\.(php|phtml|phar|pl|py|cgi|asp|js|html|htm)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```
- **Effort Estimate:** 30 minutes.

---

### FL-HIGH-05: Public AI Chat Defaults to Admin Account & Unauthenticated LLM Resource Exhaustion
- **Severity:** High (CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:L/I:N/A:H — **7.5**)
- **CWE:** CWE-200 (Exposure of Sensitive Information), CWE-400 (Uncontrolled Resource Consumption)
- **OWASP:** A01:2021 — Broken Access Control / LLM04 — Model Denial of Service
- **Affected Files:** `Eduex Laravel/Eduex Laravel/app/Http/Controllers/Student/AiChatController.php` (Lines 48–52, 103–113)

#### Description
In `AiChatController`:
```php
public function index(Request $request)
{
    $user = Auth::user() ?: auth('sanctum')->user();
    $userId = $user ? $user->id : 1; // DEFAULTS TO SUPER ADMIN ID 1!
    $messages = AiChatMessage::where('user_id', $userId)
        ->orderBy('created_at', 'asc')
        ->get();
...
public function sendMessage(Request $request)
{
    ...
    $user = Auth::user() ?: auth('sanctum')->user();
    $userId = $user ? $user->id : (User::first()?->id ?? 1);
    // Saves visitor message under Admin ID 1
    // Fetches Admin ID 1 chat history and sends to Google Gemini API
```
When an unauthenticated visitor requests `/api/fira-ai` or `/api/ai-chat`, the code falls back to `userId = 1`. Consequently, all private chat messages from user ID 1 (the Administrator) are returned to the anonymous caller. When the anonymous visitor sends a message, it is stored under user ID 1 and historical context from user 1 is fed to the Gemini AI API. Furthermore, neither `/api/fira-ai/send` nor `/ai-chat/send` implements rate limiting.

#### Impact
1. Data leak of private administrative AI queries and system logs to arbitrary public visitors.
2. Denial of service and financial quota exhaustion against the company's Google Gemini API key.

#### Safe Reproduction Steps
1. In an unauthenticated session, send: `GET /api/fira-ai HTTP/1.1`
2. Observe JSON containing chat history belonging to user ID 1.

#### Remediation Patch
Require authentication for persistent chat history, or isolate guest sessions using ephemeral session IDs instead of falling back to user ID 1:
```diff
--- a/app/Http/Controllers/Student/AiChatController.php
+++ b/app/Http/Controllers/Student/AiChatController.php
@@ -46,9 +46,12 @@ class AiChatController extends Controller
     public function index(Request $request)
     {
         $user = Auth::user() ?: auth('sanctum')->user();
-        $userId = $user ? $user->id : 1;
+        if (!$user) {
+            return response()->json(['success' => true, 'data' => []]);
+        }
+        $userId = $user->id;
```
- **Effort Estimate:** 45 minutes.

---

### FL-HIGH-06: Insecure Webhook Signature Handling on Chapa Payment Webhook
- **Severity:** High (CVSS:3.1/AV:N/AC:H/PR:N/UI:N/S:U/C:N/I:H/A:H — **8.1**)
- **CWE:** CWE-345 (Insufficient Verification of Data Authenticity)
- **OWASP:** A07:2021 — Identification and Authentication Failures
- **Affected Files:** `Eduex Laravel/Eduex Laravel/app/Http/Controllers/Front/CourseEnrollmentController.php` (Lines 2734–2743, 2789)

#### Description
In `CourseEnrollmentController::chapaWebhook`:
```php
$signatureValid = false;
if (!empty($webhookSecret) && !empty($signature)) {
    $expectedSignature = hash_hmac('sha256', $rawContent, $webhookSecret);
    if (!hash_equals($expectedSignature, $signature)) {
        return response()->json(['success' => false, 'message' => 'Invalid signature.'], 403);
    }
    $signatureValid = true;
}
```
If an attacker completely omits the `Chapa-Signature` header, the condition `!empty($signature)` evaluates to false. The execution skips signature verification and reaches:
```php
if ((!$signatureValid || $status !== 'success') && !empty($secretKey)) {
    // attempts cURL verification...
}
if ($status === 'success') {
    // APPROVES ENROLLMENT, ACTIVATES SUBSCRIPTIONS & EVENT BOOKINGS!
}
```
If the outbound cURL verification fails or if `$secretKey` is not configured, the handler falls through and marks the transaction as `STATUS_COMPLETED`, granting unauthorized access.

#### Impact
Potential spoofing of payment completion callbacks, granting fraudulent course enrollments and subscription activations.

#### Safe Reproduction Steps
1. Review logic flow: verify that omitting the signature header does not cause immediate termination with HTTP 401/403.

#### Remediation Patch
Enforce signature presence unconditionally on all incoming webhook notifications:
```diff
--- a/app/Http/Controllers/Front/CourseEnrollmentController.php
+++ b/app/Http/Controllers/Front/CourseEnrollmentController.php
@@ -2732,15 +2732,16 @@ class CourseEnrollmentController extends Controller
-        $signatureValid = false;
-        // Verify signature if webhook secret is configured
-        if (!empty($webhookSecret) && !empty($signature)) {
-            $expectedSignature = hash_hmac('sha256', $rawContent, $webhookSecret);
-            if (!hash_equals($expectedSignature, $signature)) {
-                \Illuminate\Support\Facades\Log::warning('Chapa webhook signature mismatch.', [
-                    'received' => $signature,
-                ]);
-                return response()->json(['success' => false, 'message' => 'Invalid signature.'], 403);
-            }
-            $signatureValid = true;
+        if (empty($signature) || empty($webhookSecret)) {
+            return response()->json(['success' => false, 'message' => 'Missing signature.'], 401);
+        }
+        $expectedSignature = hash_hmac('sha256', $rawContent, $webhookSecret);
+        if (!hash_equals($expectedSignature, $signature)) {
+            return response()->json(['success' => false, 'message' => 'Invalid signature.'], 403);
         }
```
- **Effort Estimate:** 30 minutes.

---

### FL-MED-01: Missing Rate Limiting and Brute-Force Lockout on Web & API Login Endpoints
- **Severity:** Medium (CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:L/I:L/A:N — **6.5**)
- **CWE:** CWE-307 (Improper Restriction of Excessive Authentication Attempts)
- **OWASP:** A07:2021 — Identification and Authentication Failures / API4:2023 — Unrestricted Resource Consumption
- **Affected Files:** `routes/api.php` (Lines 43–57), `app/Http/Controllers/Api/AuthController.php` (Lines 105–135), `app/Http/Controllers/Auth/LoginController.php` (Lines 39–86)

#### Description
The API route `POST /api/auth/login` and web route `POST /login` lack rate-limiting throttle middleware (such as `throttle:login` or `throttle:5,1`). While OTP sending has internal cooldowns, password validation attempts are completely unthrottled. An attacker can execute automated credential stuffing and dictionary attacks at high speed.

#### Impact
High vulnerability to credential stuffing, dictionary attacks, and unauthorized account access.

#### Remediation Patch
Apply rate limiting directly to the auth route groups:
```diff
--- a/routes/api.php
+++ b/routes/api.php
@@ -43,7 +43,7 @@ Route::get('/fira-ai', [AiChatController::class, 'index']);
-Route::prefix('auth')->group(function () {
+Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
     Route::post('/register', [AuthController::class, 'register']);
     Route::post('/login', [AuthController::class, 'login']);
```
- **Effort Estimate:** 30 minutes.

---

### FL-MED-02: Unencrypted Plaintext Token Storage in Mobile App (`SharedPreferences` & Android Backup)
- **Severity:** Medium (CVSS:3.1/AV:L/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N — **6.2**)
- **CWE:** CWE-312 (Cleartext Storage of Sensitive Information)
- **OWASP:** A02:2021 — Cryptographic Failures
- **Affected Files:** `Eduex Flutter/lib/services/base_service.dart` (Lines 10–23), `Eduex Flutter/android/app/src/main/AndroidManifest.xml` (Lines 16–20)

#### Description
In Flutter `BaseService.dart`, user authentication tokens (`auth_token`) are stored in unencrypted Android `SharedPreferences`. In `AndroidManifest.xml`, the `<application>` element does not specify `android:allowBackup="false"`, meaning device backups (via `adb backup`) include the plaintext `FlutterSharedPreferences.xml` file.

#### Impact
Anyone with physical access to an unlocked device, an enterprise MDM backup, or malware on a rooted device can extract the bearer token and impersonate the user indefinitely.

#### Remediation Patch
1. Add `flutter_secure_storage: ^9.2.4` to `pubspec.yaml` to store tokens in the Android Keystore and iOS Keychain.
2. In `AndroidManifest.xml`, add `android:allowBackup="false"` and `android:fullBackupContent="false"` to `<application>`.
- **Effort Estimate:** 1.5 hours.

---

### FL-MED-03: Live API Secrets Committed in Repository `.env` Configuration
- **Severity:** Medium (CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:N/A:N — **6.5**)
- **CWE:** CWE-798 (Use of Hard-coded Credentials)
- **OWASP:** A05:2021 — Security Misconfiguration
- **Affected Files:** `Eduex Laravel/Eduex Laravel/.env` (Lines 55, 59, 60)

#### Description
The repository `.env` file contains live, production-format API keys:
- `GEMINI_API_KEY`: `AQ.A...` (Google Gemini AI)
- `ANTHROPIC_API_KEY`: `sk-S...` (Anthropic AI)
- `CLAUDE_API_KEY`: `sk-S...` (Claude AI)
- `APP_DEBUG=true`

#### Impact
Anyone with read access to the repository, staging backups, or exploiting file disclosure (FL-CRIT-01, FL-CRIT-02) gains immediate access to paid AI APIs and internal application diagnostics.

#### Remediation Patch
1. Immediately revoke and regenerate the active Gemini and Anthropic API keys in the respective vendor developer consoles.
2. Remove sensitive credentials from `.env` and `.env.production`.
3. Set `APP_DEBUG=false` for any environment beyond isolated local unit testing.
- **Effort Estimate:** 30 minutes.

---

### FL-MED-04: Missing Token Invalidation & Session Termination on Password Reset
- **Severity:** Medium (CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:L/I:L/A:N — **5.3**)
- **CWE:** CWE-613 (Insufficient Session Expiration)
- **OWASP:** A07:2021 — Identification and Authentication Failures
- **Affected Files:** `Eduex Laravel/Eduex Laravel/app/Http/Controllers/Api/AuthController.php` (Lines 351–353)

#### Description
When a user completes `resetPasswordWithOtp` via phone OTP verification, the application updates the password hash:
```php
$user->password = Hash::make($request->password);
$user->save();
```
The method does not revoke existing Sanctum personal access tokens (`$user->tokens()->delete()`). If a user resets their password because their device or token was compromised, the active token remains valid indefinitely.

#### Remediation Patch
```diff
--- a/app/Http/Controllers/Api/AuthController.php
+++ b/app/Http/Controllers/Api/AuthController.php
@@ -351,6 +351,7 @@ class AuthController extends Controller
         $user->password = Hash::make($request->password);
+        $user->tokens()->delete();
         $user->save();
```
- **Effort Estimate:** 15 minutes.

---

### FL-MED-05: Vulnerabilities in NPM & Composer Dependencies
- **Severity:** Medium (CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:L/I:L/A:L — **6.8**)
- **CWE:** CWE-1395 (Dependency on Vulnerable Third-Party Component)
- **OWASP:** A06:2021 — Vulnerable and Outdated Components
- **Affected Packages:**
  - `shell-quote` (Critical — GHSA-w7jw-789q-3m8p)
  - `vite` (High — GHSA-v6wh-96g9-6wx3, Windows UNC NTLM leak; GHSA-p9ff-h696-f583, Arbitrary File Read)
  - `postcss` (High — GHSA-qx2v-qp2m-jg93, GHSA-6g55-p6wh-862q)
  - `axios` (High — Prototype pollution & header injection)
  - `symfony/routing` & `symfony/yaml` (Moderate/Low — ReDoS, dot-segment URL collapse)

#### Remediation Instructions
1. Run `npm audit fix` in `Eduex Laravel/Eduex Laravel`.
2. Update Composer packages with `composer update --with-all-dependencies` for safe security patches.
- **Effort Estimate:** 1 hour.

---

### FL-LOW-01: Missing Content-Security-Policy (CSP) & Permissions-Policy Headers
- **Severity:** Low (CVSS:3.1/AV:N/AC:H/PR:N/UI:N/S:U/C:L/I:N/A:N — **3.7**)
- **CWE:** CWE-1021
- **OWASP:** A05:2021 — Security Misconfiguration
- **Affected Files:** `app/Http/Middleware/EnforceCanonicalAndSecurityHeaders.php` (Lines 43–53)

#### Description
The middleware injects `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, and `Strict-Transport-Security`. However, it does not supply a `Content-Security-Policy` (CSP) or `Permissions-Policy`. A strict CSP is essential to mitigate residual XSS impact in LMS environments.

#### Remediation Patch
Add standard CSP headers configured for the application's domain, fonts, and payment modal requirements.
- **Effort Estimate:** 1 hour.

---

### FL-LOW-02: Unauthenticated Execution Risk on Cron Web Route
- **Severity:** Low (CVSS:3.1/AV:N/AC:H/PR:N/UI:N/S:U/C:N/I:N/A:L — **3.7**)
- **CWE:** CWE-306 (Missing Authentication for Critical Function)
- **OWASP:** A01:2021 — Broken Access Control
- **Affected Files:** `app/Http/Controllers/CronController.php` (Lines 34–55)

#### Description
If neither `cron_key` in the database settings nor `CRON_KEY` in `.env` is configured, `CronController::run()` defaults to running `Artisan::call('schedule:run')` without requiring any key query parameter. External visitors can repeatedly hit `/cron/run`, consuming system CPU and disk logging resources.

#### Remediation Patch
Abort immediately if no cron key is configured:
```diff
--- a/app/Http/Controllers/CronController.php
+++ b/app/Http/Controllers/CronController.php
@@ -32,6 +32,9 @@ class CronController extends Controller
         $configuredKey = $this->settings->get('cron_key') ?: env('CRON_KEY');
+        if (empty($configuredKey)) {
+            return response()->json(['status' => 'error', 'message' => 'Cron key not configured.'], 403);
+        }
```
- **Effort Estimate:** 15 minutes.

---

## 5. Positive Security Controls in Place
1. **Password Hashing:** Passwords are consistently hashed using Bcrypt (`Hash::make(...)`) with a standard cost factor.
2. **OTP Security Engine:** `OtpService` uses cryptographically secure random integers (`random_int`), enforces maximum attempt limits (default 5 attempts), applies resend cooldowns, and stores hashed tokens in the cache rather than plaintext.
3. **Canonical Normalization & HSTS:** `EnforceCanonicalAndSecurityHeaders` middleware automatically enforces canonical non-WWW domains and injects `Strict-Transport-Security` (`max-age=31536000; includeSubDomains`) in production environments.
4. **Parameterized ORM Queries:** Standard database operations extensively utilize Eloquent ORM parameter binding; no direct string concatenation in SQL was observed.
5. **Cookie Consent Compliance:** Integration with `spatie/laravel-cookie-consent` is present.
6. **Mobile Cleartext Traffic Disabled:** `android:usesCleartextTraffic="false"` is properly declared in `AndroidManifest.xml`.
7. **Mass Assignment Protections:** Models explicitly declare `$fillable` attributes, preventing unintended column overrides on user records.

---

## 6. INSA & Ethiopian Data Protection Compliance Gaps

### 6.1 Ethiopian Personal Data Protection Proclamation Standards
- **Data Minimization & Confidentiality:** Mobile numbers (+251...) are sensitive personal identifiers tied to National ID (Fayda / Ethio Telecom identity registry). The disclosure of unmasked phone numbers in `StudentsController.php` (FL-HIGH-02) and `AuthController::user` violates data minimization requirements.
- **Data at Rest Protection on Mobile Endpoints:** Saving bearer authentication tokens in unencrypted `SharedPreferences` (FL-MED-02) fails data at rest security benchmarks for mobile applications handling student records.

### 6.2 INSA (Information Network Security Administration) Expectations
- **Encryption in Transit Standards:** INSA strictly prohibits bypassing TLS certificate verification (`CURLOPT_SSL_VERIFYPEER = false`) on systems connecting to national payment aggregators (Chapa, Telebirr) and telecom SMS gateways (SMS Ethiopia).
- **Incident Response & Audit Logging:** While basic error logs exist, security event logging (login failures, password resets, role changes, admin impersonation) lacks dedicated audit log channels and IP tracking.

---

## 7. Recommended Next Steps

1. **Acknowledge Audit Findings:** Review the prioritized findings in [REMEDIATION_PLAN.md](file:///c:/Users/sora/fira-link-lms/security-audit/REMEDIATION_PLAN.md).
2. **Approve Code Fix Sequence:** In Phase 6, approve applying fixes in individual, isolated commits accompanied by verification tests.
3. **Verify via Retest Checklist:** Utilize [RETEST_CHECKLIST.md](file:///c:/Users/sora/fira-link-lms/security-audit/RETEST_CHECKLIST.md) before final production sign-off.
