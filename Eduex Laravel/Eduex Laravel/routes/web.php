<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Front\CoursesController;
use App\Http\Controllers\Front\CourseEnrollmentController;
use App\Http\Controllers\Front\EventController;
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
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Front\CertificateVerificationController;
use App\Http\Controllers\Front\SubscriptionController;
use App\Models\CustomPage;

require __DIR__.'/install.php';

Route::get('/', HomeController::class)->name('home');
Route::match(['get', 'post'], '/verify-certificate', [CertificateVerificationController::class, 'verify'])->name('verify-certificate');

// Terms and conditions routes
Route::get('/terms-conditions', function () {
    return redirect()->route('pages.show', ['customPage' => 'terms-and-conditions'], 301);
});

// About and contact routes
Route::get('/about', AboutController::class)->name('about');
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

// Instructors routes
Route::get('/instructors', [InstructorController::class, 'index'])->name('instructors.index');
Route::get('/instructors/{instructor}', [InstructorController::class, 'show'])
    ->whereNumber('instructor')
    ->name('instructors.show');

// Testimonials and newsletter routes
Route::get('/testimonials', [TestimonialController::class, 'index'])->name('testimonials.index');
Route::get('/pages/{customPage:slug}', PageController::class)->name('pages.show');
Route::post('/newsletter/subscribe', [NewsletterController::class, 'store'])->name('newsletter.subscribe');

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
Route::post('/blog/{slug}/comments', [BlogCommentController::class, 'store'])->name('blog.comments.store');

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
Route::get('/events/bookings/verify/{reference}', EventBookingVerificationController::class)->name('events.bookings.verify');

// Event bookings routes
Route::middleware('auth')->group(function () {
    Route::get('/events/{slug}/book', [EventController::class, 'book'])->name('events.book');
    Route::post('/events/{slug}/book', [EventController::class, 'storeBooking'])->name('events.book.store');
});
Route::get('/events/{slug}', [EventController::class, 'show'])->name('events.show');

// Public subscription pricing route
Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');

// Course checkout routes
Route::middleware(['auth'])->group(function () {
    Route::get('/subscriptions/{plan}/checkout', [SubscriptionController::class, 'checkout'])->name('subscriptions.checkout');
    Route::post('/subscriptions/{plan}/checkout', [SubscriptionController::class, 'processCheckout'])->name('subscriptions.process-checkout');
    Route::get('/student/subscription', [SubscriptionController::class, 'mySubscription'])->name('student.subscription');
    Route::post('/subscriptions/enroll/{courseId}', [SubscriptionController::class, 'enrollViaSubscription'])->name('subscriptions.enroll');

    Route::get('/courses/{id}/checkout', [CourseEnrollmentController::class, 'checkout'])->name('courses.checkout');
    Route::post('/courses/{id}/checkout', [CourseEnrollmentController::class, 'processCheckout'])->name('courses.checkout.process');
    Route::get('/bundles/{id}/checkout', [CourseEnrollmentController::class, 'checkoutBundle'])->name('bundles.checkout');
    Route::post('/bundles/{id}/checkout', [CourseEnrollmentController::class, 'processCheckoutBundle'])->name('bundles.checkout.process');
    Route::get('/enrollment/success/{id}', [CourseEnrollmentController::class, 'success'])->name('enrollment.success');
    Route::post('/coupons/validate', [CourseEnrollmentController::class, 'validateCoupon'])->name('coupons.validate');

    // Courses reviews routes
    Route::post('/courses/{courseId}/reviews', [ReviewController::class, 'store'])->name('courses.reviews.store');
    Route::put('/courses/{courseId}/reviews/{reviewId}', [ReviewController::class, 'update'])->name('courses.reviews.update');

    // Admin impersonation routes
    Route::post('/admin/stop-impersonating', [ImpersonationController::class, 'stopImpersonating'])->name('admin.stop-impersonating');
});

// Cron runner route
Route::get('/cron/run', [App\Http\Controllers\CronController::class, 'run'])->name('cron.run');

// Include all route files
require __DIR__ . '/auth.php';
require __DIR__ . '/student.php';
require __DIR__ . '/instructor.php';
require __DIR__ . '/admin.php';
