<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Carbon;

/**
 * Quiz Attempts Controller
 *
 * This controller handles the quiz attempts functionality.
 *
 * @package App\Http\Controllers\Student
 */
class QuizAttemptsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->currentUser();

        $courseId = $request->integer('course_id');
        $status = $this->normalizeStatus($request->input('status'));
        $search = trim((string) $request->input('search'));

        $courses = Enrollment::with('course:id,title')
            ->where('user_id', $user->id)
            ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])
            ->get()
            ->pluck('course')
            ->filter()
            ->unique('id')
            ->values();

        $attemptsQuery = QuizAttempt::query()
            ->with(['quiz.topic.course:id,title'])
            ->where('user_id', $user->id);

        if ($courseId) {
            $attemptsQuery->whereHas('quiz.topic.course', fn ($query) => $query->where('id', $courseId));
        }

        if ($status === 'passed') {
            $attemptsQuery->where('passed', true);
        } elseif ($status === 'failed') {
            $attemptsQuery->where('passed', false);
        }

        if ($search !== '') {
            $attemptsQuery->whereHas('quiz', fn ($query) => $query->where('title', 'like', '%' . $search . '%'));
        }

        $attempts = $attemptsQuery
            ->orderByDesc('completed_at')
            ->paginate(15)
            ->withQueryString();

        $attempts->getCollection()->transform(function (QuizAttempt $attempt) {
            $attempt->course_title = optional($attempt->quiz->topic->course)->title ?? 'Unknown Course';
            $attempt->quiz_title = $attempt->quiz->title;
            $attempt->status_label = $attempt->passed ? 'Passed' : 'Failed';
            $attempt->status_class = $attempt->passed ? 'passed' : 'failed';
            $attempt->time_taken_formatted = $this->formatSeconds($attempt->time_taken);
            $attempt->started_at_formatted = optional($attempt->started_at)?->format('M d, Y h:i A');
            $attempt->completed_at_formatted = optional($attempt->completed_at)?->format('M d, Y h:i A');

            return $attempt;
        });

        $summary = $this->buildSummary($user->id);

        return view('student.quizzes.index', [
            'user' => $user,
            'attempts' => $attempts,
            'courses' => $courses,
            'selectedCourseId' => $courseId,
            'statusFilter' => $status,
            'statusOptions' => [
                '' => 'All results',
                'passed' => 'Passed',
                'failed' => 'Failed',
            ],
            'searchTerm' => $search,
            'summary' => $summary,
        ]);
    }

    private function buildSummary(int $userId): array
    {
        $attempts = QuizAttempt::query()
            ->where('user_id', $userId)
            ->get();

        if ($attempts->isEmpty()) {
            return [
                'total_attempts' => 0,
                'passed_attempts' => 0,
                'failed_attempts' => 0,
                'average_score' => null,
                'best_score' => null,
                'average_time' => null,
            ];
        }

        $total = $attempts->count();
        $passed = $attempts->where('passed', true)->count();
        $failed = $total - $passed;
        $avgScore = (int) round($attempts->avg('score'));
        $bestScore = $attempts->max('score');
        $avgTime = $attempts->avg('time_taken');

        return [
            'total_attempts' => $total,
            'passed_attempts' => $passed,
            'failed_attempts' => $failed,
            'average_score' => $avgScore,
            'best_score' => $bestScore,
            'average_time' => $avgTime ? $this->formatSeconds($avgTime) : null,
        ];
    }

    private function normalizeStatus(?string $status): ?string
    {
        $status = $status !== null ? trim($status) : null;

        if ($status === null || $status === '') {
            return null;
        }

        return in_array($status, ['passed', 'failed'], true) ? $status : null;
    }

    private function formatSeconds(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        $interval = Carbon::now()->startOfDay()->addSeconds($seconds)->diff(Carbon::now()->startOfDay());

        return $interval->format('%H:%I:%S');
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
