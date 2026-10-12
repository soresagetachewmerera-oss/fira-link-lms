<?php
require __DIR__ . '/../Eduex Laravel/Eduex Laravel/vendor/autoload.php';
$app = require_once __DIR__ . '/../Eduex Laravel/Eduex Laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('role', 'admin')->first();
if (!$user) {
    echo "No admin user found.\n";
    exit(1);
}

auth()->login($user);
$request = Illuminate\Http\Request::create('/admin/settings/sms', 'GET');
$response = $app->handle($request);

echo "HTTP Status: " . $response->getStatusCode() . "\n";
echo "Response Length: " . strlen($response->getContent()) . "\n";
$requestIndex = Illuminate\Http\Request::create('/admin/settings', 'GET');
$responseIndex = $app->handle($requestIndex);
echo "Settings Index Status: " . $responseIndex->getStatusCode() . "\n";
if (str_contains($responseIndex->getContent(), 'SMS Ethiopia')) {
    echo "SUCCESS: Main Settings Index includes 'SMS Ethiopia & OTP' card!\n";
} else {
    echo "FAILED: Card not found on index.\n";
}
