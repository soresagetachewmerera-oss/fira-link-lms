<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Instructor\RegisterController;
use App\Http\Controllers\Instructor\DashboardController;
use App\Http\Controllers\Instructor\PendingController;
use App\Http\Controllers\Instructor\CoursesController;
use App\Http\Controllers\Instructor\StudentsController;
use App\Http\Controllers\Instructor\AnalyticsController;
use App\Http\Controllers\Instructor\AssignmentsController;
use App\Http\Controllers\Instructor\ReviewsController;
use App\Http\Controllers\Instructor\EarningsController;
use App\Http\Controllers\Instructor\SettingsController;
use App\Http\Controllers\Instructor\CourseBuilderController;
use App\Http\Controllers\Instructor\EventBuilderController;
use App\Http\Controllers\Instructor\EventController as InstructorEventController;
use App\Http\Controllers\Instructor\EventBookingEmailController;
use App\Http\Controllers\Instructor\QuizAttemptsController;

use App\Http\Controllers\Instructor\ChatController;
use App\Http\Controllers\Instructor\NotificationController;
use App\Http\Controllers\Instructor\BundleController;
use App\Http\Controllers\Instructor\CouponController as InstructorCouponController;
use App\Http\Controllers\Instructor\CourseDiscussionController as InstructorCourseDiscussionController;
use App\Http\Controllers\Instructor\LiveClassController;

// Guest instructor routes
Route::middleware('guest')->group(function () {
    Route::get('/become-instructor', [RegisterController::class, 'showRegistrationForm'])->name('instructor.register');
    Route::post('/become-instructor', [RegisterController::class, 'register'])->name('instructor.register.submit');
});

// Authenticated instructor routes
Route::middleware(['auth', 'role:instructor'])->prefix('instructor')->name('instructor.')->group(function () {
    // Pending instructor routes
    Route::get('/pending', [PendingController::class, 'index'])->name('pending');

    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Courses routes
    Route::get('/courses', [CoursesController::class, 'index'])->name('courses');
    Route::get('/students', [StudentsController::class, 'index'])->name('students');
    Route::get('/students/{id}', [StudentsController::class, 'show'])->name('students.show');

    // Assignments routes
    Route::get('/assignments', [AssignmentsController::class, 'index'])->name('assignments.index');
    Route::get('/assignments/{assignment}', [AssignmentsController::class, 'show'])->name('assignments.show');
    Route::get('/assignments/submissions/{submission}', [AssignmentsController::class, 'showSubmission'])->name('assignments.submissions.show');
    Route::put('/assignments/submissions/{submission}', [AssignmentsController::class, 'updateSubmission'])->name('assignments.submissions.update');

    // Quizzes routes
    Route::get('/quizzes', [QuizAttemptsController::class, 'index'])->name('quizzes.index');
    Route::get('/quizzes/{quiz}', [QuizAttemptsController::class, 'show'])->name('quizzes.show');

    // Events routes
    Route::get('/events', [InstructorEventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [EventBuilderController::class, 'create'])->name('events.create');
    Route::post('/events', [EventBuilderController::class, 'store'])->name('events.store');

    // Event bookings routes
    Route::get('/events/{event}/bookings', [InstructorEventController::class, 'bookings'])->name('events.bookings');
    Route::get('/events/{event}/bookings/export', [InstructorEventController::class, 'exportBookings'])->name('events.bookings.export');
    Route::patch('/events/{event}/bookings/{booking}', [InstructorEventController::class, 'updateBookingStatus'])->name('events.bookings.update');

    // Event booking email routes
    Route::post('/events/{event}/bookings/email', [EventBookingEmailController::class, 'send'])->name('events.bookings.email');

    // Event basic routes
    Route::get('/events/{event}/basic', [EventBuilderController::class, 'editBasic'])->name('events.basic.edit');
    Route::put('/events/{event}/basic', [EventBuilderController::class, 'updateBasic'])->name('events.basic.update');

    // Event schedule routes
    Route::get('/events/{event}/schedule', [EventBuilderController::class, 'editSchedule'])->name('events.schedule.edit');
    Route::put('/events/{event}/schedule', [EventBuilderController::class, 'updateSchedule'])->name('events.schedule.update');

    // Event highlights routes
    Route::get('/events/{event}/highlights', [EventBuilderController::class, 'editHighlights'])->name('events.highlights.edit');
    Route::put('/events/{event}/highlights', [EventBuilderController::class, 'updateHighlights'])->name('events.highlights.update');

    // Event speakers routes
    Route::get('/events/{event}/speakers', [EventBuilderController::class, 'editSpeakers'])->name('events.speakers.edit');
    Route::put('/events/{event}/speakers', [EventBuilderController::class, 'updateSpeakers'])->name('events.speakers.update');

    // Event review routes
    Route::get('/events/{event}/review', [EventBuilderController::class, 'editReview'])->name('events.review.edit');

    // Event publish routes
    Route::put('/events/{event}/publish', [EventBuilderController::class, 'publish'])->name('events.review.publish');

    // Analytics routes
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    // Reviews routes
    Route::get('/reviews', [ReviewsController::class, 'index'])->name('reviews');
    Route::post('/reviews/{reviewId}/reply', [ReviewsController::class, 'reply'])->name('reviews.reply');
    Route::put('/reviews/{reviewId}/reply/{replyId}', [ReviewsController::class, 'updateReply'])->name('reviews.reply.update');

    // Earnings routes
    Route::get('/earnings', [EarningsController::class, 'index'])->name('earnings');
    Route::get('/subscription-sales', [EarningsController::class, 'subscriptionSales'])->name('subscription-sales');
    Route::get('/withdrawals', [EarningsController::class, 'withdrawals'])->name('withdrawals');
    Route::post('/withdrawals', [EarningsController::class, 'storeWithdrawal'])->name('withdrawals.store');

    // Settings routes
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
    Route::delete('/settings/account', [SettingsController::class, 'destroy'])->name('settings.account.destroy');

    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    // Course builder routes
    Route::get('/courses/create', [CourseBuilderController::class, 'create'])->name('courses.create');
    Route::post('/courses', [CourseBuilderController::class, 'store'])->name('courses.store');

    // Bundles routes
    Route::resource('bundles', BundleController::class);


    // AI Course Description
    Route::post('/courses/generate-description', [CourseBuilderController::class, 'generateDescription'])->name('courses.generate-description');

    // Course edit routes
    Route::get('/courses/{id}/edit', [CourseBuilderController::class, 'edit'])->name('courses.edit');

    // Course update routes
    Route::put('/courses/{id}', [CourseBuilderController::class, 'update'])->name('courses.update');

    // Chat / Messages routes
    Route::get('messages/start', [ChatController::class, 'startConversation'])->name('messages');
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/requests', [ChatController::class, 'requests'])->name('requests');
        Route::get('/unread-count', [ChatController::class, 'unreadCount'])->name('unread-count');
        Route::get('/{conversation}', [ChatController::class, 'show'])->name('show');
        Route::post('/{conversation}/send', [ChatController::class, 'sendMessage'])->name('send');
        Route::get('/{conversation}/poll', [ChatController::class, 'poll'])->name('poll');
        Route::post('/{conversation}/accept', [ChatController::class, 'accept'])->name('accept');
        Route::post('/{conversation}/decline', [ChatController::class, 'decline'])->name('decline');
    });

    // Coupons
    Route::resource('coupons', InstructorCouponController::class);

    // Course Discussions
    Route::prefix('discussions')->name('discussions.')->group(function () {
        Route::get('/', [InstructorCourseDiscussionController::class, 'index'])->name('index');
        Route::post('/course/{courseId}', [InstructorCourseDiscussionController::class, 'store'])->name('store');
        Route::get('/{id}', [InstructorCourseDiscussionController::class, 'show'])->name('show');
        Route::post('/{id}/replies', [InstructorCourseDiscussionController::class, 'storeReply'])->name('replies.store');
        Route::post('/{id}/pin', [InstructorCourseDiscussionController::class, 'togglePin'])->name('pin');
        Route::post('/{id}/announcement', [InstructorCourseDiscussionController::class, 'toggleAnnouncement'])->name('announcement');
        Route::delete('/{id}', [InstructorCourseDiscussionController::class, 'destroy'])->name('destroy');
        Route::delete('/{id}/replies/{replyId}', [InstructorCourseDiscussionController::class, 'destroyReply'])->name('replies.destroy');
    });

    // Live Classes
    Route::get('live-classes/lessons', [LiveClassController::class, 'getLessonsForCourse'])->name('live-classes.lessons');
    Route::post('live-classes/{id}/start', [LiveClassController::class, 'startSession'])->name('live-classes.start');
    Route::post('live-classes/{id}/end', [LiveClassController::class, 'endSession'])->name('live-classes.end');
    Route::resource('live-classes', LiveClassController::class);
});
