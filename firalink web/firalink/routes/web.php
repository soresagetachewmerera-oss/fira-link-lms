<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\CoursesController;
use App\Http\Controllers\Front\CourseEnrollmentController;
use App\Http\Controllers\Front\EventController;
use App\Http\Controllers\Front\TourController;
use App\Http\Controllers\Front\EventBookingVerificationController;
use App\Http\Controllers\Front\ReviewController;
use App\Http\Controllers\Front\BlogController;
use App\Http\Controllers\Front\BlogCommentController;
use App\Http\Controllers\Front\AboutController;
use App\Http\Controllers\Front\ContactController;
use App\Http\Controllers\Front\InstructorController;
use App\Http\Controllers\Front\TestimonialController;
use App\Http\Controllers\Front\FaqController;
use App\Http\Controllers\Front\NewsletterController;
use App\Http\Controllers\Front\PageController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\BundleController;
use App\Http\Controllers\Front\CertificateVerificationController;
use App\Http\Controllers\Student\AiChatController;
use App\Models\CustomPage;

require __DIR__.'/install.php';

Route::get('/', HomeController::class)->name('home');

// Fira AI Public Routes - freely accessible by any web visitor or student
Route::get('/ai-chat', [AiChatController::class, 'publicIndex'])->name('ai-chat.public');
Route::post('/ai-chat/send', [AiChatController::class, 'sendMessage'])->name('ai-chat.send');

// Integrated Coffee B2B Marketplace Web Routes
Route::prefix('marketplace')->name('marketplace.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Front\MarketplaceWebController::class, 'index'])->name('index');
    Route::get('/suppliers', [\App\Http\Controllers\Front\MarketplaceWebController::class, 'suppliers'])->name('suppliers');
    Route::get('/suppliers/{slug}', [\App\Http\Controllers\Front\MarketplaceWebController::class, 'showSupplier'])->name('suppliers.show');
    Route::get('/listings/{slug}', [\App\Http\Controllers\Front\MarketplaceWebController::class, 'show'])->name('show');
    Route::get('/pricing', [\App\Http\Controllers\Front\MarketplaceWebController::class, 'pricing'])->name('pricing');

    // Protected Marketplace Actions (Authenticated & Role-Verified)
    Route::middleware(['auth'])->group(function () {
        Route::post('/subscribe', [\App\Http\Controllers\Front\MarketplaceWebController::class, 'subscribe'])->name('subscribe');
        Route::post('/cancel-subscription', [\App\Http\Controllers\Front\MarketplaceWebController::class, 'cancelSubscription'])->name('subscription.cancel');
        Route::post('/listings/{id}/save', [\App\Http\Controllers\Front\MarketplaceWebController::class, 'toggleSave'])->name('save');
        Route::get('/dashboard', [\App\Http\Controllers\Front\MarketplaceWebController::class, 'dashboard'])->name('dashboard');

        // Inquiries & Sample Requests
        Route::post('/listings/{id}/inquire', [\App\Http\Controllers\Front\MarketplaceWebController::class, 'inquire'])
            ->middleware('verified.role:buyer,broker,exporter,supplier')
            ->name('inquire');



        // Organization Verification & Compliance Center (Web)
        Route::get('/verification', [\App\Http\Controllers\Front\OrganizationVerificationWebController::class, 'index'])->name('verification.center');
        Route::post('/verification/submit', [\App\Http\Controllers\Front\OrganizationVerificationWebController::class, 'submitDetails'])->name('verification.submit');
        Route::post('/verification/upload-document', [\App\Http\Controllers\Front\OrganizationVerificationWebController::class, 'uploadDocument'])->middleware('sensitive')->name('verification.upload');
        Route::delete('/verification/documents/{id}', [\App\Http\Controllers\Front\OrganizationVerificationWebController::class, 'deleteDocument'])->name('verification.delete-document');
        Route::get('/verification/documents/{id}/download', [\App\Http\Controllers\VerificationDocumentDownloadController::class, 'download'])->name('verification.download');
    });
});

// Real-Time Coffee Trading Terminal & Market Prices
Route::get('/market-prices', [\App\Http\Controllers\Front\MarketPricesController::class, 'index'])->name('market-prices.index');
Route::get('/market-prices/chart-data', [\App\Http\Controllers\Front\MarketPricesController::class, 'chartData'])->name('market-prices.chart-data');
Route::get('/market-prices/live-tick', [\App\Http\Controllers\Front\MarketPricesController::class, 'liveTick'])->name('market-prices.live-tick');

// Free Business Tools routes
Route::get('/free-tools', [\App\Http\Controllers\Front\FreeToolsController::class, 'index'])->name('free-tools.index');
Route::get('/free-tools/market-data', [\App\Http\Controllers\Front\FreeToolsController::class, 'marketData'])->name('free-tools.market-data');
Route::get('/free-tools/coffee-prices', [\App\Http\Controllers\Front\FreeToolsController::class, 'coffeePricesData'])->name('free-tools.coffee-prices');
Route::get('/free-tools/coffee-prices/export', [\App\Http\Controllers\Front\FreeToolsController::class, 'exportCoffeePrices'])->name('free-tools.coffee-prices.export');
Route::post('/free-tools/ai-analysis', [\App\Http\Controllers\Front\FreeToolsController::class, 'aiAnalysis'])->name('free-tools.ai-analysis');
Route::post('/free-tools/calculate', [\App\Http\Controllers\Front\FreeToolsController::class, 'calculate'])->name('free-tools.calculate');
Route::post('/free-tools/save-calculation', [\App\Http\Controllers\Front\FreeToolsController::class, 'saveCalculation'])->name('free-tools.save-calculation');
Route::post('/free-tools/ask-advisor', [\App\Http\Controllers\Front\FreeToolsController::class, 'askAdvisor'])->name('free-tools.ask-advisor');

// Public seller check by Fira ID (slice 3): shareable page with link preview, noindex, rate-limited.
Route::middleware('throttle:public-seller')->group(function () {
    Route::get('/verify', [\App\Http\Controllers\Front\PublicSellerWebController::class, 'index'])->name('verify.index');
    Route::get('/verify/{firaId}', [\App\Http\Controllers\Front\PublicSellerWebController::class, 'show'])->name('verify.show');
    Route::get('/verify/{firaId}/qr.svg', [\App\Http\Controllers\Front\PublicSellerWebController::class, 'qr'])->name('verify.qr');
});
Route::match(['get', 'post'], '/verify-certificate', [CertificateVerificationController::class, 'verify'])->name('verify-certificate');

// Sitemap XML route
Route::get('/sitemap.xml', [\App\Http\Controllers\Front\SitemapController::class, 'index'])->name('sitemap');

// Programs & Pricing public routes
Route::get('/programs', [\App\Http\Controllers\Front\ProgramController::class, 'index'])->name('programs');
Route::get('/pricing', [\App\Http\Controllers\Front\PricingController::class, 'index'])->name('pricing');

// Legal routes (Privacy Policy & Terms of Service)
Route::get('/privacy', [\App\Http\Controllers\Front\LegalController::class, 'privacy'])->name('privacy');
Route::get('/terms', [\App\Http\Controllers\Front\LegalController::class, 'terms'])->name('terms');
Route::get('/privacy-policy', function () {
    return redirect('/privacy', 301);
});
Route::get('/terms-conditions', function () {
    return redirect('/terms', 301);
});

// About and contact routes
Route::get('/about', AboutController::class)->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->middleware('throttle:public-form')->name('contact.submit');

// Instructors routes
Route::get('/instructors', [InstructorController::class, 'index'])->name('instructors.index');
Route::get('/instructors/{instructor}', [InstructorController::class, 'show'])
    ->whereNumber('instructor')
    ->name('instructors.show');

// Testimonials and newsletter routes
Route::get('/testimonials', [TestimonialController::class, 'index'])->name('testimonials.index');
Route::get('/pages/{customPage:slug}', PageController::class)->name('pages.show');
Route::post('/newsletter/subscribe', [NewsletterController::class, 'store'])->middleware('throttle:public-form')->name('newsletter.subscribe');

// Custom pages routes
try {
    $customPageSlugs = CustomPage::query()->pluck('slug')->filter()->all();

    if (!empty($customPageSlugs)) {
        Route::get('/{customPage:slug}', PageController::class)
            ->whereIn('customPage', $customPageSlugs)
            ->name('pages.legacy');
    }
} catch (\Throwable $exception) {
        
}

// FAQ routes
Route::get('/faq', FaqController::class)->name('faq');

// Courses routes

Route::get('/courses', [CoursesController::class, 'index'])->name('courses');
Route::get('/courses/{course}', [CoursesController::class, 'show'])->name('courses.show');

// Bundle public routes
Route::get('/bundles', [BundleController::class, 'index'])->name('bundles.index');
Route::get('/bundles/{id}', [BundleController::class, 'show'])->name('bundles.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::post('/blog/{slug}/comments', [BlogCommentController::class, 'store'])->middleware('throttle:public-form')->name('blog.comments.store');

// Payment callbacks
Route::post('/payment/razorpay/callback', [CourseEnrollmentController::class, 'razorpayCallback'])->name('payment.razorpay.callback');
Route::get('/payment/stripe/callback', [CourseEnrollmentController::class, 'stripeCallback'])->name('payment.stripe.callback');
Route::get('/payment/paystack/callback', [CourseEnrollmentController::class, 'paystackCallback'])->name('payment.paystack.callback');
Route::get('/payment/flutterwave/callback', [CourseEnrollmentController::class, 'flutterwaveCallback'])->name('payment.flutterwave.callback');
Route::get('/payment/paypal/callback', [CourseEnrollmentController::class, 'paypalCallback'])->name('payment.paypal.callback');
Route::match(['get', 'post'], '/payment/sslcommerz/callback', [CourseEnrollmentController::class, 'sslcommerzCallback'])->name('payment.sslcommerz.callback');
Route::get('/payment/mollie/callback', [CourseEnrollmentController::class, 'mollieCallback'])->name('payment.mollie.callback');
Route::get('/payment/bkash/callback', [CourseEnrollmentController::class, 'bkashCallback'])->name('payment.bkash.callback');
Route::match(['get', 'post'], '/payment/xpay/callback', [CourseEnrollmentController::class, 'xpayCallback'])->name('payment.xpay.callback');
Route::match(['get', 'post'], '/payment/chapa/callback', [CourseEnrollmentController::class, 'chapaCallback'])->name('payment.chapa.callback');
Route::post('/payment/chapa/webhook', [CourseEnrollmentController::class, 'chapaWebhook'])->name('payment.chapa.webhook');

// Events routes
Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/bookings/verify/{reference}', EventBookingVerificationController::class)->middleware('throttle:public')->name('events.bookings.verify');
Route::match(['get', 'post'], '/events/payment/chapa/callback', [EventController::class, 'chapaCallback'])->name('events.chapa.callback');
Route::get('/events/booking/confirmation/{reference}', [EventController::class, 'confirmation'])->middleware('auth')->name('events.confirmation');

// Tours routes
Route::get('/tours', [TourController::class, 'index'])->name('tours.index');
Route::get('/tours/{slug}', [TourController::class, 'show'])->name('tours.show');
Route::get('/tours/{slug}/book', [TourController::class, 'bookInApp'])->name('tours.book');
Route::post('/tours/{slug}/book', [TourController::class, 'bookInApp'])->name('tours.book.process');
Route::match(['get', 'post'], '/tours/payment/chapa/callback', [TourController::class, 'chapaCallback'])->name('tours.chapa.callback');
Route::get('/tours/booking/confirmation/{reference}', [TourController::class, 'confirmation'])->middleware('auth')->name('tours.confirmation');

// Event bookings routes
Route::middleware('auth')->group(function () {
    Route::get('/events/{slug}/book', [EventController::class, 'book'])->name('events.book');
    Route::post('/events/{slug}/book', [EventController::class, 'storeBooking'])->name('events.book.store');
    Route::get('/student/tours', [TourController::class, 'myBookings'])->name('student.tours');
    Route::post('/student/tours/{reference}/cancel', [TourController::class, 'cancelBooking'])->name('student.tours.cancel');
});
Route::get('/events/{slug}', [EventController::class, 'show'])->name('events.show');

// Course checkout routes
Route::middleware(['auth'])->group(function () {

    Route::get('/courses/{id}/checkout', [CourseEnrollmentController::class, 'checkout'])->name('courses.checkout');
    Route::post('/courses/{id}/checkout', [CourseEnrollmentController::class, 'processCheckout'])->name('courses.checkout.process');
    Route::get('/bundles/{id}/checkout', [CourseEnrollmentController::class, 'checkoutBundle'])->name('bundles.checkout');
    Route::post('/bundles/{id}/checkout', [CourseEnrollmentController::class, 'processCheckoutBundle'])->name('bundles.checkout.process');
    Route::get('/enrollment/success/{id}', [CourseEnrollmentController::class, 'success'])->name('enrollment.success');
    Route::post('/coupons/validate', [CourseEnrollmentController::class, 'validateCoupon'])->name('coupons.validate');

    // Courses reviews routes
    Route::post('/courses/{courseId}/reviews', [ReviewController::class, 'store'])->name('courses.reviews.store');
    Route::put('/courses/{courseId}/reviews/{reviewId}', [ReviewController::class, 'update'])->name('courses.reviews.update');

});

// Cron runner route
Route::get('/cron/run', [App\Http\Controllers\CronController::class, 'run'])->name('cron.run');

// Serve storage files with CORS headers, caching, and strict path-traversal prevention
Route::get('/storage/{path}', function (string $path) {
    $cleanPath = ltrim(str_replace(['../', '..\\'], '', $path), '/\\');

    $storagePublicBase = realpath(storage_path('app/public'));
    $publicStorageBase = realpath(public_path('storage'));

    $fullPath = null;
    $candidate1 = realpath(storage_path('app/public/' . $cleanPath));
    if ($candidate1 && $storagePublicBase && str_starts_with($candidate1, $storagePublicBase) && is_file($candidate1)) {
        $fullPath = $candidate1;
    } else {
        $candidate2 = realpath(public_path('storage/' . $cleanPath));
        if ($candidate2 && $publicStorageBase && str_starts_with($candidate2, $publicStorageBase) && is_file($candidate2)) {
            $fullPath = $candidate2;
        }
    }

    if (!$fullPath) {
        abort(404);
    }

    $mime = mime_content_type($fullPath) ?: 'application/octet-stream';
    return response()->file($fullPath, [
        'Content-Type' => $mime,
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
        'Access-Control-Allow-Headers' => '*',
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*');

// SaaS Billing Webhooks & Customer Portal
Route::post('/billing/webhook/{provider}', [\App\Http\Controllers\BillingWebhookController::class, 'handle'])->name('billing.webhook');

// ==========================================
// FIRA EXPORT DOCS STUDIO (WEB ROUTES)
// ==========================================
// Signed-in users only: these pages show a business's own shipments and documents.
Route::prefix('export-docs')->name('export-docs.')->middleware('auth')->group(function () {
    // Template Marketplace & Catalog
    Route::get('/', [\App\Http\Controllers\ExportDocsWebController::class, 'index'])->name('index');
    Route::get('/templates/{code}/start', [\App\Http\Controllers\ExportDocsWebController::class, 'startFromTemplate'])->name('templates.start');

    // 3-Panel Interactive Studio Workspace
    Route::get('/studio/{id}', [\App\Http\Controllers\ExportDocsWebController::class, 'studio'])->name('studio');

    // Canonical Export Shipments ("Enter Shipment Once")
    Route::get('/shipments', [\App\Http\Controllers\ExportDocsWebController::class, 'shipments'])->name('shipments.index');
    Route::get('/shipments/create', [\App\Http\Controllers\ExportDocsWebController::class, 'createShipment'])->name('shipments.create');
    Route::post('/shipments', [\App\Http\Controllers\ExportDocsWebController::class, 'storeShipment'])->name('shipments.store');
    Route::get('/shipments/{id}', [\App\Http\Controllers\ExportDocsWebController::class, 'showShipment'])->name('shipments.show');

    // Secure Document Downloads
    Route::get('/download/{id}/{format}', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'download'])->name('download');
});


// Legacy subscription route aliases for backward compatibility
Route::get('/subscriptions', fn() => redirect()->route('courses'))->name('subscriptions.index');
Route::get('/student/subscription', fn() => redirect()->route('student.dashboard'))->name('student.subscription');
Route::post('/subscriptions/{id}/checkout', fn() => redirect()->route('courses'))->name('subscriptions.process-checkout');

// Slice 8: the payer's own bank slip, private disk, signed link from Payment::receipt_url.
Route::get('/payments/{payment}/receipt', [\App\Http\Controllers\Api\PaymentController::class, 'showReceipt'])
    ->middleware(['auth', 'signed'])->whereNumber('payment')->name('payments.receipt.show');

// Include all route files
require __DIR__ . '/auth.php';
require __DIR__ . '/student.php';
require __DIR__ . '/instructor.php';
require __DIR__ . '/admin.php';
