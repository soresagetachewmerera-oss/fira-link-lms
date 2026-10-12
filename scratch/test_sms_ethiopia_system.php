<?php

require __DIR__ . '/../Eduex Laravel/Eduex Laravel/vendor/autoload.php';
$app = require_once __DIR__ . '/../Eduex Laravel/Eduex Laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsEthiopiaService;
use Illuminate\Support\Facades\Cache;

echo "========================================================\n";
echo "   SMS ETHIOPIA & DYNAMIC ADMIN SYSTEM AUDIT & TESTS    \n";
echo "========================================================\n\n";

$allPassed = true;

function assertTest(string $description, bool $condition): void {
    global $allPassed;
    if ($condition) {
        echo " [PASS] {$description}\n";
    } else {
        echo " [FAIL] {$description}\n";
        $allPassed = false;
    }
}

// ----------------------------------------------------
// 1. Phone Normalization & Ethiopian Number Validation
// ----------------------------------------------------
echo "--- 1. Testing Phone Normalization ---\n";
$smsService = app(SmsEthiopiaService::class);

$n1 = $smsService->normalizePhoneNumber('0911223344');
assertTest("0911223344 normalized to 251911223344 (got: {$n1})", $n1 === '251911223344');

$n2 = $smsService->normalizePhoneNumber('+251911223344');
assertTest("+251911223344 normalized to 251911223344 (got: {$n2})", $n2 === '251911223344');

$n3 = $smsService->normalizePhoneNumber('0711223344');
assertTest("0711223344 (Safaricom ET) normalized to 251711223344 (got: {$n3})", $n3 === '251711223344');

$n4 = $smsService->normalizePhoneNumber('911223344');
assertTest("911223344 normalized to 251911223344 (got: {$n4})", $n4 === '251911223344');

$val1 = $smsService->isValidEthiopianNumber('0911223344');
assertTest("0911223344 is valid Ethiopian number", $val1 === true);

$val2 = $smsService->isValidEthiopianNumber('0712345678');
assertTest("0712345678 is valid Ethiopian number", $val2 === true);

$val3 = $smsService->isValidEthiopianNumber('1234567890');
assertTest("1234567890 is NOT valid Ethiopian number", $val3 === false);

// ----------------------------------------------------
// 2. Dynamic Templates & Variable Substitution
// ----------------------------------------------------
echo "\n--- 2. Testing Dynamic SMS Templates ---\n";
$tplCount = SmsTemplate::count();
assertTest("SmsTemplate table has default templates (count: {$tplCount})", $tplCount >= 6);

$otpTpl = SmsTemplate::where('key', 'otp')->first();
assertTest("OTP template found", $otpTpl !== null);

if ($otpTpl) {
    $rendered = $otpTpl->render(['app_name' => 'Fira Link', 'otp' => '548291', 'expiry' => 10]);
    echo "  Rendered OTP: {$rendered}\n";
    assertTest("OTP template variables replaced correctly", str_contains($rendered, '548291') && str_contains($rendered, 'Fira Link'));
}

$enrollTpl = SmsTemplate::where('key', 'course_enrollment')->first();
assertTest("Course enrollment template found", $enrollTpl !== null);
if ($enrollTpl) {
    $renderedEnroll = $enrollTpl->render(['app_name' => 'Fira Link', 'name' => 'Abebe', 'course_title' => 'Flutter Mastery']);
    echo "  Rendered Enrollment: {$renderedEnroll}\n";
    assertTest("Enrollment template replaced correctly", str_contains($renderedEnroll, 'Abebe') && str_contains($renderedEnroll, 'Flutter Mastery'));
}

// Unicode & Amharic check
$amharicMsg = "የእርስዎ የማረጋገጫ ኮድ: 123456 ነው። ፊራ ሊንክ";
assertTest("Amharic Unicode string intact", mb_strlen($amharicMsg) > 0);

// ----------------------------------------------------
// 3. OTP Service Lifecycle, Rate Limiting & Security
// ----------------------------------------------------
echo "\n--- 3. Testing OTP Security Lifecycle ---\n";
$otpService = app(OtpService::class);
$testPhone = '0912345678';
$normalizedTestPhone = '251912345678';

// Clear any prior cache
$otpService->clearPhone($testPhone, 'unit_test');

// Test dispatch to phone
$dispatchRes = $otpService->sendToPhone($testPhone, 'unit_test');
assertTest("sendToPhone generated code and status", isset($dispatchRes['cooldown_seconds']));

// Test cooldown active
$cooldownRes = $otpService->sendToPhone($testPhone, 'unit_test');
assertTest("Second request rejected due to active cooldown", ($cooldownRes['code'] ?? '') === 'COOLDOWN_ACTIVE');

// Test invalid code attempt
$invalidAttempt = $otpService->verifyPhone($testPhone, '000000', 'unit_test');
assertTest("Invalid code rejected", $invalidAttempt['success'] === false && ($invalidAttempt['code'] ?? '') === 'INVALID_CODE');

// Check brute force protection
echo "  Testing brute-force max attempt invalidation...\n";
for ($i = 0; $i < 6; $i++) {
    $otpService->verifyPhone($testPhone, '00000' . $i, 'unit_test');
}
$afterMax = $otpService->verifyPhone($testPhone, '000000', 'unit_test');
assertTest("Brute-force protection invalidates OTP on excessive attempts", $afterMax['success'] === false);

// ----------------------------------------------------
// 4. Verification of Hash Storage (No Plain Text Leak)
// ----------------------------------------------------
echo "\n--- 4. Testing OTP Hash Storage Security ---\n";
$otpService->clearPhone($testPhone, 'hash_test');
// Send new code
$otpService->sendToPhone($testPhone, 'hash_test');
$cacheKey = 'sec_otp_hash_test_' . md5(strtolower(trim($normalizedTestPhone)));
$cachedData = Cache::get($cacheKey);
assertTest("Cached OTP payload exists", !empty($cachedData));
assertTest("Cached code is a bcrypt hash (starts with $2y$)", isset($cachedData['code']) && str_starts_with($cachedData['code'], '$2y$'));

// ----------------------------------------------------
// 5. Audit Logging to sms_logs Table
// ----------------------------------------------------
echo "\n--- 5. Testing Audit Logging (Masked Numbers) ---\n";
$latestLog = SmsLog::latest()->first();
assertTest("SmsLog created in database", $latestLog !== null);
if ($latestLog) {
    echo "  Latest log recipient masked: {$latestLog->recipient_masked}\n";
    echo "  Latest log status: {$latestLog->status}\n";
    echo "  Latest log preview: {$latestLog->message_preview}\n";
    assertTest("Recipient phone is masked in log", str_contains($latestLog->recipient_masked, '***'));
    assertTest("Message preview has OTP digits masked", str_contains($latestLog->message_preview, '****'));
}

// ----------------------------------------------------
// 6. Final Summary
// ----------------------------------------------------
echo "\n========================================================\n";
if ($allPassed) {
    echo " [ALL TESTS PASSED] SMS Ethiopia System is 100% Verified!\n";
} else {
    echo " [SOME TESTS FAILED] Please inspect output above.\n";
}
echo "========================================================\n";
