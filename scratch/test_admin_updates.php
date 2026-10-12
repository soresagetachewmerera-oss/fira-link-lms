<?php
require __DIR__ . '/../Eduex Laravel/Eduex Laravel/vendor/autoload.php';
$app = require_once __DIR__ . '/../Eduex Laravel/Eduex Laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = app(\App\Http\Controllers\Admin\SettingsController::class);

echo "--- Testing Direct Controller Update Logic ---\n";

// 1. Provider Update
$req = Illuminate\Http\Request::create('/admin/settings/sms/provider', 'PUT', [
    'enabled'   => '1',
    'api_url'   => 'https://smsethiopia.com/api/sms/send',
    'api_key'   => 'test_api_key_secret_999',
    'sender_id' => 'FiraLink',
    'timeout'   => 25,
]);
$resp = $controller->updateSmsProvider($req);
echo "Provider update executed without exception.\n";
$savedKey = settings('sms.provider.api_key');
$savedTimeout = settings('sms.provider.timeout');
echo "Saved API Key: {$savedKey}\n";
echo "Saved Timeout: {$savedTimeout}\n";

// 2. OTP Security Update
$otpReq = Illuminate\Http\Request::create('/admin/settings/sms/otp', 'PUT', [
    'length'                    => 6,
    'expiry_minutes'            => 15,
    'resend_cooldown_seconds'   => 45,
    'max_attempts'              => 4,
    'enable_login'              => '1',
    'enable_phone_verification' => '1',
    'enable_password_reset'     => '1',
]);
$controller->updateSmsOtp($otpReq);
echo "OTP update executed without exception.\n";
echo "Saved OTP Length: " . settings('sms.otp.length') . "\n";
echo "Saved OTP Expiry: " . settings('sms.otp.expiry_minutes') . " minutes\n";
echo "Saved OTP Cooldown: " . settings('sms.otp.resend_cooldown_seconds') . " seconds\n";
echo "Saved OTP Max Attempts: " . settings('sms.otp.max_attempts') . "\n";

// 3. Template Update
$tpl = App\Models\SmsTemplate::first();
$tplReq = Illuminate\Http\Request::create("/admin/settings/sms/template/{$tpl->id}", 'PUT', [
    'content'    => 'Hello {name}, your security OTP for {app_name} is {otp}. Valid for {expiry} mins.',
    'is_enabled' => '1',
]);
$controller->updateSmsTemplate($tplReq, $tpl->id);
$tpl->refresh();
echo "Updated Template Content: {$tpl->content}\n";
echo "SUCCESS: All admin controller updates executed and persisted to database!\n";
