<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Quiz Attempts Controller
 *
 * This controller handles the quiz attempts functionality.
 *
 * @package App\Http\Controllers\Instructor
 */
class QuizAttemptsController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $courseId = $request->integer('course_id');
        $search = trim((string) $request->input('search'));

        $quizzesQuery = Quiz::query()
            ->with(['topic.course:id,title,instructor_id'])
            ->withCount([
                'attempts as attempts_count',
                'attempts as passed_attempts_count' => fn($q) => $q->where('passed', true),
            ])
            ->whereHas('topic.course', fn($q) => $q->where('instructor_id', $user->id));

        if ($courseId) {
            $quizzesQuery->whereHas('topic.course', fn($q) => $q->where('id', $courseId));
        }

        if ($search !== '') {
            $quizzesQuery->where('title', 'like', '%' . $search . '%');
        }

        $quizIds = (clone $quizzesQuery)->select('quizzes.id')->pluck('quizzes.id');
        $summary = $this->summarizeQuizzes($quizIds);

        $quizzes = $quizzesQuery
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        $quizzes->getCollection()->transform(function (Quiz $quiz) {
            $quiz->course_title = optional($quiz->topic->course)->title ?? 'Unknown Course';
            $description = $quiz->description ?? 'No description provided.';
            $quiz->short_description = Str::limit(strip_tags($description), 140);
            $quiz->pass_rate = $quiz->attempts_count > 0
                ? (int) round(($quiz->passed_attempts_count / $quiz->attempts_count) * 100)
                : null;

            return $quiz;
        });

        return view('instructor.quizzes.index', [
            'user' => $user,
            'quizzes' => $quizzes,
            'summary' => $summary,
            'selectedCourseId' => $courseId,
            'searchTerm' => $search,
            'courses' => $user->courses()->select(['id', 'title'])->orderBy('title')->get(),
        ]);
    }

    public function show(Request $request, Quiz $quiz): View|RedirectResponse
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $quiz->load('topic.course');
        $this->authorizeQuiz($quiz, $user->id);

        $status = $request->input('status');
        $search = trim((string) $request->input('search'));

        $attemptsQuery = QuizAttempt::query()
            ->with(['user:id,name,email', 'enrollment:id,status'])
            ->where('quiz_id', $quiz->id)
            ->whereHas('quiz.topic.course', fn($q) => $q->where('instructor_id', $user->id));

        if ($status === 'passed') {
            $attemptsQuery->where('passed', true);
        } elseif ($status === 'failed') {
            $attemptsQuery->where('passed', false);
        }

        if ($search !== '') {
            $attemptsQuery->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $attempts = $attemptsQuery
            ->orderByDesc('completed_at')
            ->paginate(15)
            ->withQueryString();

        $attempts->getCollection()->transform(function (QuizAttempt $attempt) {
            $attempt->status_label = $attempt->passed ? 'Passed' : 'Failed';
            $attempt->status_class = $attempt->passed ? 'passed' : 'failed';
            $attempt->completed_at_formatted = optional($attempt->completed_at)?->format('M d, Y h:i A');
            $attempt->started_at_formatted = optional($attempt->started_at)?->format('M d, Y h:i A');
            $attempt->time_taken_formatted = $this->formatSeconds($attempt->time_taken);

            return $attempt;
        });

        $stats = $this->summarizeAttempts($quiz->id);

        return view('instructor.quizzes.show', [
            'user' => $user,
            'quiz' => $quiz,
            'attempts' => $attempts,
            'statusFilter' => $status,
            'searchTerm' => $search,
            'stats' => $stats,
            'statusOptions' => [
                '' => 'All Attempts',
                'passed' => 'Passed',
                'failed' => 'Failed',
            ],
        ]);
    }

    private function authorizeQuiz(Quiz $quiz, int $instructorId): void
    {
        $quiz->loadMissing('topic.course');

        if (!$quiz->topic || !$quiz->topic->course || $quiz->topic->course->instructor_id != $instructorId) {
            abort(403, 'You are not authorized to access this quiz.');
        }
    }

    private function summarizeQuizzes($quizIds): array
    {
        $ids = collect($quizIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [
                'total_quizzes' => 0,
                'total_attempts' => 0,
                'passed_attempts' => 0,
                'average_score' => null,
            ];
        }

        $attemptsQuery = QuizAttempt::query()->whereIn('quiz_id', $ids);

        $totalAttempts = (clone $attemptsQuery)->count();

        return [
            'total_quizzes' => $ids->count(),
            'total_attempts' => $totalAttempts,
            'passed_attempts' => (clone $attemptsQuery)->where('passed', true)->count(),
            'average_score' => $totalAttempts > 0 ? (int) round((clone $attemptsQuery)->avg('score')) : null,
        ];
    }

    private function summarizeAttempts(int $quizId): array
    {
        $attempts = QuizAttempt::query()
            ->where('quiz_id', $quizId)
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

    private function formatSeconds(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        $interval = Carbon::now()->startOfDay()->addSeconds($seconds)->diff(Carbon::now()->startOfDay());

        return $interval->format('%H:%I:%S');
    }
}
