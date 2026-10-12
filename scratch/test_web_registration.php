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

echo "=== MANDATORY PHONE & SMS OTP WEB FLOW TEST ===\n\n";

$testPhoneRaw = '0966554433';
$testPhoneNormalized = '251966554433';
$testEmail = 'web_student_test@example.com';

User::where('email', $testEmail)->orWhere('phone', $testPhoneNormalized)->delete();
$otpService = app(OtpService::class);
$otpService->clearPhone($testPhoneNormalized, 'registration');

// 1. Web registration request
echo "[1] Submitting Web Registration Form...\n";
$registerController = app(\App\Http\Controllers\Student\RegisterController::class);

$webReq = Request::create('/register', 'POST', [
    'first_name' => 'Web Test',
    'last_name' => 'Student',
    'email' => $testEmail,
    'phone' => $testPhoneRaw,
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'terms' => 'on',
]);

$session = app('session')->driver();
$webReq->setLaravelSession($session);

$webRes = $registerController->register($webReq);

if ($webRes->getStatusCode() === 302 && str_contains($webRes->getTargetUrl(), 'verify-phone')) {
    echo "  SUCCESS: Web registration redirected to: " . $webRes->getTargetUrl() . "\n";
} else {
    echo "  FAILED: Expected redirect to verify-phone, got status " . $webRes->getStatusCode() . " -> " . $webRes->getTargetUrl() . "\n";
    exit(1);
}

// 2. Check pending user created
$pendingUser = User::where('email', $testEmail)->first();
if (!$pendingUser || $pendingUser->hasVerifiedPhone()) {
    echo "  FAILED: User must exist unverified!\n";
    exit(1);
}
echo "  SUCCESS: Pending user created with phone_verified_at = NULL.\n";

// 3. Test middleware blocking unverified user
echo "\n[2] Testing EnsurePhoneIsVerified Middleware on protected route...\n";
$middleware = new \App\Http\Middleware\EnsurePhoneIsVerified();
$protectedReq = Request::create('/student/dashboard', 'GET');
$protectedReq->setUserResolver(fn () => $pendingUser);

$middlewareRes = $middleware->handle($protectedReq, function ($req) {
    return new \Illuminate\Http\Response('ALLOWED');
});

if ($middlewareRes->getStatusCode() === 302 && str_contains($middlewareRes->getTargetUrl(), 'verify-phone')) {
    echo "  SUCCESS: Unverified user blocked from protected dashboard and redirected to verify-phone.\n";
} else {
    echo "  FAILED: Unverified user was not redirected: " . $middlewareRes->getStatusCode() . "\n";
    exit(1);
}

// 4. Test Web PhoneVerificationController notice view
echo "\n[3] Testing PhoneVerificationController notice render...\n";
$verifyController = app(\App\Http\Controllers\Auth\PhoneVerificationController::class);
$noticeReq = Request::create('/verify-phone', 'GET');
$noticeReq->setLaravelSession($session);
$session->put('pending_verification_user_id', $pendingUser->id);
$session->put('pending_verification_phone', $testPhoneNormalized);

$noticeRes = $verifyController->notice($noticeReq);
if ($noticeRes instanceof \Illuminate\Contracts\View\View) {
    echo "  SUCCESS: Notice view rendered successfully: " . $noticeRes->name() . "\n";
} else {
    echo "  FAILED: Notice view failed\n";
    exit(1);
}

// 5. Test Web OTP Verification submission with correct code
echo "\n[4] Testing Web OTP code verification...\n";
$knownWebOtp = '654321';
$cacheKey = 'sec_otp_registration_' . md5(strtolower(trim($testPhoneNormalized)));
$cachedPayload = Cache::get($cacheKey);
if (!$cachedPayload) {
    echo "  FAILED: No OTP cache payload found for $testPhoneNormalized\n";
    exit(1);
}
$cachedPayload['code'] = Hash::make($knownWebOtp);
Cache::put($cacheKey, $cachedPayload, $cachedPayload['expires_at']);

$submitReq = Request::create('/verify-phone', 'POST', [
    'code' => $knownWebOtp,
]);
$submitReq->setLaravelSession($session);

$submitRes = $verifyController->verify($submitReq);
if ($submitRes->getStatusCode() === 302 && str_contains($submitRes->getTargetUrl(), 'student')) {
    echo "  SUCCESS: Verification successful, redirected to: " . $submitRes->getTargetUrl() . "\n";
} else {
    echo "  FAILED: Expected redirect to student dashboard, got " . $submitRes->getStatusCode() . " -> " . $submitRes->getTargetUrl() . "\n";
    exit(1);
}

// 6. Confirm phone is now verified
$pendingUser->refresh();
if ($pendingUser->hasVerifiedPhone()) {
    echo "  SUCCESS: User is now marked verified in database: " . $pendingUser->phone_verified_at . "\n";
} else {
    echo "  FAILED: User phone_verified_at is still null!\n";
    exit(1);
}

// 7. Test middleware now allows access
$allowedRes = $middleware->handle($protectedReq, function ($req) {
    return new \Illuminate\Http\Response('ALLOWED');
});
if ($allowedRes->getContent() === 'ALLOWED') {
    echo "  SUCCESS: Verified user successfully granted access to protected route!\n";
} else {
    echo "  FAILED: Verified user was blocked!\n";
    exit(1);
}

// Clean up
$pendingUser->delete();
$otpService->clearPhone($testPhoneNormalized, 'registration');

echo "\n=======================================================\n";
echo "WEB FLOW TESTS PASSED: Web Registration & Verification verified!\n";
echo "=======================================================\n";
