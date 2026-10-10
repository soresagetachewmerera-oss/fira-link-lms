<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Quiz Attempt Controller
 *
 * This controller handles the management of quiz attempts.
 *
 * @package App\Http\Controllers\Admin
 */
class QuizAttemptController extends Controller
{
    /**
     * Display all quiz attempts
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $query = QuizAttempt::query()
            ->with([
                'user:id,name,email,profile_photo',
                'quiz:id,title,course_topic_id,passing_score',
                'quiz.topic:id,title,course_id',
                'quiz.topic.course:id,title,instructor_id',
                'quiz.topic.course.instructor:id,name',
                'enrollment:id,course_id,user_id,status',
            ]);

        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('id', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('quiz', function ($quizQuery) use ($search) {
                        $quizQuery->where('title', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('quiz.topic.course', function ($courseQuery) use ($search) {
                        $courseQuery->where('title', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($result = $request->query('result')) {
            if ($result === 'passed') {
                $query->where('passed', true);
            } elseif ($result === 'failed') {
                $query->where('passed', false);
            } elseif ($result === 'in_progress') {
                $query->whereNull('completed_at');
            }
        }

        if ($courseId = $request->query('course_id')) {
            $query->whereHas('quiz.topic.course', function ($courseQuery) use ($courseId) {
                $courseQuery->where('id', $courseId);
            });
        }

        if ($quizId = $request->query('quiz_id')) {
            $query->where('quiz_id', $quizId);
        }

        if ($studentId = $request->query('student_id')) {
            $query->where('user_id', $studentId);
        }

        if ($instructorId = $request->query('instructor_id')) {
            $query->whereHas('quiz.topic.course', function ($courseQuery) use ($instructorId) {
                $courseQuery->where('instructor_id', $instructorId);
            });
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $attempts = $query
            ->orderByDesc('completed_at')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $totalAttempts = QuizAttempt::count();
        $passedAttempts = QuizAttempt::where('passed', true)->count();
        $failedAttempts = QuizAttempt::where('passed', false)->count();
        $averageScoreRaw = QuizAttempt::avg('score');
        $averageScore = $averageScoreRaw !== null ? round((float) $averageScoreRaw, 1) : null;
        $passRate = $totalAttempts > 0 ? round(($passedAttempts / $totalAttempts) * 100, 1) : 0.0;
        $todayAttempts = QuizAttempt::whereDate('created_at', Carbon::today())->count();
        $uniqueStudents = QuizAttempt::distinct('user_id')->count('user_id');

        $courses = Course::orderBy('title')->get(['id', 'title']);
        $quizzes = Quiz::with([
            'topic:id,course_id,title',
            'topic.course:id,title,instructor_id',
        ])->orderBy('title')->get(['id', 'course_topic_id', 'title', 'passing_score']);
        $students = User::where('role', 'student')->orderBy('name')->get(['id', 'name']);
        $instructors = User::where('role', 'instructor')->orderBy('name')->get(['id', 'name']);

        $pageTitle = 'Quiz Attempts';
        $breadcrumbItems = [
            ['name' => 'Dashboard', 'route' => 'admin.dashboard'],
            ['name' => 'Quiz Attempts', 'route' => null],
        ];

        return view('admin.quiz-attempts.index', compact(
            'attempts',
            'pageTitle',
            'breadcrumbItems',
            'courses',
            'quizzes',
            'students',
            'instructors',
            'totalAttempts',
            'passedAttempts',
            'failedAttempts',
            'averageScore',
            'passRate',
            'todayAttempts',
            'uniqueStudents'
        ));
    }

    /**
     * Display a quiz attempt details
     *
     * @param \App\Models\QuizAttempt $quizAttempt
     * @return \Illuminate\Contracts\View\View
     */
    public function show(QuizAttempt $quizAttempt): View
    {
        $quizAttempt->load([
            'user',
            'quiz.topic.course.instructor',
            'quiz.topic.course',
            'answers.question',
            'enrollment.course',
        ]);

        $pageTitle = 'Quiz Attempt #' . $quizAttempt->id;
        $breadcrumbItems = [
            ['name' => 'Dashboard', 'route' => 'admin.dashboard'],
            ['name' => 'Quiz Attempts', 'route' => 'admin.quiz-attempts.index'],
            ['name' => 'Attempt #' . $quizAttempt->id, 'route' => null],
        ];

        return view('admin.quiz-attempts.show', [
            'attempt' => $quizAttempt,
            'pageTitle' => $pageTitle,
            'breadcrumbItems' => $breadcrumbItems,
        ]);
    }
}
