<?php
require __DIR__ . '/../Eduex Laravel/Eduex Laravel/vendor/autoload.php';
$app = require_once __DIR__ . '/../Eduex Laravel/Eduex Laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

App\Models\User::where('role', 'admin')->whereNull('phone_verified_at')->update(['phone_verified_at' => now()]);
echo "Existing admins marked as verified.\n";
