<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tour;
use App\Models\Event;
use App\Models\User;
use App\Models\TourBooking;
use App\Models\EventBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

echo "=== FIRALINK TOURS & EVENTS VERIFICATION TEST ===\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($condition, $testName) {
    global $passCount, $failCount;
    if ($condition) {
        echo " [PASS] $testName\n";
        $passCount++;
    } else {
        echo " [FAIL] $testName\n";
        $failCount++;
    }
}

// 1. Verify User exists for test
$user = User::first();
if (!$user) {
    $user = User::factory()->create([
        'name' => 'Test Coffee Buyer',
        'email' => 'buyer@testfiralink.com',
        'password' => bcrypt('password123'),
        'role' => 'student',
    ]);
}
Auth::login($user);
echo "Acting as User: {$user->name} ({$user->email})\n\n";

// ==========================================
// 2. TOURS TEST
// ==========================================
echo "--- TESTING TOURS ---\n";

$paidTour = Tour::where('price', '>', 0)->first();
assertTest($paidTour !== null, "Found at least one paid tour in database (ID: {$paidTour->id}, Price: {$paidTour->price})");

// Test Tour Images
assertTest(!empty($paidTour->featured_image) || !empty($paidTour->thumbnail), "Tour has image paths configured");
$tourImagePath = public_path('storage/' . ($paidTour->featured_image ?? $paidTour->thumbnail));
assertTest(file_exists($tourImagePath) || file_exists(storage_path('app/public/' . ($paidTour->featured_image ?? $paidTour->thumbnail))), "Tour image file actually exists on filesystem ($tourImagePath)");

// Test A: Attempt to book paid tour with 'free' payment method via Front Controller logic
$tourController = app(\App\Http\Controllers\Front\TourController::class);

$fakeRequestFreeOnPaid = Request::create(route('tours.book.process', $paidTour->slug), 'POST', [
    'first_name' => 'Abebe',
    'last_name' => 'Bikila',
    'email' => 'abebe@testfiralink.com',
    'phone' => '+251911223344',
    'participants' => 1,
    'payment_method' => 'free',
]);

$response = null;
try {
    $response = $tourController->processBooking($fakeRequestFreeOnPaid, $paidTour->slug);
    $failedFreeOnPaid = false;
    if ($response->isRedirection()) {
        if (session('error') && str_contains(session('error'), 'requires payment')) {
            $failedFreeOnPaid = true;
        }
    }
    assertTest($failedFreeOnPaid, "Paid Tour booking with 'free' payment method is BLOCKED with error message: '" . session('error') . "'");
} catch (\Exception $e) {
    assertTest(str_contains($e->getMessage(), 'requires payment'), "Paid Tour booking with 'free' payment method threw exception: " . $e->getMessage());
}

// Test B: API Tour Controller validation on paid tour with 'free' method
$apiTourController = app(\App\Http\Controllers\Api\TourController::class);
$apiReqFreeOnPaid = Request::create('/api/tours/' . $paidTour->id . '/book', 'POST', [
    'first_name' => 'API',
    'last_name' => 'Buyer',
    'email' => 'buyer@api.com',
    'phone' => '+251911000111',
    'participants' => 1,
    'payment_method' => 'free',
]);
$apiResponse = $apiTourController->book($apiReqFreeOnPaid, $paidTour->id);
$apiData = json_decode($apiResponse->getContent(), true);
assertTest($apiResponse->getStatusCode() === 422 && isset($apiData['message']) && str_contains($apiData['message'], 'requires payment'), "API blocks paid tour with 'free' method returning 422: " . ($apiData['message'] ?? ''));

// Clear session state
session()->flush();

// ==========================================
// 3. EVENTS TEST
// ==========================================
echo "\n--- TESTING EVENTS ---\n";

$paidEvent = Event::where('price', '>', 0)->first();
assertTest($paidEvent !== null, "Found paid event (ID: {$paidEvent->id}, Title: '{$paidEvent->title}', Price: {$paidEvent->price})");

$freeEvent = Event::where('price', '<=', 0)->orWhereNull('price')->first();
assertTest($freeEvent !== null, "Found free event (ID: {$freeEvent->id}, Title: '{$freeEvent->title}', Price: " . ($freeEvent->price ?? 0) . ")");

// Test Event Images
assertTest(!empty($paidEvent->featured_image), "Event featured_image set to: {$paidEvent->featured_image}");
$eventImagePath = public_path('storage/' . $paidEvent->featured_image);
assertTest(file_exists($eventImagePath) || file_exists(storage_path('app/public/' . $paidEvent->featured_image)), "Event image file exists on filesystem ($eventImagePath)");

function createFormRequest($class, $url, array $data, $user, $app) {
    $request = $class::create($url, 'POST', $data);
    $request->setUserResolver(fn() => $user);
    $request->setContainer($app);
    $request->setRedirector(app('redirect'));
    $request->validateResolved();
    return $request;
}

// Test C: StoreEventBookingRequest validation and controller blocking 'free' on paid event
$eventController = app(\App\Http\Controllers\Front\EventController::class);

$reqFreeOnPaidEvent = createFormRequest(
    \App\Http\Requests\Event\StoreEventBookingRequest::class,
    route('events.book.store', $paidEvent->slug),
    [
        'first_name' => 'Abebe',
        'last_name' => 'Bikila',
        'email' => 'abebe@ethiopiacoffee.et',
        'phone' => '+251911334455',
        'seats' => 2,
        'payment_method' => 'free',
    ],
    $user,
    $app
);

$failedFreeEvent = false;
$respEvent = $eventController->storeBooking($reqFreeOnPaidEvent, $paidEvent->slug);
$eventErrMsg = '';
if ($respEvent->isRedirection()) {
    $errors = session('errors');
    if ($errors && $errors->has('payment_method')) {
        $eventErrMsg = $errors->first('payment_method');
        if (str_contains($eventErrMsg, 'requires a paid ticket')) {
            $failedFreeEvent = true;
        }
    }
}
assertTest($failedFreeEvent, "Paid Event booking with 'free' payment method is BLOCKED with error: '$eventErrMsg'");

// Test D: API Event Controller validation on paid event with 'free' method
$apiEventController = app(\App\Http\Controllers\Api\EventController::class);
$apiReqEventFree = createFormRequest(
    \App\Http\Requests\Event\StoreEventBookingRequest::class,
    '/api/events/' . $paidEvent->id . '/book',
    [
        'first_name' => 'API',
        'last_name' => 'Cupper',
        'email' => 'cupper@test.com',
        'phone' => '+251911445566',
        'seats' => 1,
        'payment_method' => 'free',
    ],
    $user,
    $app
);
$apiEventResp = $apiEventController->book($apiReqEventFree, $paidEvent->id);
$apiEventData = json_decode($apiEventResp->getContent(), true);
assertTest($apiEventResp->getStatusCode() === 422 && isset($apiEventData['message']) && str_contains($apiEventData['message'], 'requires a paid ticket'), "API blocks paid event with 'free' method returning 422: " . ($apiEventData['message'] ?? ''));

// Test E: Booking a FREE Event succeeds immediately without payment
$reqBookFreeEvent = createFormRequest(
    \App\Http\Requests\Event\StoreEventBookingRequest::class,
    route('events.book.store', $freeEvent->slug),
    [
        'first_name' => 'Free',
        'last_name' => 'Attendee',
        'email' => 'free.attendee@firalink.com',
        'phone' => '+251922334455',
        'seats' => 1,
        'payment_method' => 'free',
    ],
    $user,
    $app
);
$respFreeBooking = $eventController->storeBooking($reqBookFreeEvent, $freeEvent->slug);

$createdFreeBooking = EventBooking::where('event_id', $freeEvent->id)
    ->where('email', 'free.attendee@firalink.com')
    ->latest()
    ->first();

assertTest($createdFreeBooking !== null, "Free event booking created in database (Booking Ref: {$createdFreeBooking->reference})");
assertTest($createdFreeBooking && $createdFreeBooking->status === EventBooking::STATUS_CONFIRMED, "Free event status is immediately 'confirmed'");
assertTest($createdFreeBooking && $createdFreeBooking->payment_status === EventBooking::PAYMENT_PAID, "Free event payment_status is 'paid' (complimentary/free)");
assertTest($createdFreeBooking && $createdFreeBooking->total_amount == 0, "Free event total_amount is 0.00");

// Test F: List sample real events created
echo "\n--- SAMPLE REAL INDUSTRY EVENTS IN DATABASE ---\n";
$events = Event::orderBy('start_date', 'asc')->get();
foreach ($events as $idx => $ev) {
    $typeStr = ($ev->isFree() || $ev->price <= 0) ? "FREE" : "PAID (" . number_format($ev->price, 2) . " " . ($ev->currency ?? 'ETB') . ")";
    $hasImg = file_exists(public_path('storage/' . $ev->featured_image)) ? "Image OK" : "No image";
    echo " [" . ($idx + 1) . "] {$ev->title}\n";
    echo "     Date: " . date('M d, Y', strtotime($ev->start_date)) . " | Venue: {$ev->location} | Access: $typeStr | [$hasImg]\n";
}

echo "\n--- SAMPLE TOURS IN DATABASE ---\n";
$tours = Tour::orderBy('price', 'desc')->get();
foreach ($tours as $idx => $tr) {
    $typeStr = ($tr->price <= 0) ? "FREE" : "PAID (" . number_format($tr->price, 2) . " ETB)";
    $hasImg = file_exists(public_path('storage/' . $tr->featured_image)) ? "Image OK" : "No image";
    echo " [" . ($idx + 1) . "] {$tr->title}\n";
    echo "     Region: {$tr->region} | Duration: {$tr->duration_days} days | Price: $typeStr | [$hasImg]\n";
}

echo "\n==========================================\n";
echo "SUMMARY: Passed: $passCount, Failed: $failCount\n";
echo "==========================================\n";

if ($failCount === 0) {
    echo "ALL TESTS PASSED SUCCESSFULLY! The payment enforcement and realistic sample data are verified.\n";
    exit(0);
} else {
    echo "SOME TESTS FAILED! Please check the output above.\n";
    exit(1);
}
