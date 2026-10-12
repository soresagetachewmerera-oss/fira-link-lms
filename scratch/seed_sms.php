<?php
require __DIR__ . '/../Eduex Laravel/Eduex Laravel/vendor/autoload.php';
$app = require_once __DIR__ . '/../Eduex Laravel/Eduex Laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (App\Models\SmsTemplate::defaultTemplates() as $template) {
    App\Models\SmsTemplate::updateOrCreate(
        ['key' => $template['key']],
        $template
    );
}

echo "Successfully seeded SMS templates! Total count: " . App\Models\SmsTemplate::count() . PHP_EOL;
foreach (App\Models\SmsTemplate::all() as $t) {
    echo "- [{$t->key}] {$t->name}: {$t->content}" . PHP_EOL;
}
