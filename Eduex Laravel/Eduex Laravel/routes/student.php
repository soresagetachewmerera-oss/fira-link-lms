<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Student\RegisterController;
use App\Http\Controllers\Student\DashboardController;
use App\Http\Controllers\Student\MyCoursesController;
use App\Http\Controllers\Student\CertificatesController;
use App\Http\Controllers\Student\StudentCourseController;
use App\Http\Controllers\Student\SettingsController;
use App\Http\Controllers\Student\EventBookingController;
use App\Http\Controllers\Student\AssignmentsController;
use App\Http\Controllers\Student\QuizAttemptsController;
use App\Http\Controllers\Student\PaymentsController;
use App\Http\Controllers\Student\NotificationController;
use App\Http\Controllers\Student\CommunityController;
use App\Http\Controllers\Student\AiChatController;

use App\Http\Controllers\Student\CourseDiscussionController;
use App\Http\Controllers\Student\ChatController;
use App\Http\Controllers\Student\LiveClassController;

// Guest student routes
Route::middleware('guest')->group(function () {
    // Register routes
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// Authenticated student routes
Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/my-courses', [MyCoursesController::class, 'index'])->name('my-courses');

    // Assignments routes
    Route::get('/assignments', [AssignmentsController::class, 'index'])->name('assignments');

    // Quizzes routes
    Route::get('/quiz-attempts', [QuizAttemptsController::class, 'index'])->name('quiz-attempts');

    // Payments routes
    Route::get('/payments', [PaymentsController::class, 'index'])->name('payments');

    // Event bookings routes
    Route::get('/events/bookings/{booking}/ticket', [EventBookingController::class, 'ticket'])->name('events.bookings.ticket');

    // Certificates routes
    Route::get('/certificates', [CertificatesController::class, 'index'])->name('certificates');
    Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::put('/settings/notifications', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
    Route::delete('/settings/account', [SettingsController::class, 'destroy'])->name('settings.account.destroy');

    // Course access routes
    Route::get('/courses/{courseId}/access', [StudentCourseController::class, 'show'])->name('courses.access');
    Route::post('/courses/{courseId}/lesson/load', [StudentCourseController::class, 'loadLesson'])->name('courses.lesson.load');
    Route::post('/courses/{courseId}/lesson/complete', [StudentCourseController::class, 'markComplete'])->name('courses.lesson.complete');
    Route::post('/courses/{courseId}/lesson/progress', [StudentCourseController::class, 'updateProgress'])->name('courses.lesson.progress');
    Route::post('/courses/{courseId}/quiz/{quizId}/load', [StudentCourseController::class, 'loadQuiz'])->name('courses.quiz.load');
    Route::post('/courses/{courseId}/quiz/{quizId}/submit', [StudentCourseController::class, 'submitQuiz'])->name('courses.quiz.submit');
    Route::get('/courses/{courseId}/quiz/attempt/{attemptId}/results', [StudentCourseController::class, 'viewQuizResults'])->name('courses.quiz.results');
    Route::post('/courses/{courseId}/assignment/{assignmentId}/load', [StudentCourseController::class, 'loadAssignment'])->name('courses.assignment.load');
    Route::post('/courses/{courseId}/assignment/{assignmentId}/submit', [StudentCourseController::class, 'submitAssignment'])->name('courses.assignment.submit');
    Route::get('/courses/{courseId}/assignment/submission/{submissionId}', [StudentCourseController::class, 'viewAssignmentSubmission'])->name('courses.assignment.submission');
    Route::get('/courses/{courseId}/item/{itemId}/next', [StudentCourseController::class, 'getNextItem'])->name('courses.item.next');
    Route::get('/courses/{courseId}/item/{itemId}/previous', [StudentCourseController::class, 'getPreviousItem'])->name('courses.item.previous');
    Route::post('/courses/{courseId}/search', [StudentCourseController::class, 'searchContent'])->name('courses.search');
    Route::get('/courses/{courseId}/sidebar/refresh', [StudentCourseController::class, 'refreshSidebar'])->name('courses.sidebar.refresh');
    Route::get('/courses/{courseId}/completion', [StudentCourseController::class, 'showCompletionPage'])->name('courses.completion');
    Route::get('/courses/{courseId}/certificate/download', [StudentCourseController::class, 'downloadCertificate'])->name('courses.certificate.download');

    // Course Discussions routes
    Route::prefix('courses/{courseId}/discussions')->name('courses.discussions.')->group(function () {
        Route::get('/', [CourseDiscussionController::class, 'index'])->name('index');
        Route::post('/', [CourseDiscussionController::class, 'store'])->name('store');
        Route::get('/{id}', [CourseDiscussionController::class, 'show'])->name('show');
        Route::post('/{id}/replies', [CourseDiscussionController::class, 'storeReply'])->name('replies.store');
        Route::post('/{id}/like', [CourseDiscussionController::class, 'toggleLike'])->name('like');
        Route::post('/replies/{replyId}/like', [CourseDiscussionController::class, 'toggleReplyLike'])->name('replies.like');
        Route::delete('/{id}', [CourseDiscussionController::class, 'destroy'])->name('destroy');
        Route::delete('/{id}/replies/{replyId}', [CourseDiscussionController::class, 'destroyReply'])->name('replies.destroy');
    });

    // Live classes routes
    Route::get('/live-classes', [LiveClassController::class, 'index'])->name('live-classes.index');

    // Events bookings routes
    Route::get('/events/bookings', [EventBookingController::class, 'index'])->name('events.bookings');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::get('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.readAll');

    // Community Q&A routes
    Route::prefix('community')->name('community.')->group(function () {
        Route::get('/', [CommunityController::class, 'index'])->name('index');
        Route::post('/', [CommunityController::class, 'store'])->name('store');
        Route::get('/{id}', [CommunityController::class, 'show'])->name('show');
        Route::post('/{id}/answers', [CommunityController::class, 'storeAnswer'])->name('answers.store');
        Route::post('/answers/{id}/accept', [CommunityController::class, 'markAnswerAccepted'])->name('answers.accept');
        Route::post('/vote/{type}/{id}', [CommunityController::class, 'vote'])->name('vote');
    });

    // AI Chat routes
    Route::get('/ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
    Route::post('/ai-chat/send', [AiChatController::class, 'sendMessage'])->name('ai-chat.send');

    // Chat / Messages routes
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('index');
        Route::get('/requests', [ChatController::class, 'requests'])->name('requests');
        Route::get('/unread-count', [ChatController::class, 'unreadCount'])->name('unread-count');
        Route::get('/instructors/search', [ChatController::class, 'searchInstructors'])->name('instructors.search');
        Route::post('/start', [ChatController::class, 'startConversation'])->name('start');
        Route::get('/{conversation}', [ChatController::class, 'show'])->name('show');
        Route::post('/{conversation}/send', [ChatController::class, 'sendMessage'])->name('send');
        Route::get('/{conversation}/poll', [ChatController::class, 'poll'])->name('poll');
    });

});
