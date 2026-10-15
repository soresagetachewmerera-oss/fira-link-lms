<?php
require __DIR__ . '/../Eduex Laravel/Eduex Laravel/vendor/autoload.php';
$app = require_once __DIR__ . '/../Eduex Laravel/Eduex Laravel/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== TOURS (" . App\Models\Tour::count() . ") ===\n";
foreach (App\Models\Tour::all() as $tour) {
    echo "#{$tour->id} [{$tour->slug}] {$tour->title}\n";
    echo "  Price: {$tour->price} {$tour->currency} | Seats: {$tour->total_seats}\n";
    echo "  Featured Image: {$tour->featured_image}\n";
    echo "  Gallery: " . json_encode($tour->gallery) . "\n";
}

echo "\n=== EVENTS (" . App\Models\Event::count() . ") ===\n";
foreach (App\Models\Event::all() as $event) {
    echo "#{$event->id} [{$event->slug}] {$event->title}\n";
    echo "  Price: {$event->price} | Total Seats: {$event->total_seats}\n";
    echo "  Featured Image: {$event->featured_image}\n";
}

echo "\n=== TOUR BOOKINGS (" . App\Models\TourBooking::count() . ") ===\n";
foreach (App\Models\TourBooking::all() as $tb) {
    echo "#{$tb->id} Ref: {$tb->reference} | Status: {$tb->status} | PayStatus: {$tb->payment_status} | Amount: {$tb->total_amount}\n";
}

echo "\n=== EVENT BOOKINGS (" . App\Models\EventBooking::count() . ") ===\n";
foreach (App\Models\EventBooking::all() as $eb) {
    echo "#{$eb->id} Ref: {$eb->reference} | Status: {$eb->status} | Seats: {$eb->seats}\n";
}
