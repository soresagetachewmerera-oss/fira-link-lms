<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== BRANDING ASSETS ===\n";
$b = \App\Models\PlatformSetting::where('key', 'branding.assets')->first();
print_r($b ? $b->value : 'null');

echo "\n=== PLATFORM GENERAL ===\n";
$g = \App\Models\PlatformSetting::where('key', 'platform.general')->first();
print_r($g ? $g->value : 'null');
