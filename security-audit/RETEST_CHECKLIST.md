# Retest & Fix Verification Checklist
**Target System:** Fira Link Academy  
**Purpose:** Precise, step-by-step verification procedures to confirm the complete resolution of every identified vulnerability without regression.  
**Audit Reference:** [SECURITY_AUDIT_REPORT.md](file:///c:/Users/sora/fira-link-lms/security-audit/SECURITY_AUDIT_REPORT.md)  

---

## 1. Verification Test Matrix

---

### RETEST-01: Verify Storage Path Traversal Fix (FL-CRIT-01)
- **Target Endpoint:** `GET /storage/{path}`
- **Test Command (PowerShell / cURL):**
  ```powershell
  Invoke-WebRequest -Uri "http://127.0.0.1:8000/storage/..%2F..%2F.env" -SkipHttpErrorCheck
  ```
- **Expected Result (Vulnerable):** HTTP 200 OK with `.env` file contents containing `APP_KEY`.
- **Expected Result (Fixed):** **HTTP 404 Not Found** with standard error page.
- **Pass Criteria:** Requests attempting relative directory traversal (`../` or `..\`) fail to access files outside `storage/app/public`.

---

### RETEST-02: Verify Video Streaming Traversal Fix (FL-CRIT-02)
- **Target Endpoint:** `GET /api/video/stream?path={path}`
- **Test Command:**
  ```powershell
  Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/video/stream?path=..%2F..%2F.env" -SkipHttpErrorCheck
  Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/video/stream?path=nonexistent_test" -SkipHttpErrorCheck
  ```
- **Expected Result (Vulnerable):**
  - Traversal returns HTTP 200 streaming `.env`.
  - Non-existent file outputs: `{"error": "File not found at C:\\Users\\...\\storage\\app\\nonexistent_test"}`.
- **Expected Result (Fixed):**
  - Traversal returns **HTTP 404 Not Found** with generic `{"error": "File not found."}`.
  - Zero internal filesystem paths disclosed in response bodies.
- **Pass Criteria:** Traversal blocked; internal absolute paths never disclosed.

---

### RETEST-03: Verify Outbound TLS Peer Verification (FL-CRIT-03)
- **Target Components:** `CourseEnrollmentController`, `SmsEthiopiaService`, `PaymentGatewayController`, `EventController`, `TourController`
- **Verification Command:**
  ```powershell
  Select-String -Path "Eduex Laravel\Eduex Laravel\app\*.php" -Pattern "CURLOPT_SSL_VERIFYPEER\s*,\s*false" -AllMatches
  ```
- **Expected Result (Vulnerable):** Over 35 matching code lines returned across controllers.
- **Expected Result (Fixed):** **0 occurrences returned**. All cURL instances configure `CURLOPT_SSL_VERIFYPEER => true` and `CURLOPT_SSL_VERIFYHOST => 2`, or utilize `Illuminate\Support\Facades\Http`.
- **Pass Criteria:** Zero disabled TLS verification options in production codebase.

---

### RETEST-04: Verify Removal of Leaked Merchant Secret (FL-CRIT-04)
- **Target Endpoint:** `POST /api/courses/{id}/enroll`
- **Test Command:**
  ```powershell
  $headers = @{ "Authorization" = "Bearer $STUDENT_TOKEN"; "Content-Type" = "application/json" }
  $body = @{ "payment_method" = "sslcommerz" } | ConvertTo-Json
  Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/courses/1/enroll" -Method Post -Headers $headers -Body $body
  ```
- **Expected Result (Vulnerable):** Response JSON includes `'store_password'` property with merchant password string.
- **Expected Result (Fixed):** Response JSON contains transaction ID, amount, and mode, but **strictly omits `'store_password'`**.
- **Pass Criteria:** Merchant secret credentials never appear in API responses.

---

### RETEST-05: Verify Role Authorization on Instructor API (FL-HIGH-01)
- **Target Endpoint:** `GET /api/instructor/dashboard`, `GET /api/instructor/earnings`
- **Test Command:**
  ```powershell
  # Authenticate with student credentials
  $loginRes = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/auth/login" -Method Post -Body (@{ email="student@eduex.com"; password="password" } | ConvertTo-Json) -ContentType "application/json"
  $studentToken = $loginRes.token

  # Attempt calling instructor API using student token
  Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/instructor/dashboard" -Headers @{ "Authorization" = "Bearer $studentToken" } -SkipHttpErrorCheck
  ```
- **Expected Result (Vulnerable):** HTTP 200 OK (student successfully accesses instructor dashboard).
- **Expected Result (Fixed):** **HTTP 403 Forbidden** with `{"message": "Unauthorized. Instructor role required."}`.
- **Pass Criteria:** Non-instructor tokens are rejected with HTTP 403 on all `/api/instructor/*` endpoints.

---

### RETEST-06: Verify Student Profile IDOR & PII Masking Fix (FL-HIGH-02)
- **Target Endpoint:** `GET /api/instructor/students/{id}`
- **Test Command:**
  ```powershell
  # Instructor token requests a student NOT enrolled in their courses
  Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/instructor/students/9999" -Headers @{ "Authorization" = "Bearer $INSTRUCTOR_TOKEN" } -SkipHttpErrorCheck
  ```
- **Expected Result (Vulnerable):** HTTP 200 OK returning user data with raw phone number.
- **Expected Result (Fixed):** **HTTP 403 Forbidden** (`"Student not enrolled in your courses"`) or **HTTP 404 Not Found**. Any authorized student view masks phone numbers (`+251 91 ****567`).
- **Pass Criteria:** No cross-instructor student PII access; raw telephone numbers never returned.

---

### RETEST-07: Verify Discussion Forum & Assignment XSS Fix (FL-HIGH-03)
- **Target Views:** `forum-show.blade.php`, `assignment-content.blade.php`
- **Test Procedure:**
  1. Post a forum reply containing: `SafeText<b>Bold</b><script>alert('XSS')</script>`.
  2. Load the discussion thread in a web browser.
- **Expected Result (Vulnerable):** JavaScript popup executes or `<script>` tag is rendered unescaped in DOM.
- **Expected Result (Fixed):** Script tags are rendered as plain escaped text (`&lt;script&gt;alert('XSS')&lt;/script&gt;`). No script executes.
- **Pass Criteria:** DOM tree shows fully sanitized and HTML-encoded output.

---

### RETEST-08: Verify Assignment Upload Restriction (FL-HIGH-04)
- **Target Endpoint:** `POST /api/user/courses/{course}/assignments/{assignment}/submit`
- **Test Procedure:**
  1. Send multipart POST request with a test file named `test_exploit.php`.
  2. Send multipart POST request with a legitimate file named `homework.pdf`.
- **Expected Result (Vulnerable):** `test_exploit.php` upload succeeds with HTTP 200.
- **Expected Result (Fixed):** `test_exploit.php` fails with **HTTP 422 Unprocessable Content** (`"The file field must be a file of type: pdf, doc, docx, zip, txt."`). Valid PDF succeeds.
- **Pass Criteria:** Only safe whitelisted document MIME types/extensions are accepted.

---

### RETEST-09: Verify AI Chat Isolation & Rate Limiting (FL-HIGH-05)
- **Target Endpoints:** `GET /api/fira-ai`, `POST /api/fira-ai/send`
- **Test Command:**
  ```powershell
  # Unauthenticated request to read chat history
  Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/fira-ai"
  ```
- **Expected Result (Vulnerable):** Returns chat history messages belonging to User ID 1 (Administrator).
- **Expected Result (Fixed):** Returns empty array (`[]`) or requires authentication.
- **Pass Criteria:** Anonymous visitors never receive administrative chat history; rate-limiting triggers HTTP 429 upon request bursts.

---

### RETEST-10: Verify Webhook Signature Enforcement (FL-HIGH-06)
- **Target Endpoint:** `POST /payment/chapa/webhook`
- **Test Command:**
  ```powershell
  # Send fake webhook payload with NO signature header
  $body = @{ "status" = "success"; "tx_ref" = "TEST_TX_123" } | ConvertTo-Json
  Invoke-WebRequest -Uri "http://127.0.0.1:8000/payment/chapa/webhook" -Method Post -Body $body -ContentType "application/json" -SkipHttpErrorCheck
  ```
- **Expected Result (Vulnerable):** HTTP 200 OK or continues processing.
- **Expected Result (Fixed):** **HTTP 401 Unauthorized** (`"Missing signature."`).
- **Pass Criteria:** Unsigned webhook payloads are rejected immediately.

---

### RETEST-11: Verify Authentication Rate Limiting (FL-MED-01)
- **Target Endpoint:** `POST /api/auth/login`
- **Test Command:**
  ```powershell
  1..15 | ForEach-Object {
    Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/auth/login" -Method Post -Body '{"email":"test@example.com","password":"wrong"}' -ContentType "application/json" -SkipHttpErrorCheck | Select-Object StatusCode
  }
  ```
- **Expected Result (Vulnerable):** All 15 requests return HTTP 401.
- **Expected Result (Fixed):** First 5–10 requests return HTTP 401; subsequent requests return **HTTP 429 Too Many Requests**.
- **Pass Criteria:** Automated brute-force attacks are throttled by HTTP 429.

---

### RETEST-12: Verify Token Invalidation on Password Reset (FL-MED-04)
- **Target Flow:** `POST /api/auth/password/reset/otp`
- **Test Procedure:**
  1. Capture active Sanctum Bearer token for user A.
  2. Execute password reset for user A.
  3. Attempt calling `GET /api/user` with the original pre-reset token.
- **Expected Result (Vulnerable):** HTTP 200 OK (pre-reset token remains active).
- **Expected Result (Fixed):** **HTTP 401 Unauthenticated** (pre-reset token was revoked).
- **Pass Criteria:** Password reset terminates all active API tokens for that user.

---

### RETEST-13: Verify Mobile Secure Token Storage (FL-MED-02)
- **Target Flow:** Flutter mobile client
- **Test Procedure:**
  1. Build Android debug/release APK.
  2. Log in with user credentials.
  3. Inspect `/data/data/com.example.eduex_app/shared_prefs/FlutterSharedPreferences.xml`.
  4. Attempt `adb backup com.example.eduex_app`.
- **Expected Result (Vulnerable):** `auth_token` visible in cleartext XML; backup file extracts application state.
- **Expected Result (Fixed):** `auth_token` is not in XML (stored in Android Keystore via EncryptedSharedPreferences); `adb backup` refuses backup due to `android:allowBackup="false"`.
- **Pass Criteria:** Mobile credentials protected against extraction on disk and via backup channels.
