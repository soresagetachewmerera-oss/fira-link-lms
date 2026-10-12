<?php
require_once __DIR__ . '/../Eduex Laravel/Eduex Laravel/vendor/autoload.php';
$app = require_once __DIR__ . '/../Eduex Laravel/Eduex Laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$settings = app(\App\Services\SettingsRepository::class);
$config = $settings->get('ai.config', []);
$config['gemini_model'] = 'gemini-flash-latest';
$settings->set('ai.config', $config, 'ai', 'json');

echo "Successfully updated configured model to: " . $settings->get('ai.config.gemini_model') . "\n";

// Test GeminiService directly:
$gemini = app(\App\Services\GeminiService::class);
$response = $gemini->generateContent("Hello, who are you?");
echo "Test Response:\n" . $response . "\n";
