<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\TourController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\CourseEnrollmentController;
use App\Http\Controllers\Api\BundleController;
use App\Http\Controllers\Api\StudentCourseController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\CertificateVerificationController as ApiCertificateVerificationController;
use App\Http\Controllers\Api\CustomPageController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Student\AiChatController;
use App\Http\Controllers\Api\ValidateCouponController;
use App\Http\Controllers\Api\CourseDiscussionController;
use App\Http\Controllers\Api\Instructor\CourseDiscussionController as InstructorCourseDiscussionController;
use App\Http\Controllers\Api\LiveClassController;
use App\Http\Controllers\Api\FocusController;
use App\Http\Controllers\Api\HabitController;

Route::get('/settings', [SettingController::class, 'index']);
Route::get('/settings/pages', [CustomPageController::class, 'index']);
Route::get('/settings/pages/{slug}', [CustomPageController::class, 'show']);

// Fira AI (Powered by Google Gemini AI) - Open with optional Sanctum token
Route::get('/fira-ai/status', [AiChatController::class, 'status']);
Route::get('/fira-ai', [AiChatController::class, 'index']);
Route::post('/fira-ai/send', [AiChatController::class, 'sendMessage']);
Route::post('/fira-ai/analyze-document', [AiChatController::class, 'analyzeDocument'])
    ->middleware('throttle:10,1');
Route::match(['GET', 'POST'], '/fira-ai/test', [AiChatController::class, 'testConnection']);
Route::get('/ai-chat', [AiChatController::class, 'index']);
Route::post('/ai-chat/send', [AiChatController::class, 'sendMessage']);
Route::post('/ai-chat/analyze-document', [AiChatController::class, 'analyzeDocument'])
    ->middleware('throttle:10,1');

Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/login/otp/verify', [AuthController::class, 'verifyOtp']);
    Route::post('/login/otp/resend', [AuthController::class, 'resendOtp']);
    // Registration SMS OTP Verification
    Route::post('/register/verify', [AuthController::class, 'verifyRegistrationOtp']);
    Route::post('/register/resend', [AuthController::class, 'resendRegistrationOtp']);
    Route::post('/register/change-phone', [AuthController::class, 'changeRegistrationPhone']);
    // SMS Ethiopia Phone Verification & Password Recovery
    Route::post('/phone/verify/send', [AuthController::class, 'sendPhoneOtp']);
    Route::post('/phone/verify/check', [AuthController::class, 'verifyPhoneOtp']);
    Route::post('/password/forgot/otp', [AuthController::class, 'sendPasswordResetOtp']);
    Route::post('/password/reset/otp', [AuthController::class, 'resetPasswordWithOtp']);

    // Phone sign-in: one step for new and returning users (slice 1)
    Route::post('/phone/start', [\App\Http\Controllers\Api\PhoneAuthController::class, 'start']);
    Route::post('/phone/email-fallback', [\App\Http\Controllers\Api\PhoneAuthController::class, 'emailFallback']);
    Route::post('/phone/verify', [\App\Http\Controllers\Api\PhoneAuthController::class, 'verify']);
    Route::post('/phone/complete', [\App\Http\Controllers\Api\PhoneAuthController::class, 'complete']);

    // Email and password sign-up (no phone needed), confirmed by an emailed 6-digit code
    Route::post('/email/register/start', [\App\Http\Controllers\Api\EmailSignUpController::class, 'start']);
    Route::post('/email/register/verify', [\App\Http\Controllers\Api\EmailSignUpController::class, 'verify']);

    // Forgot password by phone or email: identical answers whether or not the account exists
    Route::post('/password/forgot', [\App\Http\Controllers\Api\PasswordResetController::class, 'forgot']);
    Route::post('/password/verify-code', [\App\Http\Controllers\Api\PasswordResetController::class, 'verifyCode']);
    Route::post('/password/reset', [\App\Http\Controllers\Api\PasswordResetController::class, 'reset']);
});

// Fresh authentication for sensitive actions (signed-in users)
Route::middleware(['auth:sanctum', 'throttle:10,1'])->group(function () {
    Route::post('/auth/step-up/code', [\App\Http\Controllers\Api\StepUpController::class, 'sendCode']);
    Route::post('/auth/step-up/verify', [\App\Http\Controllers\Api\StepUpController::class, 'verify']);
    Route::post('/user/phone', [\App\Http\Controllers\Api\StepUpController::class, 'changePhone'])->middleware('fresh.auth');
    Route::post('/user/email/code', [\App\Http\Controllers\Api\StepUpController::class, 'sendEmailChangeCode'])->middleware('fresh.auth');
    Route::post('/user/email', [\App\Http\Controllers\Api\StepUpController::class, 'changeEmail'])->middleware('fresh.auth');
});

Route::get('/home', [HomeController::class, 'index']);
Route::get('/courses/recent', [HomeController::class, 'recentCourses']);
Route::get('/courses', [CourseController::class, 'index']);
// Categories
Route::get('/categories', [CourseController::class, 'categories']);
Route::get('/courses/{id}', [CourseController::class, 'show']);
Route::get('/bundles', [BundleController::class, 'index']);
Route::get('/bundles/{id}', [BundleController::class, 'show']);
Route::get('/video/stream', [FileController::class, 'stream']);
Route::get('/instructors', [InstructorController::class, 'index']);
Route::get('/instructors/{id}', [InstructorController::class, 'show']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::get('/tours', [TourController::class, 'index']);
Route::get('/tours/{id}', [TourController::class, 'show']);
Route::post('/tours/{id}/book', [TourController::class, 'book']);
Route::post('/tours/payments/chapa/verify', [TourController::class, 'chapaVerify']);
Route::post('/events/payments/chapa/verify', [EventController::class, 'chapaVerify']);
Route::post('/payments/chapa/verify', [CourseEnrollmentController::class, 'chapaVerify']);
Route::post('/courses/payments/chapa/verify', [CourseEnrollmentController::class, 'chapaVerify']);
Route::get('/certificates/download/signed/{id}', [CertificateController::class, 'downloadSigned'])->name('api.certificates.download.signed')->middleware('signed');


// Certificate Verification (Public)
Route::post('/verify-certificate', [ApiCertificateVerificationController::class, 'verify']);
Route::get('/verify-certificate/{code}', [ApiCertificateVerificationController::class, 'show']);

// Public seller check by Fira ID only (slice 3). Rate-limited per IP; no list, search or TIN lookup.
Route::get('/sellers/{firaId}', [\App\Http\Controllers\Api\PublicSellerController::class, 'show'])->middleware('throttle:public-seller');

// Community Q&A (Public)
Route::get('/community/questions', [CommunityController::class, 'index']);
Route::get('/community/questions/{id}', [CommunityController::class, 'show']);

// Coffee B2B Marketplace (Public Discovery & Exploration)
Route::prefix('marketplace')->group(function () {
    Route::get('/listings', [\App\Http\Controllers\Api\MarketplaceController::class, 'index']);
    Route::get('/listings/{idOrSlug}', [\App\Http\Controllers\Api\MarketplaceController::class, 'show']);
    Route::get('/filters', [\App\Http\Controllers\Api\MarketplaceController::class, 'filters']);
    Route::get('/plans', [\App\Http\Controllers\Api\MarketplaceSellerController::class, 'plans']);
    Route::get('/subscribe/chapa/callback', [\App\Http\Controllers\Api\MarketplaceSellerController::class, 'chapaVerify'])->name('api.marketplace.chapa.callback');
    Route::post('/subscribe/chapa/verify', [\App\Http\Controllers\Api\MarketplaceSellerController::class, 'chapaVerify']);
});

// Business setup options: entity types, roles, countries (one list, slice 2)
Route::get('/workspaces/options', [\App\Http\Controllers\Api\OrganizationController::class, 'options']);

// SaaS Dynamic Entitlements API (Server Authority Snapshot)
Route::get('/org/{id}/entitlements', [\App\Http\Controllers\Api\OrganizationEntitlementApiController::class, 'show']);
Route::middleware('auth:sanctum')->get('/user/organization/entitlements', [\App\Http\Controllers\Api\OrganizationEntitlementApiController::class, 'current']);
Route::post('/billing/webhook/{provider}', [\App\Http\Controllers\BillingWebhookController::class, 'handle']);

// Real-Time Coffee Trading Charts API
Route::get('/market/chart-data', [\App\Http\Controllers\Api\CoffeeMarketChartApiController::class, 'chartData']);
Route::get('/market/live-tick', [\App\Http\Controllers\Api\CoffeeMarketChartApiController::class, 'liveTick']);
Route::get('/market/ticker', [\App\Http\Controllers\Api\CoffeeMarketChartApiController::class, 'ticker']);

// Coffee Export Toolkit & Market Intelligence (Public)
Route::prefix('coffee-toolkit')->group(function () {
    Route::get('/hub-summary', [\App\Http\Controllers\Api\CoffeeToolkitController::class, 'hubSummary']);
    Route::get('/market-intelligence', [\App\Http\Controllers\Api\CoffeeToolkitController::class, 'getMarketIntelligence']);
    Route::get('/coffee-prices', [\App\Http\Controllers\Api\CoffeeToolkitController::class, 'getCoffeePrices']);
    Route::post('/calculate', [\App\Http\Controllers\Api\CoffeeToolkitController::class, 'calculate']);
    Route::post('/assess', [\App\Http\Controllers\Api\CoffeeToolkitController::class, 'assessReadiness']);
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Profile & Settings
    Route::post('/user/profile', [ProfileController::class, 'updateProfile']);
    Route::post('/user/profile-photo', [ProfileController::class, 'updateProfilePhoto']);
    Route::post('/user/password', [ProfileController::class, 'updatePassword'])->middleware('fresh.auth');
    Route::post('/user/delete-request', [ProfileController::class, 'requestDeleteAccount']);
    Route::get('/user/delete-request', [ProfileController::class, 'getDeleteRequestStatus']);
    Route::get('/user/settings/notifications', [ProfileController::class, 'getNotificationSettings']);
    Route::put('/user/settings/notifications', [ProfileController::class, 'updateNotificationSettings']);

    // Workspace & Business Tenancy
    Route::get('/user/organizations', [\App\Http\Controllers\Api\OrganizationController::class, 'index']);
    Route::post('/user/organizations', [\App\Http\Controllers\Api\OrganizationController::class, 'store']);
    Route::post('/user/organizations/switch', [\App\Http\Controllers\Api\OrganizationController::class, 'switchWorkspace']);
    Route::get('/organizations/{id}/members', [\App\Http\Controllers\Api\OrganizationController::class, 'members']);
    Route::post('/organizations/{id}/members', [\App\Http\Controllers\Api\OrganizationController::class, 'addMember']);
    Route::delete('/organizations/{id}/members/{userId}', [\App\Http\Controllers\Api\OrganizationController::class, 'removeMember']);
    Route::post('/user/onboarding-intent', [\App\Http\Controllers\Api\OrganizationController::class, 'saveIntent']);

    // Deal Rooms & Contract Milestone Execution
    Route::prefix('deal-rooms')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\DealRoomController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\DealRoomController::class, 'store']);
        Route::get('/{id}', [\App\Http\Controllers\Api\DealRoomController::class, 'show']);
        Route::post('/{id}/sign-contract', [\App\Http\Controllers\Api\DealRoomController::class, 'signContract']);
        Route::post('/{id}/milestones/{milestoneId}/advance', [\App\Http\Controllers\Api\DealRoomController::class, 'advanceMilestone']);
        // Admin or finance only: confirm a milestone after the bank confirms it
        Route::post('/{id}/milestones/{milestoneId}/confirm', [\App\Http\Controllers\Api\DealRoomController::class, 'confirmMilestone'])->middleware('fresh.auth');
        Route::post('/{id}/dispute', [\App\Http\Controllers\Api\DealRoomController::class, 'raiseDispute']);
        Route::post('/{id}/complete', [\App\Http\Controllers\Api\DealRoomController::class, 'completeDeal']);
    });

    // Trust & Safety Reports
    Route::post('/reports', [\App\Http\Controllers\Api\PublicVerificationController::class, 'storeReport']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::post('/user/device-token', [NotificationController::class, 'storeDeviceToken']);
    Route::delete('/user/device-token', [NotificationController::class, 'removeDeviceToken']);
    Route::post('/notifications/test-push', [NotificationController::class, 'testPushNotification']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Enrollment
    Route::post('/courses/{id}/enroll', [CourseEnrollmentController::class, 'enroll']);
    Route::post('/bundles/{id}/checkout', [CourseEnrollmentController::class, 'enrollBundle']);
    Route::post('/events/{id}/book', [EventController::class, 'book']);
    Route::get('/user/bookings', [EventController::class, 'myBookings']);
    Route::get('/user/bookings/{id}', [EventController::class, 'showBooking']);
    Route::get('/user/tour-bookings', [TourController::class, 'myBookings']);
    Route::get('/user/tour-bookings/{id}', [TourController::class, 'showBooking']);
    Route::post('/user/tour-bookings/{id}/cancel', [TourController::class, 'cancelBooking']);
    Route::post('/payments/razorpay/verify', [CourseEnrollmentController::class, 'razorpayVerify']);
    Route::post('/payments/sslcommerz/verify', [CourseEnrollmentController::class, 'sslcommerzVerify']);
    Route::post('/payments/stripe/verify', [CourseEnrollmentController::class, 'stripeVerify']);
    Route::post('/payments/paystack/verify', [CourseEnrollmentController::class, 'paystackVerify']);
    Route::post('/payments/mollie/verify', [CourseEnrollmentController::class, 'mollieVerify']);
    Route::post('/payments/bkash/verify', [CourseEnrollmentController::class, 'bkashVerify']);
    Route::post('/payments/xpay/verify', [CourseEnrollmentController::class, 'xpayVerify']);
    Route::post('/payments/chapa/verify', [CourseEnrollmentController::class, 'chapaVerify']);
    Route::post('/events/payments/chapa/verify', [EventController::class, 'chapaVerify']);
    Route::post('/tours/payments/chapa/verify', [TourController::class, 'chapaVerify']);
    Route::get('/user/courses', [CourseController::class, 'myCourses']);
    Route::get('/user/certificates', [CertificateController::class, 'index']);
    Route::get('/user/certificates/{id}', [CertificateController::class, 'show']);
    Route::get('/user/certificates/{id}/download', [CertificateController::class, 'download']);

    // Payment History
    Route::get('/user/payments', [App\Http\Controllers\Api\PaymentController::class, 'index']);
    Route::get('/user/payments/{id}/receipt', [App\Http\Controllers\Api\PaymentController::class, 'downloadReceipt']);


    // Course Learning & Progress
    Route::get('/user/courses/{course}/learn', [StudentCourseController::class, 'show']);
    // ...
    Route::get('/user/courses/{course}/lessons/{lesson}', [StudentCourseController::class, 'loadLesson']);
    Route::post('/user/courses/{course}/lessons/{lesson}/complete', [StudentCourseController::class, 'markLessonComplete']);

    // Quiz & Assignment Routes
    Route::get('/user/assignments', [StudentCourseController::class, 'myAssignments']);
    Route::get('/user/quizzes', [StudentCourseController::class, 'myQuizzes']);
    Route::get('/user/courses/{course}/quizzes/{quiz}', [StudentCourseController::class, 'loadQuiz']);
    Route::post('/user/courses/{course}/quizzes/{quiz}/submit', [StudentCourseController::class, 'submitQuiz']);
    Route::get('/user/courses/{course}/assignments/{assignment}', [StudentCourseController::class, 'loadAssignment']);
    Route::post('/user/courses/{course}/assignments/{assignment}/submit', [StudentCourseController::class, 'submitAssignment']);
    Route::post('/payments/paypal/verify', [CourseEnrollmentController::class, 'paypalVerify']);

    // Community Q&A (Protected)
    Route::post('/community/questions', [CommunityController::class, 'store']);
    Route::post('/community/questions/{id}/answers', [CommunityController::class, 'storeAnswer']);
    Route::post('/community/answers/{id}/accept', [CommunityController::class, 'markAnswerAccepted']);
    Route::post('/community/vote/{type}/{id}', [CommunityController::class, 'vote']);

    // Coffee B2B Marketplace (Authenticated Operations)
    Route::prefix('marketplace')->group(function () {
        // Buyer & General Actions
        Route::post('/listings/{id}/save', [\App\Http\Controllers\Api\MarketplaceController::class, 'toggleSave']);
        Route::get('/saved-listings', [\App\Http\Controllers\Api\MarketplaceController::class, 'savedListings']);
        Route::post('/listings/{id}/inquire', [\App\Http\Controllers\Api\MarketplaceController::class, 'inquire'])->middleware('verified.role:buyer,broker,exporter,supplier');
        Route::get('/inquiries', [\App\Http\Controllers\Api\MarketplaceController::class, 'myInquiries']);

        // Requests (RFQs) and quotes (slice 5): between businesses, checked by MarketplaceRfqPolicy.
        // Requests are never public; open requests are for verified seller businesses only.
        Route::controller(\App\Http\Controllers\Api\MarketplaceRfqController::class)->group(function () {
            Route::get('/rfqs', 'index');
            Route::get('/rfqs/prefill', 'prefill');
            Route::get('/rfqs/{rfq}', 'show')->whereNumber('rfq');
            Route::post('/rfqs', 'store');
            Route::post('/rfqs/{rfq}/close', 'close')->whereNumber('rfq');
            Route::get('/my-rfqs', 'myRfqs');
            Route::get('/rfqs/{rfq}/quotes', 'rfqQuotes')->whereNumber('rfq');
            Route::post('/rfqs/{rfq}/quote', 'submitQuote')->whereNumber('rfq');
            Route::get('/my-quotes', 'myQuotes');
            Route::post('/quotes/{quote}/accept', 'accept')->whereNumber('quote');
            Route::post('/quotes/{quote}/decline', 'decline')->whereNumber('quote');
        });

        // Seller Inventory & Profile
        Route::get('/seller/profile', [\App\Http\Controllers\Api\MarketplaceSellerController::class, 'profile']);
        Route::post('/seller/profile', [\App\Http\Controllers\Api\MarketplaceSellerController::class, 'updateProfile']);
        // Seller lots (slice 4): owned by the active business; drafts until Tier 1; level from reviewed evidence.
        // Exporters tab and exporter profile (S-114): signed-in users only, rate-limited.
        Route::middleware('throttle:60,1')->controller(\App\Http\Controllers\Api\ExporterController::class)->group(function () {
            Route::get('/exporters', 'index');
            Route::get('/exporters/{organization}', 'show')->whereNumber('organization');
        });

        Route::prefix('seller/lots')->controller(\App\Http\Controllers\Api\SellerLotController::class)->group(function () {
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('/{lot}', 'show')->whereNumber('lot');
            Route::put('/{lot}', 'update')->whereNumber('lot');
            Route::delete('/{lot}', 'destroy')->whereNumber('lot');
            Route::post('/{lot}/publish', 'publish')->whereNumber('lot');
            Route::post('/{lot}/pause', 'pause')->whereNumber('lot');
            Route::post('/{lot}/evidence/photos', 'storePhoto')->whereNumber('lot');
            Route::post('/{lot}/evidence/lab-report', 'storeLabReport')->whereNumber('lot');
            Route::delete('/{lot}/evidence/{evidence}', 'withdrawEvidence')->whereNumber('lot')->whereNumber('evidence');
        });

        // Verification & Compliance Dossier (Multi-Role)
        Route::get('/verification/status', [\App\Http\Controllers\Api\OrganizationVerificationController::class, 'status']);
        Route::post('/verification/documents', [\App\Http\Controllers\Api\OrganizationVerificationController::class, 'uploadDocument'])->middleware('fresh.auth');
        Route::delete('/verification/documents/{id}', [\App\Http\Controllers\Api\OrganizationVerificationController::class, 'deleteDocument']);

        // Samples (slice 6): between businesses, checked by MarketplaceSamplePolicy.
        Route::controller(\App\Http\Controllers\Api\MarketplaceSampleController::class)->group(function () {
            Route::get('/samples', 'index');
            Route::post('/samples', 'store');
            Route::get('/samples/{sample}', 'show')->whereNumber('sample');
            Route::get('/samples/{sample}/photos/{kind}', 'photo')->whereNumber('sample')->whereIn('kind', ['sealed', 'retained'])
                ->middleware('signed')->name('api.samples.photo');
            Route::post('/samples/{sample}/pay', 'pay')->whereNumber('sample');
            Route::post('/samples/{sample}/cancel', 'cancel')->whereNumber('sample');
            Route::post('/samples/{sample}/dispatch', 'dispatchSample')->whereNumber('sample');
            Route::post('/samples/{sample}/delivered', 'markDelivered')->whereNumber('sample');
            Route::post('/samples/{sample}/evaluate', 'evaluateSample')->whereNumber('sample');
        });

        // Subscriptions & Upgrades
        Route::post('/subscribe', [\App\Http\Controllers\Api\MarketplaceSellerController::class, 'subscribe']);
    });

    // Coffee Export Toolkit (Protected User Operations)
    Route::prefix('coffee-toolkit')->group(function () {
        Route::post('/calculations', [\App\Http\Controllers\Api\CoffeeToolkitController::class, 'saveCalculation']);
        Route::get('/calculations', [\App\Http\Controllers\Api\CoffeeToolkitController::class, 'getCalculations']);
        Route::post('/offers', [\App\Http\Controllers\Api\CoffeeToolkitController::class, 'createOffer']);
        Route::get('/offers', [\App\Http\Controllers\Api\CoffeeToolkitController::class, 'getOffers']);
        Route::get('/offers/{id}', [\App\Http\Controllers\Api\CoffeeToolkitController::class, 'getOffer']);
        Route::get('/offers/{id}/pdf', [\App\Http\Controllers\Api\CoffeeToolkitController::class, 'downloadOfferPdf']);
        Route::get('/assessments', [\App\Http\Controllers\Api\CoffeeToolkitController::class, 'getAssessments']);
    });

    // Student ↔ Instructor Chat
    Route::prefix('messages')->group(function () {
        Route::get('/', [ChatController::class, 'index']);
        Route::get('/requests', [ChatController::class, 'requests']);
        Route::get('/unread-count', [ChatController::class, 'unreadCount']);
        Route::get('/instructors/search', [ChatController::class, 'searchInstructors']);
        Route::post('/start', [ChatController::class, 'startConversation']);
        Route::get('/{id}', [ChatController::class, 'show']);
        Route::post('/{id}/send', [ChatController::class, 'sendMessage']);
        Route::get('/{id}/poll', [ChatController::class, 'poll']);
    });

    // Coupon Validation
    Route::post('/coupons/validate', [ValidateCouponController::class, 'validateCoupon']);

    // Course Discussions
    Route::prefix('courses/{courseId}/discussions')->group(function () {
        Route::get('/', [CourseDiscussionController::class, 'index']);
        Route::post('/', [CourseDiscussionController::class, 'store']);
        Route::get('/{id}', [CourseDiscussionController::class, 'show']);
        Route::post('/{id}/replies', [CourseDiscussionController::class, 'storeReply']);
        Route::post('/{id}/like', [CourseDiscussionController::class, 'toggleLike']);
        Route::post('/replies/{replyId}/like', [CourseDiscussionController::class, 'toggleReplyLike']);
        Route::delete('/{id}', [CourseDiscussionController::class, 'destroy']);
        Route::delete('/{id}/replies/{replyId}', [CourseDiscussionController::class, 'destroyReply']);
    });

    // Live Classes (Student)
    Route::get('/live-classes', [LiveClassController::class, 'index']);
    Route::get('/live-classes/upcoming', [LiveClassController::class, 'upcoming']);
    Route::get('/live-classes/{id}', [LiveClassController::class, 'show']);

    // Focus / Study Timer
    Route::post('/focus/session', [FocusController::class, 'store']);
    Route::get('/focus/stats', [FocusController::class, 'stats']);

    // Habits & Weekly Review
    Route::get('/habits', [HabitController::class, 'index']);
    Route::post('/habits', [HabitController::class, 'store']);
    Route::post('/habits/{id}/log', [HabitController::class, 'log']);
    Route::get('/habits/weekly-review', [HabitController::class, 'weeklyReview']);
    Route::delete('/habits/{id}', [HabitController::class, 'destroy']);
});

// Instructor App Routes
Route::prefix('instructor')->group(function () {
    Route::post('/verify-certificate', [ApiCertificateVerificationController::class, 'verify']);
    Route::get('/verify-certificate/{code}', [ApiCertificateVerificationController::class, 'show']);

    Route::prefix('auth')->middleware('throttle:10,1')->group(function () {
        Route::post('/register', [\App\Http\Controllers\Api\Instructor\AuthController::class, 'register']);
        Route::post('/login', [\App\Http\Controllers\Api\Instructor\AuthController::class, 'login']);
        Route::post('/login/otp/verify', [\App\Http\Controllers\Api\Instructor\AuthController::class, 'verifyOtp']);
        Route::post('/login/otp/resend', [\App\Http\Controllers\Api\Instructor\AuthController::class, 'resendOtp']);
        Route::post('/forgot-password', [\App\Http\Controllers\Api\Instructor\AuthController::class, 'forgotPassword']);
    });
    
    Route::middleware(['auth:sanctum', 'role:instructor', 'throttle:api'])->group(function () {
        Route::get('/user', [\App\Http\Controllers\Api\Instructor\AuthController::class, 'user']);
        Route::post('/logout', [\App\Http\Controllers\Api\Instructor\AuthController::class, 'logout']);
        Route::get('/dashboard', [\App\Http\Controllers\Api\Instructor\DashboardController::class, 'index']);
        Route::get('/analytics', [\App\Http\Controllers\Api\Instructor\AnalyticsController::class, 'index']);
        
        // Settings
        Route::prefix('settings')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Instructor\SettingsController::class, 'index']);
            Route::post('/profile', [\App\Http\Controllers\Api\Instructor\SettingsController::class, 'updateProfile']);
            Route::post('/profile-photo', [\App\Http\Controllers\Api\Instructor\SettingsController::class, 'updateProfilePhoto']);
            Route::post('/password', [\App\Http\Controllers\Api\Instructor\SettingsController::class, 'updatePassword'])->middleware('fresh.auth');
            Route::post('/delete-request', [\App\Http\Controllers\Api\Instructor\SettingsController::class, 'deleteAccount']);
            Route::put('/notifications', [\App\Http\Controllers\Api\Instructor\SettingsController::class, 'updateNotifications']);
        });

        Route::get('/courses', [\App\Http\Controllers\Api\Instructor\CourseController::class, 'index']);
        Route::get('/courses/{id}', [\App\Http\Controllers\Api\Instructor\CourseController::class, 'show']);
        
        Route::get('/bundles', [\App\Http\Controllers\Api\Instructor\BundleController::class, 'index']);
        Route::get('/bundles/{id}', [\App\Http\Controllers\Api\Instructor\BundleController::class, 'show']);

        // Chat Routes
        Route::get('/chat', [\App\Http\Controllers\Api\Instructor\ChatController::class, 'index']);
        Route::get('/chat/requests', [\App\Http\Controllers\Api\Instructor\ChatController::class, 'requests']);
        Route::post('/chat/accept/{id}', [\App\Http\Controllers\Api\Instructor\ChatController::class, 'accept']);
        Route::post('/chat/decline/{id}', [\App\Http\Controllers\Api\Instructor\ChatController::class, 'decline']);
        Route::get('/chat/unread-count', [\App\Http\Controllers\Api\Instructor\ChatController::class, 'unreadCount']);
        Route::post('/chat/start', [\App\Http\Controllers\Api\Instructor\ChatController::class, 'startConversation']);
        Route::get('/chat/{id}', [\App\Http\Controllers\Api\Instructor\ChatController::class, 'show']);
        Route::post('/chat/{id}/send', [\App\Http\Controllers\Api\Instructor\ChatController::class, 'sendMessage']);
        Route::get('/chat/{id}/poll', [\App\Http\Controllers\Api\Instructor\ChatController::class, 'poll']);

        // Assignment Routes
        Route::get('/assignments', [\App\Http\Controllers\Api\Instructor\AssignmentController::class, 'index']);
        Route::get('/assignments/{id}', [\App\Http\Controllers\Api\Instructor\AssignmentController::class, 'show']);
        Route::get('/assignments/submissions/{id}', [\App\Http\Controllers\Api\Instructor\AssignmentController::class, 'showSubmission']);
        Route::put('/assignments/submissions/{id}', [\App\Http\Controllers\Api\Instructor\AssignmentController::class, 'updateSubmission']);

        // Quiz Attempt Routes
        Route::get('/quizzes', [\App\Http\Controllers\Api\Instructor\QuizAttemptController::class, 'index']);
        Route::get('/quizzes/{id}', [\App\Http\Controllers\Api\Instructor\QuizAttemptController::class, 'show']);

        // Events
        Route::get('/events', [App\Http\Controllers\Api\Instructor\EventController::class, 'index']);
        Route::get('/events/{id}', [App\Http\Controllers\Api\Instructor\EventController::class, 'show']);
        Route::post('/events/bookings/{id}/status', [App\Http\Controllers\Api\Instructor\EventController::class, 'updateBookingStatus']);
        Route::post('/events/{id}/email', [App\Http\Controllers\Api\Instructor\EventController::class, 'sendEmail']);
        Route::get('/events/{id}/export', [App\Http\Controllers\Api\Instructor\EventController::class, 'export']);
        Route::post('/events/verify', [App\Http\Controllers\Api\Instructor\EventController::class, 'verifyTicket']);

        // Students
        Route::get('/students', [\App\Http\Controllers\Api\Instructor\StudentsController::class, 'index']);
        Route::get('/students/{id}', [\App\Http\Controllers\Api\Instructor\StudentsController::class, 'show']);

        // Reviews
        Route::get('/reviews', [\App\Http\Controllers\Api\Instructor\ReviewController::class, 'index']);
        Route::post('/reviews/{id}/reply', [\App\Http\Controllers\Api\Instructor\ReviewController::class, 'reply']);
        Route::put('/reviews/{id}/reply/{replyId}', [\App\Http\Controllers\Api\Instructor\ReviewController::class, 'updateReply']);
        Route::delete('/reviews/{id}/reply/{replyId}', [\App\Http\Controllers\Api\Instructor\ReviewController::class, 'deleteReply']);

        // Earnings & Withdrawals
        Route::get('/earnings', [\App\Http\Controllers\Api\Instructor\EarningsController::class, 'index']);
        Route::get('/withdrawals', [\App\Http\Controllers\Api\Instructor\EarningsController::class, 'withdrawals']);
        Route::post('/withdrawals', [\App\Http\Controllers\Api\Instructor\EarningsController::class, 'storeWithdrawal'])->middleware('fresh.auth');

        // Coupons
        Route::get('/coupons', [\App\Http\Controllers\Api\Instructor\CouponController::class, 'index']);
        Route::post('/coupons', [\App\Http\Controllers\Api\Instructor\CouponController::class, 'store']);
        Route::get('/coupons/{id}', [\App\Http\Controllers\Api\Instructor\CouponController::class, 'show']);
        Route::put('/coupons/{id}', [\App\Http\Controllers\Api\Instructor\CouponController::class, 'update']);
        Route::delete('/coupons/{id}', [\App\Http\Controllers\Api\Instructor\CouponController::class, 'destroy']);

        // Course Discussions
        Route::prefix('discussions')->group(function () {
            Route::get('/', [InstructorCourseDiscussionController::class, 'index']);
            Route::post('/course/{courseId}', [InstructorCourseDiscussionController::class, 'store']);
            Route::get('/{id}', [InstructorCourseDiscussionController::class, 'show']);
            Route::post('/{id}/replies', [InstructorCourseDiscussionController::class, 'storeReply']);
            Route::post('/{id}/like', [InstructorCourseDiscussionController::class, 'toggleLike']);
            Route::post('/replies/{replyId}/like', [InstructorCourseDiscussionController::class, 'toggleReplyLike']);
            Route::post('/{id}/pin', [InstructorCourseDiscussionController::class, 'togglePin']);
            Route::post('/{id}/announcement', [InstructorCourseDiscussionController::class, 'toggleAnnouncement']);
            Route::delete('/{id}', [InstructorCourseDiscussionController::class, 'destroy']);
            Route::delete('/{id}/replies/{replyId}', [InstructorCourseDiscussionController::class, 'destroyReply']);
        });

        // Live Classes (Instructor)
        Route::get('/live-classes', [\App\Http\Controllers\Api\Instructor\LiveClassController::class, 'index']);
        Route::post('/live-classes', [\App\Http\Controllers\Api\Instructor\LiveClassController::class, 'store']);
        Route::get('/live-classes/course/{courseId}/lessons', [\App\Http\Controllers\Api\Instructor\LiveClassController::class, 'getLessonsForCourse']);
        Route::get('/live-classes/{id}', [\App\Http\Controllers\Api\Instructor\LiveClassController::class, 'show']);
        Route::put('/live-classes/{id}', [\App\Http\Controllers\Api\Instructor\LiveClassController::class, 'update']);
        Route::delete('/live-classes/{id}', [\App\Http\Controllers\Api\Instructor\LiveClassController::class, 'destroy']);
        Route::post('/live-classes/{id}/start', [\App\Http\Controllers\Api\Instructor\LiveClassController::class, 'start']);
        Route::post('/live-classes/{id}/end', [\App\Http\Controllers\Api\Instructor\LiveClassController::class, 'end']);

    });
});

// ==========================================
// FIRA EXPORT DOCS STUDIO API
// ==========================================
Route::prefix('export-docs')->group(function () {
    // Template Catalog & Calculation (Public / Instant Explore)
    Route::get('/templates', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'templates']);
    Route::get('/templates/{code}', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'template']);
    Route::post('/calculate', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'calculate']);

    // Contextual Fira AI Assistant with Trade Guardrails
    Route::post('/ai/assist', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'aiAssist'])
        ->middleware('throttle:30,1');

    // Canonical Shipments ("Enter Shipment Once")
    Route::get('/shipments', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'shipments']);
    Route::post('/shipments', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'storeShipment']);
    Route::get('/shipments/{id}', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'showShipment']);
    Route::post('/shipments/{id}/generate-all', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'generateAllDocuments']);

    // Generated Documents & Studio
    Route::get('/documents', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'documents']);
    Route::post('/documents', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'storeDocument']);
    Route::get('/documents/{id}', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'showDocument']);
    Route::put('/documents/{id}', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'updateDocument']);
    Route::post('/documents/{id}/finalize', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'finalizeDocument']);
    Route::get('/documents/{id}/preview', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'preview'])->name('api.export-docs.preview');
    Route::get('/documents/{id}/download/{format}', [\App\Http\Controllers\Api\ExportDocsApiController::class, 'download'])->name('api.export-docs.download');
});

