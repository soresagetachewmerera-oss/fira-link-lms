<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$bookings = App\Models\TourBooking::with('tour')->get();
foreach ($bookings as $b) {
    echo "ID: {$b->id} | Ref: {$b->reference} | Tour: {$b->tour->title} | Customer: {$b->first_name} {$b->last_name} ({$b->email}) | Amount: {$b->currency} {$b->total_amount} | Status: {$b->status} | Payment: {$b->payment_status}\n";
}
