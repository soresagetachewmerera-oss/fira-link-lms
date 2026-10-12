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

echo "=== INSTRUCTOR MANDATORY PHONE & SMS OTP VERIFICATION TEST ===\n\n";

$smsService = app(SmsEthiopiaService::class);
$otpService = app(OtpService::class);

$instructorEmail = 'instructor_otp_test@example.com';
$instructorPhoneRaw = '0922334455';
$instructorPhoneNormalized = '251922334455';

User::where('email', $instructorEmail)->orWhere('phone', $instructorPhoneNormalized)->delete();
$otpService->clearPhone($instructorPhoneNormalized, 'registration');

// 1. Test missing phone in API instructor registration
echo "[1] Testing Instructor API registration without phone...\n";
$instAuthController = app(\App\Http\Controllers\Api\Instructor\AuthController::class);

$reqNoPhone = Request::create('/api/instructor/register', 'POST', [
    'first_name' => 'Abebe',
    'last_name' => 'Bikila',
    'email' => $instructorEmail,
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'bio' => 'Experienced coffee agriculture lecturer with 10 years experience.',
    'years_experience' => '10',
    'specialization' => 'Coffee Agronomy',
    'previous_experience' => 'Haramaya University',
    'highest_degree' => 'Master of Science',
    'field_of_study' => 'Plant Breeding',
]);

$resNoPhone = $instAuthController->register($reqNoPhone);
$dataNoPhone = json_decode($resNoPhone->getContent(), true);

if ($resNoPhone->getStatusCode() === 422 && isset($dataNoPhone['errors']['phone'])) {
    echo "  SUCCESS: Missing phone rejected with 422: " . json_encode($dataNoPhone['errors']['phone']) . "\n";
} else {
    echo "  FAILED: Expected 422 for missing phone: " . $resNoPhone->getContent() . "\n";
    exit(1);
}

// 2. Test invalid Ethiopian phone
echo "\n[2] Testing Instructor API registration with invalid phone (e.g. 123456)...\n";
$photoFile = \Illuminate\Http\UploadedFile::fake()->image('profile.jpg', 200, 200);
$resumeFile = \Illuminate\Http\UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

$reqBadPhone = Request::create('/api/instructor/register', 'POST', [
    'first_name' => 'Abebe',
    'last_name' => 'Bikila',
    'email' => $instructorEmail,
    'phone' => '123456',
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'bio' => 'Experienced lecturer.',
    'years_experience' => '10',
    'specialization' => 'Coffee Agronomy',
    'previous_experience' => 'Haramaya University',
    'highest_degree' => 'Master of Science',
    'field_of_study' => 'Plant Breeding',
], [], [
    'profile_photo' => $photoFile,
    'resume' => $resumeFile,
]);

$resBadPhone = $instAuthController->register($reqBadPhone);
$dataBadPhone = json_decode($resBadPhone->getContent(), true);

if ($resBadPhone->getStatusCode() === 422 && isset($dataBadPhone['errors']['phone'])) {
    echo "  SUCCESS: Invalid phone rejected with 422: " . json_encode($dataBadPhone['errors']['phone']) . "\n";
} else {
    echo "  FAILED: Expected 422 for invalid phone: " . $resBadPhone->getContent() . "\n";
    exit(1);
}

// 3. Test Web Instructor Registration with valid Ethiopian phone
echo "\n[3] Testing Web Instructor registration with valid Ethiopian phone...\n";
$webInstController = app(\App\Http\Controllers\Instructor\RegisterController::class);

$photoFile = \Illuminate\Http\UploadedFile::fake()->image('profile.jpg', 200, 200);
$resumeFile = \Illuminate\Http\UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf');

$webReq = Request::create('/instructor/register', 'POST', [
    'first_name' => 'Abebe',
    'last_name' => 'Bikila',
    'email' => $instructorEmail,
    'phone' => $instructorPhoneRaw,
    'password' => 'password123',
    'password_confirmation' => 'password123',
    'bio' => 'Experienced lecturer in coffee cupping and quality inspection.',
    'years_experience' => '8',
    'specialization' => 'Coffee Cupping & Quality',
    'previous_experience' => 'Jimma Agricultural Research Center',
    'highest_degree' => 'BSc',
    'field_of_study' => 'Food Science',
    'terms' => 'on',
], [], [
    'profile_photo' => $photoFile,
    'resume' => $resumeFile,
]);

$session = app('session')->driver();
$webReq->setLaravelSession($session);

$webRes = $webInstController->register($webReq);

if ($webRes->getStatusCode() === 302 && str_contains($webRes->getTargetUrl(), 'verify-phone')) {
    echo "  SUCCESS: Instructor registration redirected to: " . $webRes->getTargetUrl() . "\n";
} else {
    echo "  FAILED: Expected redirect to verify-phone, got " . $webRes->getStatusCode() . " -> " . $webRes->getTargetUrl() . "\n";
    exit(1);
}

// 4. Verify unverified instructor in database
$instUser = User::where('email', $instructorEmail)->first();
if (!$instUser || $instUser->phone_verified_at !== null) {
    echo "  FAILED: Instructor user must be created with phone_verified_at = NULL!\n";
    exit(1);
}
echo "  SUCCESS: Instructor created with role=instructor, phone={$instUser->phone}, phone_verified_at=NULL (unverified).\n";

// 5. Test verifying instructor phone with OTP
echo "\n[4] Testing OTP verification for Instructor...\n";
$knownOtp = '852963';
$cacheKey = 'sec_otp_registration_' . md5(strtolower(trim($instructorPhoneNormalized)));
$cachedPayload = Cache::get($cacheKey);
if (!$cachedPayload) {
    echo "  FAILED: No OTP cache payload found for $instructorPhoneNormalized\n";
    exit(1);
}
$cachedPayload['code'] = Hash::make($knownOtp);
Cache::put($cacheKey, $cachedPayload, $cachedPayload['expires_at']);

$verifyController = app(\App\Http\Controllers\Auth\PhoneVerificationController::class);
$submitReq = Request::create('/verify-phone', 'POST', ['code' => $knownOtp]);
$submitReq->setLaravelSession($session);

$submitRes = $verifyController->verify($submitReq);
if ($submitRes->getStatusCode() === 302 && str_contains($submitRes->getTargetUrl(), 'login')) {
    echo "  SUCCESS: Instructor phone verified, redirected to login for review notice.\n";
} else {
    echo "  FAILED: Unexpected redirect: " . $submitRes->getStatusCode() . " -> " . $submitRes->getTargetUrl() . "\n";
    exit(1);
}

$instUser->refresh();
if ($instUser->hasVerifiedPhone()) {
    echo "  SUCCESS: Instructor is now verified: {$instUser->phone_verified_at}\n";
} else {
    echo "  FAILED: Instructor phone_verified_at is still null!\n";
    exit(1);
}

// Clean up
$instUser->delete();
$otpService->clearPhone($instructorPhoneNormalized, 'registration');

echo "\n=======================================================\n";
echo "INSTRUCTOR TESTS PASSED: Phone & SMS OTP verification enforced!\n";
echo "=======================================================\n";
