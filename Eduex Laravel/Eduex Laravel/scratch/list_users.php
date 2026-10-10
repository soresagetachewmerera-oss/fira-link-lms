<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = App\Models\User::all(['id', 'email', 'name', 'role']);
foreach ($users as $u) {
    if ($u->email === 'student@firalink.com' || $u->email === 'student@eduex.com') {
        $u->password = bcrypt('12345678');
        $u->save();
        echo "Updated password for {$u->email}\n";
    }
    echo "ID: {$u->id} | Email: {$u->email} | Name: {$u->name} | Role: {$u->role}\n";
}
