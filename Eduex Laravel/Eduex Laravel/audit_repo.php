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

echo "\n=== SCANNING ETHIOPIC TEXT IN FRONTEND SETTINGS ===\n";
foreach (\App\Models\FrontendSetting::all() as $s) {
    $json = json_encode($s->value, JSON_UNESCAPED_UNICODE);
    if (preg_match('/[\x{1200}-\x{137F}]/u', $json)) {
        echo "Found in setting: " . $s->key . " -> " . $json . "\n";
    }
}

echo "\n=== SCANNING ETHIOPIC TEXT IN COURSES ===\n";
foreach (\App\Models\Course::all() as $c) {
    if (preg_match('/[\x{1200}-\x{137F}]/u', $c->title . ' ' . $c->description)) {
        echo "Found in course: " . $c->id . " -> " . $c->title . "\n";
    }
}
