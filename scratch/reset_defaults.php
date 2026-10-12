<?php
require __DIR__ . '/../Eduex Laravel/Eduex Laravel/vendor/autoload.php';
$app = require_once __DIR__ . '/../Eduex Laravel/Eduex Laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tpl = App\Models\SmsTemplate::where('key', 'otp')->first();
if ($tpl) {
    $tpl->content = 'Your {app_name} verification code is: {otp}. It expires in {expiry} minutes. Do not share this code with anyone.';
    $tpl->save();
}

settings()->set('sms.provider', [
    'enabled'   => true,
    'api_url'   => 'https://smsethiopia.com/api/sms/send',
    'api_key'   => env('SMS_ETHIOPIA_API_KEY', ''),
    'sender_id' => 'FiraLink',
    'timeout'   => 15,
], 'sms', 'array');

settings()->set('sms.otp', [
    'length'                    => 6,
    'expiry_minutes'            => 10,
    'resend_cooldown_seconds'   => 60,
    'max_attempts'              => 5,
    'enable_login'              => true,
    'enable_phone_verification' => true,
    'enable_password_reset'     => true,
], 'sms', 'array');

echo "Defaults restored successfully!\n";
