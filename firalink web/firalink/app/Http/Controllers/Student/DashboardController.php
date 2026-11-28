<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Services\LiveClassService;

/**
 * Dashboard Controller
 *
 * This controller handles the dashboard functionality.
 *
 * @package App\Http\Controllers\Student
 */
class DashboardController extends Controller
{
    public function __construct(protected LiveClassService $liveClassService) {}

    /**
     * Display the student dashboard
     */
    public function index()
    {
        $user = $this->currentUser();
        $upcomingLiveClass = $this->liveClassService->getUpcomingForStudent($user);

        $totalEnrollments = Enrollment::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'completed'])
            ->count();

        $completedCourses = Enrollment::where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_COMPLETED)
            ->count();

        $pendingEnrollments = Enrollment::where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_PENDING)
            ->count();

        $totalLessons = Enrollment::with('course.topics.lessons')
            ->where('user_id', $user->id)
            ->whereIn('status', ['approved', 'completed'])
            ->get()
            ->sum(function (Enrollment $enrollment) {
                return $enrollment->course?->topics->sum(fn ($topic) => $topic->lessons->count()) ?? 0;
            });

        $recentEnrollments = Enrollment::with(['course.instructor', 'course.topics.lessons'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['approved', 'completed'])
            ->orderBy('enrolled_at', 'desc')
            ->limit(5)
            ->get()
            ->each(function (Enrollment $enrollment) {
                $enrollment->lessons_count = $enrollment->course?->topics->sum(fn ($topic) => $topic->lessons->count()) ?? 0;
            });

        $stats = [
            'total_enrollments' => $totalEnrollments,
            'completed_courses' => $completedCourses,
            'pending_enrollments' => $pendingEnrollments,
            'total_lessons' => $totalLessons,
        ];

        $firstName = self::greetingName((string) $user->name);

        $activeSubscription = null;
        $latestSubscription = null;

        return view('student.dashboard', compact(
            'user', 'stats', 'recentEnrollments', 'firstName', 'upcomingLiveClass', 'activeSubscription', 'latestSubscription'
        ));
    }

    /**
     * The name used in "Welcome back, …": the first word that is not a title,
     * so "Mr. Donato Mosciski" and "Ato Abebe Kebede" greet Donato and Abebe.
     */
    public static function greetingName(string $name): string
    {
        $titles = ['mr', 'mrs', 'ms', 'miss', 'dr', 'prof', 'ato', 'w/ro', 'w/rt', 'weyzero', 'weyzerit', 'አቶ', 'ወ/ሮ', 'ወ/ሪት', 'ዶ/ር'];
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($words as $word) {
            if (! in_array(mb_strtolower(rtrim($word, '.')), $titles, true)) {
                return $word;
            }
        }

        return $words[0] ?? $name;
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

