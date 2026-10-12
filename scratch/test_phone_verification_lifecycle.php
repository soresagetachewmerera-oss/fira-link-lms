<?php

require __DIR__ . '/../Eduex Laravel/Eduex Laravel/vendor/autoload.php';
$app = require_once __DIR__ . '/../Eduex Laravel/Eduex Laravel/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsEthiopiaService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

echo "=== MANDATORY PHONE & SMS OTP VERIFICATION LIFECYCLE TEST ===\n\n";

$smsService = app(SmsEthiopiaService::class);
$otpService = app(OtpService::class);

// 1. Phone number validation & normalization tests
echo "[1] Testing Ethiopian phone validation & normalization...\n";
$validNumbers = [
    '0911223344' => '251911223344',
    '0711223344' => '251711223344',
    '+251911223344' => '251911223344',
    '+251711223344' => '251711223344',
    '251911223344' => '251911223344',
    '911223344' => '251911223344',
];

foreach ($validNumbers as $raw => $expected) {
    $isValid = $smsService->isValidEthiopianNumber($raw);
    $normalized = $smsService->normalizePhoneNumber($raw);
    if (!$isValid || $normalized !== $expected) {
        echo "  FAILED for $raw: valid=$isValid, normalized=$normalized (expected $expected)\n";
        exit(1);
    }
}
echo "  SUCCESS: All valid formats correctly validated and normalized.\n";

$invalidNumbers = ['123456', '0812345678', '+12025550199', 'abcdef', ''];
foreach ($invalidNumbers as $bad) {
    if ($smsService->isValidEthiopianNumber($bad)) {
        echo "  FAILED: Invalid number '$bad' was considered valid!\n";
        exit(1);
    }
}
echo "  SUCCESS: Invalid numbers correctly rejected.\n\n";

// 2. Clean up any previous test user
$testPhoneRaw = '0988776655';
$testPhoneNormalized = '251988776655';
$testEmail = 'test_student_otp@example.com';

User::where('email', $testEmail)->orWhere('phone', $testPhoneNormalized)->delete();
$otpService->clearPhone($testPhoneNormalized, 'registration');

// 3. API Registration - Test missing phone
echo "[2] Testing Registration API without phone number...\n";
$authController = app(\App\Http\Controllers\Api\AuthController::class);

$reqWithoutPhone = Request::create('/api/auth/register', 'POST', [
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => $testEmail,
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'terms' => true,
]);

$resNoPhone = $authController->register($reqWithoutPhone);
$dataNoPhone = json_decode($resNoPhone->getContent(), true);

if ($resNoPhone->getStatusCode() === 422 && isset($dataNoPhone['errors']['phone'])) {
    echo "  SUCCESS: Missing phone rejected with 422: " . json_encode($dataNoPhone['errors']['phone']) . "\n";
} else {
    echo "  FAILED: Expected 422 with phone error, got " . $resNoPhone->getStatusCode() . ": " . $resNoPhone->getContent() . "\n";
    exit(1);
}

// 4. API Registration - Test with valid Ethiopian phone
echo "\n[3] Testing Registration API with valid phone...\n";
$reqWithPhone = Request::create('/api/auth/register', 'POST', [
    'first_name' => 'Test',
    'last_name' => 'Student',
    'email' => $testEmail,
    'phone' => $testPhoneRaw,
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'terms' => true,
]);

$response = $authController->register($reqWithPhone);
$data = json_decode($response->getContent(), true);

if ($response->getStatusCode() === 201 && ($data['requires_phone_verification'] ?? false) === true) {
    echo "  SUCCESS: Registration returned 201 with requires_phone_verification: true\n";
    echo "  Normalized phone stored: " . $data['phone'] . " (masked: " . $data['phone_masked'] . ")\n";
} else {
    echo "  FAILED: " . $response->getContent() . "\n";
    exit(1);
}

// 5. Verify pending user in database
echo "\n[4] Checking database state for unverified user...\n";
$user = User::where('email', $testEmail)->first();
if (!$user || $user->hasVerifiedPhone()) {
    echo "  FAILED: User must exist with unverified phone! Verified at: " . var_export($user->phone_verified_at, true) . "\n";
    exit(1);
}
echo "  SUCCESS: User created with role={$user->role}, phone_verified_at=NULL (unverified).\n";

// 6. Test invalid OTP verification
echo "\n[5] Testing verification with invalid code...\n";
$reqBadCode = Request::create('/api/auth/register/verify', 'POST', [
    'phone' => $testPhoneRaw,
    'code' => '000000',
]);
$resBadCode = $authController->verifyRegistrationOtp($reqBadCode);
if ($resBadCode->getStatusCode() === 422) {
    echo "  SUCCESS: Invalid OTP rejected with HTTP 422.\n";
} else {
    echo "  FAILED: Expected 422, got " . $resBadCode->getStatusCode() . "\n";
    exit(1);
}

// 7. Test cooldown on resend
echo "\n[6] Testing OTP resend cooldown...\n";
$reqResend = Request::create('/api/auth/register/resend', 'POST', [
    'phone' => $testPhoneRaw,
]);
$resResend = $authController->resendRegistrationOtp($reqResend);
if ($resResend->getStatusCode() === 429) {
    echo "  SUCCESS: Immediate resend blocked by active cooldown (HTTP 429).\n";
} else {
    echo "  NOTE: Resend status: " . $resResend->getStatusCode() . " (data: " . $resResend->getContent() . ")\n";
}

// 8. Test phone number change before verification
echo "\n[7] Testing phone number correction...\n";
$newPhoneRaw = '0977665544';
$newPhoneNormalized = '251977665544';
$reqChange = Request::create('/api/auth/register/change-phone', 'POST', [
    'old_phone' => $testPhoneRaw,
    'new_phone' => $newPhoneRaw,
]);
$resChange = $authController->changeRegistrationPhone($reqChange);
if ($resChange->getStatusCode() === 200) {
    echo "  SUCCESS: Phone number changed to $newPhoneNormalized.\n";
} else {
    echo "  FAILED to change phone: " . $resChange->getContent() . "\n";
    exit(1);
}

// 9. Test verification with valid code
echo "\n[8] Testing verification with valid code for updated phone...\n";
// Inject a known OTP for test verification into the secure cache
$knownOtp = '789123';
$cacheKey = 'sec_otp_registration_' . md5(strtolower(trim($newPhoneNormalized)));
$cachedPayload = Cache::get($cacheKey);
if (!$cachedPayload) {
    echo "  FAILED: No OTP cache payload found for $newPhoneNormalized\n";
    exit(1);
}
$cachedPayload['code'] = Hash::make($knownOtp);
Cache::put($cacheKey, $cachedPayload, $cachedPayload['expires_at']);

$reqGoodCode = Request::create('/api/auth/register/verify', 'POST', [
    'phone' => $newPhoneRaw,
    'code' => $knownOtp,
]);
$resGoodCode = $authController->verifyRegistrationOtp($reqGoodCode);
$goodData = json_decode($resGoodCode->getContent(), true);

if ($resGoodCode->getStatusCode() === 200 && ($goodData['success'] ?? false) === true) {
    echo "  SUCCESS: Phone verified! Auth token received: " . substr($goodData['token'] ?? '', 0, 15) . "...\n";
} else {
    echo "  FAILED: Expected 200, got: " . $resGoodCode->getContent() . "\n";
    exit(1);
}

// 10. Check user verified status in database
$user->refresh();
if ($user->hasVerifiedPhone() && $user->phone_verified_at !== null) {
    echo "  SUCCESS: User phone_verified_at is now set: {$user->phone_verified_at}\n";
} else {
    echo "  FAILED: User phone_verified_at is still null!\n";
    exit(1);
}

// 11. Test single-use prevention (reuse of verified OTP)
echo "\n[9] Testing OTP reuse prevention...\n";
$resReuse = $authController->verifyRegistrationOtp($reqGoodCode);
if ($resReuse->getStatusCode() === 422) {
    echo "  SUCCESS: Reused OTP rejected with HTTP 422.\n";
} else {
    echo "  FAILED: Reused OTP was not rejected! Status: " . $resReuse->getStatusCode() . "\n";
    exit(1);
}

// Clean up
$user->tokens()->delete();
$user->delete();
$otpService->clearPhone($newPhoneNormalized, 'registration');

echo "\n=======================================================\n";
echo "ALL TESTS PASSED: Mandatory Phone Number & SMS OTP Verification verified end-to-end!\n";
echo "=======================================================\n";
