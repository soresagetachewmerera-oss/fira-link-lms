<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$repo = app(\App\Services\SettingsRepository::class);
echo "=== PLATFORM.GENERAL ===\n";
print_r($repo->get('platform.general'));

echo "\n=== BRANDING.ASSETS ===\n";
print_r($repo->get('branding.assets'));

echo "\n=== CONTACT.DETAILS ===\n";
print_r($repo->get('contact.details'));
