<?php
require_once __DIR__ . '/../Eduex Laravel/Eduex Laravel/vendor/autoload.php';
$app = require_once __DIR__ . '/../Eduex Laravel/Eduex Laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$settings = app(\App\Services\SettingsRepository::class);
$config = $settings->get('ai.config', []);
$apiKey = $config['gemini_api_key'] ?? '';
echo "Configured model: " . ($config['gemini_model'] ?? 'none') . "\n";

$ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);
if (isset($data['models'])) {
    foreach ($data['models'] as $m) {
        if (in_array('generateContent', $m['supportedGenerationMethods'] ?? [])) {
            echo $m['name'] . "\n";
        }
    }
} else {
    echo "Error or no models: " . $res . "\n";
}
