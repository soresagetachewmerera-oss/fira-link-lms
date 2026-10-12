<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AssignmentSubmissionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InstructorController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\BundleController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\AccountDeletionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Controllers\Admin\BlogCommentController as AdminBlogCommentController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\CourseCategoryController;
use App\Http\Controllers\Admin\CourseLanguageController;
use App\Http\Controllers\Admin\FrontendSettingController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\QuizAttemptController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\SystemMaintenanceController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\EventManagementController;
use App\Http\Controllers\Admin\TourManagementController;
use App\Http\Controllers\Admin\CustomPageController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\UserSubscriptionController;

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Settings Management
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/platform', [SettingsController::class, 'platform'])->name('settings.platform');
    Route::put('/settings/platform', [SettingsController::class, 'updatePlatform'])->name('settings.platform.update');
    Route::get('/settings/ai', [SettingsController::class, 'ai'])->name('settings.ai');
    Route::put('/settings/ai', [SettingsController::class, 'updateAi'])->name('settings.ai.update');
    Route::get('/settings/email-notifications', [SettingsController::class, 'emailNotifications'])->name('settings.email');
    Route::put('/settings/email-notifications', [SettingsController::class, 'updateEmailNotifications'])->name('settings.email.update');
    Route::get('/settings/smtp', [SettingsController::class, 'smtp'])->name('settings.smtp');
    Route::put('/settings/smtp', [SettingsController::class, 'updateSmtp'])->name('settings.smtp.update');
    Route::get('/settings/account-deletion', [SettingsController::class, 'accountDeletion'])->name('settings.account_deletion');
    Route::put('/settings/account-deletion', [SettingsController::class, 'updateAccountDeletion'])->name('settings.account_deletion.update');
    Route::get('/settings/course', [SettingsController::class, 'coursePolicies'])->name('settings.course');
    Route::put('/settings/course', [SettingsController::class, 'updateCoursePolicies'])->name('settings.course.update');
    Route::get('/settings/branding', [SettingsController::class, 'branding'])->name('settings.branding');
    Route::put('/settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding.update');
    Route::get('/settings/chat', [SettingsController::class, 'chat'])->name('settings.chat');
    Route::put('/settings/chat', [SettingsController::class, 'updateChat'])->name('settings.chat.update');
    Route::get('/settings/contact', [SettingsController::class, 'contact'])->name('settings.contact');
    Route::put('/settings/contact', [SettingsController::class, 'updateContact'])->name('settings.contact.update');
    Route::get('/settings/authentication', [SettingsController::class, 'authentication'])->name('settings.authentication');
    Route::put('/settings/authentication', [SettingsController::class, 'updateAuthentication'])->name('settings.authentication.update');
    Route::get('/settings/recaptcha', [SettingsController::class, 'recaptcha'])->name('settings.recaptcha');
    Route::put('/settings/recaptcha', [SettingsController::class, 'updateRecaptcha'])->name('settings.recaptcha.update');
    Route::get('/settings/revenue', [SettingsController::class, 'revenue'])->name('settings.revenue');
    Route::put('/settings/revenue', [SettingsController::class, 'updateRevenue'])->name('settings.revenue.update');
    Route::get('/settings/withdrawals', [SettingsController::class, 'withdrawals'])->name('settings.withdrawals');
    Route::put('/settings/withdrawals', [SettingsController::class, 'updateWithdrawals'])->name('settings.withdrawals.update');
    Route::get('/settings/support', [SettingsController::class, 'support'])->name('settings.support');
    Route::put('/settings/support', [SettingsController::class, 'updateSupport'])->name('settings.support.update');
    Route::get('/settings/custom-scripts', [SettingsController::class, 'customScripts'])->name('settings.custom_scripts');
    Route::put('/settings/custom-scripts', [SettingsController::class, 'updateCustomScripts'])->name('settings.custom_scripts.update');
    Route::get('/settings/cron-setup', [SettingsController::class, 'cronSetup'])->name('settings.cron_setup');
    Route::put('/settings/cron-setup', [SettingsController::class, 'updateCronSetup'])->name('settings.cron_setup.update');
    Route::get('/settings/live-class', [SettingsController::class, 'liveClassSettings'])->name('settings.live_class');
    Route::put('/settings/live-class', [SettingsController::class, 'updateLiveClassSettings'])->name('settings.live_class.update');
    Route::get('/settings/gateways', [PaymentGatewayController::class, 'index'])->name('settings.gateways.index');
    Route::put('/settings/gateways/{identifier}', [PaymentGatewayController::class, 'update'])->name('settings.gateways.update');
    Route::post('/settings/gateways/{identifier}/test', [PaymentGatewayController::class, 'testConnection'])->name('settings.gateways.test');

    // Language Manager
    Route::get('/settings/languages', [LanguageController::class, 'index'])->name('languages.index');
    Route::get('/settings/languages/create', [LanguageController::class, 'create'])->name('languages.create');
    Route::post('/settings/languages', [LanguageController::class, 'store'])->name('languages.store');
    Route::delete('/settings/languages/{locale}', [LanguageController::class, 'destroy'])->name('languages.destroy');
    Route::get('/settings/languages/{locale}/translations', [LanguageController::class, 'editTranslations'])->name('languages.translations.edit');
    Route::post('/settings/languages/{locale}/translations', [LanguageController::class, 'updateTranslations'])->name('languages.translations.update');
    Route::post('/settings/languages/{locale}/translations/add-key', [LanguageController::class, 'addTranslationKey'])->name('languages.translations.add-key');

    Route::resource('custom-pages', CustomPageController::class)->only(['index', 'edit', 'update']);
    Route::prefix('settings/system')->name('settings.system.')->group(function () {
        Route::get('/status', [SystemMaintenanceController::class, 'status'])->name('status');
        Route::get('/cache', [SystemMaintenanceController::class, 'cache'])->name('cache');
        Route::post('/cache/clear', [SystemMaintenanceController::class, 'clearCache'])->name('cache.clear');
        Route::post('/storage-link', [SystemMaintenanceController::class, 'linkStorage'])->name('storage.link');
        Route::get('/logs', [SystemMaintenanceController::class, 'logs'])->name('logs');
    });
    Route::get('/settings/newsletter', [AdminNewsletterController::class, 'index'])->name('settings.newsletter.index');
    Route::post('/settings/newsletter/send', [AdminNewsletterController::class, 'send'])->name('settings.newsletter.send');

    // Event management
    Route::get('/events', [EventManagementController::class, 'index'])->name('events.index');
    Route::get('/events/{event}', [EventManagementController::class, 'show'])->name('events.show');
    Route::patch('/events/{event}/toggle-publish', [EventManagementController::class, 'togglePublish'])->name('events.toggle-publish');

    // Tour management
    Route::get('/tours', [TourManagementController::class, 'index'])->name('tours.index');
    Route::get('/tours/create', [TourManagementController::class, 'create'])->name('tours.create');
    Route::post('/tours', [TourManagementController::class, 'store'])->name('tours.store');
    Route::get('/tours/bookings', [TourManagementController::class, 'bookings'])->name('tours.bookings.index');
    Route::get('/tours/bookings/{booking}', [TourManagementController::class, 'showBooking'])->name('tours.bookings.show');
    Route::put('/tours/bookings/{booking}/status', [TourManagementController::class, 'updateBookingStatus'])->name('tours.bookings.update-status');
    Route::get('/tours/{tour}', [TourManagementController::class, 'show'])->name('tours.show');
    Route::get('/tours/{tour}/edit', [TourManagementController::class, 'edit'])->name('tours.edit');
    Route::put('/tours/{tour}', [TourManagementController::class, 'update'])->name('tours.update');
    Route::delete('/tours/{tour}', [TourManagementController::class, 'destroy'])->name('tours.destroy');
    Route::patch('/tours/{tour}/toggle-publish', [TourManagementController::class, 'togglePublish'])->name('tours.toggle-publish');

    // Instructor management
    Route::get('/instructors', [InstructorController::class, 'index'])->name('instructors.index');
    Route::get('/instructors/pending', [InstructorController::class, 'pending'])->name('instructors.pending');
    Route::get('/instructors/{id}/edit', [InstructorController::class, 'edit'])->name('instructors.edit');
    Route::put('/instructors/{id}', [InstructorController::class, 'update'])->name('instructors.update');
    Route::get('/instructors/{id}', [InstructorController::class, 'show'])->name('instructors.show');
    Route::post('/instructors/{id}/approve', [InstructorController::class, 'approve'])->name('instructors.approve');
    Route::post('/instructors/{id}/reject', [InstructorController::class, 'reject'])->name('instructors.reject');

    // Student management
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/{id}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('/students/{id}', [StudentController::class, 'update'])->name('students.update');
    Route::get('/students/{id}', [StudentController::class, 'show'])->name('students.show');

    // Enrollment management
    Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::get('/enrollments/pending', [EnrollmentController::class, 'pending'])->name('enrollments.pending');
    Route::get('/enrollments/completed', [EnrollmentController::class, 'completed'])->name('enrollments.completed');
    Route::get('/enrollments/{id}', [EnrollmentController::class, 'show'])->name('enrollments.show');
    Route::put('/enrollments/{id}/update-status', [EnrollmentController::class, 'updateStatus'])->name('enrollments.update-status');
    Route::post('/enrollments/{id}/approve', [EnrollmentController::class, 'approve'])->name('enrollments.approve');
    Route::post('/enrollments/{id}/reject', [EnrollmentController::class, 'reject'])->name('enrollments.reject');
    Route::post('/enrollments/{id}/cancel', [EnrollmentController::class, 'cancel'])->name('enrollments.cancel');
    Route::post('/enrollments/{id}/complete', [EnrollmentController::class, 'complete'])->name('enrollments.complete');

    // Payment management
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/pending', [PaymentController::class, 'pending'])->name('payments.pending');
    Route::get('/payments/offline-pending', [PaymentController::class, 'offlinePending'])->name('payments.offline-pending');
    Route::get('/payments/{id}', [PaymentController::class, 'show'])->name('payments.show');
    Route::put('/payments/{id}/update-status', [PaymentController::class, 'updateStatus'])->name('payments.update-status');
    Route::post('/payments/{id}/approve', [PaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{id}/reject', [PaymentController::class, 'reject'])->name('payments.reject');

    // Reports & Analytics
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');

    // Certificates
    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{enrollment}/download', [CertificateController::class, 'download'])->name('certificates.download');

    // Payout management
    Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::get('/withdrawals/{withdrawal}', [WithdrawalController::class, 'show'])->name('withdrawals.show');
    Route::put('/withdrawals/{withdrawal}/status', [WithdrawalController::class, 'updateStatus'])->name('withdrawals.status');

    // Account deletion requests
    Route::get('/account-deletions', [AccountDeletionController::class, 'index'])->name('account-deletions.index');
    Route::put('/account-deletions/{accountDeletionRequest}/approve', [AccountDeletionController::class, 'approve'])->name('account-deletions.approve');
    Route::put('/account-deletions/{accountDeletionRequest}/reject', [AccountDeletionController::class, 'reject'])->name('account-deletions.reject');

    // Review management
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/reviews/pending', [ReviewController::class, 'pending'])->name('reviews.pending');
    Route::get('/reviews/{id}', [ReviewController::class, 'show'])->name('reviews.show');
    Route::post('/reviews/{id}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{id}/reject', [ReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
    Route::delete('/reviews/{reviewId}/replies/{replyId}', [ReviewController::class, 'deleteReply'])->name('reviews.delete-reply');

    // Impersonation
    Route::post('/impersonate/instructor/{id}', [ImpersonationController::class, 'impersonate'])->name('impersonate.instructor');
    Route::post('/impersonate/student/{id}', [ImpersonationController::class, 'impersonateStudent'])->name('impersonate.student');

    // User management
    Route::resource('users', UserController::class)->only(['index', 'show', 'destroy']);

    // Course management
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/pending', [CourseController::class, 'pending'])->name('courses.pending');
    Route::get('/courses/{id}', [CourseController::class, 'show'])->name('courses.show');
    Route::post('/courses/{id}/approve', [CourseController::class, 'approve'])->name('courses.approve');
    Route::post('/courses/{id}/reject', [CourseController::class, 'reject'])->name('courses.reject');
    Route::post('/courses/{id}/update-status', [CourseController::class, 'updateStatus'])->name('courses.update-status');
    Route::delete('/courses/{id}', [CourseController::class, 'destroy'])->name('courses.destroy');
    Route::delete('/courses/{id}/full-delete', [CourseController::class, 'fullDelete'])->name('courses.full-delete');

    // Bundle management
    Route::resource('bundles', BundleController::class);
    Route::post('/bundles/{bundle}/approve', [BundleController::class, 'approve'])->name('bundles.approve');
    Route::post('/bundles/{bundle}/reject', [BundleController::class, 'reject'])->name('bundles.reject');

    Route::resource('course-categories', CourseCategoryController::class);
    Route::resource('course-languages', CourseLanguageController::class);
    Route::resource('blog-categories', BlogCategoryController::class);

    // Frontend Manager
    Route::resource('banners', BannerController::class);
    Route::prefix('frontend')->name('frontend.')->group(function () {
        Route::resource('menus', MenuController::class);
        Route::post('menus/{menu}/items/order', [MenuItemController::class, 'reorder'])->name('menus.items.reorder');
        Route::post('menus/{menu}/items', [MenuItemController::class, 'store'])->name('menus.items.store');
        Route::put('menus/{menu}/items/{menuItem}', [MenuItemController::class, 'update'])->name('menus.items.update');
        Route::delete('menus/{menu}/items/{menuItem}', [MenuItemController::class, 'destroy'])->name('menus.items.destroy');

        Route::get('settings', [FrontendSettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [FrontendSettingController::class, 'update'])->name('settings.update');
        Route::get('faq', [FrontendSettingController::class, 'faq'])->name('faq.edit');
        Route::put('faq', [FrontendSettingController::class, 'updateFaq'])->name('faq.update');
        Route::get('seo', [FrontendSettingController::class, 'seo'])->name('seo.edit');
        Route::put('seo', [FrontendSettingController::class, 'updateSeo'])->name('seo.update');
        Route::get('marquee', [FrontendSettingController::class, 'marquee'])->name('marquee.edit');
        Route::put('marquee', [FrontendSettingController::class, 'updateMarquee'])->name('marquee.update');
        Route::get('home', [FrontendSettingController::class, 'homepage'])->name('home.edit');
        Route::put('home', [FrontendSettingController::class, 'updateHomepage'])->name('home.update');
        Route::get('about', [FrontendSettingController::class, 'about'])->name('about.edit');
        Route::put('about', [FrontendSettingController::class, 'updateAbout'])->name('about.update');
        Route::get('contact', [FrontendSettingController::class, 'contact'])->name('contact.edit');
        Route::put('contact', [FrontendSettingController::class, 'updateContact'])->name('contact.update');

        // Theme management routes
        Route::get('theme', [FrontendSettingController::class, 'theme'])->name('theme.edit');
        Route::put('theme', [FrontendSettingController::class, 'updateTheme'])->name('theme.update');
        Route::post('theme/upload', [FrontendSettingController::class, 'uploadTheme'])->name('theme.upload');
        Route::delete('theme', [FrontendSettingController::class, 'deleteTheme'])->name('theme.delete');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Learning activity
    Route::get('/learning/quiz-attempts', [QuizAttemptController::class, 'index'])->name('quiz-attempts.index');
    Route::get('/learning/quiz-attempts/{quizAttempt}', [QuizAttemptController::class, 'show'])->name('quiz-attempts.show');
    Route::get('/learning/assignment-submissions', [AssignmentSubmissionController::class, 'index'])->name('assignment-submissions.index');
    Route::get('/learning/assignment-submissions/{assignmentSubmission}', [AssignmentSubmissionController::class, 'show'])->name('assignment-submissions.show');

    // Blog management
    Route::resource('blog-posts', AdminBlogController::class);
    Route::get('blog-comments', [AdminBlogCommentController::class, 'index'])->name('blog-comments.index');
    Route::patch('blog-comments/{comment}/toggle', [AdminBlogCommentController::class, 'toggle'])->name('blog-comments.toggle');
    Route::delete('blog-comments/{comment}', [AdminBlogCommentController::class, 'destroy'])->name('blog-comments.destroy');

    // Certificate management
    Route::resource('certificates', CertificateController::class);

    // Notifications
    Route::resource('notifications', NotificationController::class);
    Route::post('notifications/{id}/resend', [NotificationController::class, 'resend'])->name('notifications.resend');

    // Coupons
    Route::resource('coupons', CouponController::class);

    // Subscriptions & Package Plans
    Route::resource('subscription-plans', SubscriptionPlanController::class);
    Route::post('subscription-plans/{plan}/toggle-status', [SubscriptionPlanController::class, 'toggleStatus'])->name('subscription-plans.toggle-status');
    Route::get('subscriptions', [UserSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('subscriptions/assign', [UserSubscriptionController::class, 'assign'])->name('subscriptions.assign');
    Route::post('subscriptions/approve-offline/{paymentId}', [UserSubscriptionController::class, 'approveOffline'])->name('subscriptions.approve-offline');

    // System Updater
    Route::get('/updater', [\App\Http\Controllers\Admin\UpdaterController::class, 'index'])->name('updater.index');
    Route::post('/updater/run', [\App\Http\Controllers\Admin\UpdaterController::class, 'update'])->name('updater.update');
});

