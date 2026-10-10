<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class QuizAttemptController extends Controller
{
    /**
     * List all quizzes for the authenticated instructor with summary.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $courseId = $request->integer('course_id');

        $quizzesQuery = Quiz::query()
            ->with(['topic.course:id,title,instructor_id,featured_image'])
            ->withCount([
                'attempts as attempts_count',
                'attempts as passed_attempts_count' => fn($q) => $q->where('passed', true),
            ])
            ->whereHas('topic.course', fn($q) => $q->where('instructor_id', $user->id));

        if ($courseId) {
            $quizzesQuery->whereHas('topic.course', fn($q) => $q->where('id', $courseId));
        }

        $quizIds = (clone $quizzesQuery)->select('quizzes.id')->pluck('quizzes.id');
        $summary = $this->summarizeQuizzes($quizIds);

        $quizzes = $quizzesQuery->orderBy('title')->paginate(15);

        $quizzes->getCollection()->transform(function (Quiz $quiz) {
            $quiz->course_title = optional($quiz->topic->course)->title ?? 'Unknown Course';
            $quiz->course_image = optional($quiz->topic->course)->featured_image;
            $description = $quiz->description ?? '';
            $quiz->short_description = Str::limit(strip_tags($description), 140);
            $quiz->pass_rate = $quiz->attempts_count > 0
                ? (int) round(($quiz->passed_attempts_count / $quiz->attempts_count) * 100)
                : null;
            return $quiz;
        });

        $courses = $user->courses()->select(['id', 'title'])->orderBy('title')->get();

        return response()->json([
            'quizzes' => $quizzes,
            'summary' => $summary,
            'courses' => $courses,
        ]);
    }

    /**
     * Show a single quiz with its attempts and stats.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $status = $request->input('status'); // passed | failed

        $quiz = Quiz::with(['topic.course:id,title,instructor_id'])->findOrFail($id);

        // Authorize
        if (!$quiz->topic || !$quiz->topic->course || $quiz->topic->course->instructor_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $attemptsQuery = QuizAttempt::query()
            ->with(['user:id,name,email,profile_photo'])
            ->where('quiz_id', $quiz->id);

        if ($status === 'passed') {
            $attemptsQuery->where('passed', true);
        } elseif ($status === 'failed') {
            $attemptsQuery->where('passed', false);
        }

        $attempts = $attemptsQuery->orderByDesc('completed_at')->paginate(15);

        $attempts->getCollection()->transform(function (QuizAttempt $attempt) {
            $attempt->status_label = $attempt->passed ? 'Passed' : 'Failed';
            $attempt->completed_at_formatted = optional($attempt->completed_at)?->format('M d, Y h:i A');
            $attempt->time_taken_formatted = $this->formatSeconds($attempt->time_taken);
            return $attempt;
        });

        $stats = $this->summarizeAttempts($quiz->id);

        return response()->json([
            'quiz'     => $quiz,
            'attempts' => $attempts,
            'stats'    => $stats,
        ]);
    }

    private function summarizeQuizzes($quizIds): array
    {
        $ids = collect($quizIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [
                'total_quizzes'   => 0,
                'total_attempts'  => 0,
                'passed_attempts' => 0,
                'average_score'   => null,
            ];
        }

        $q = QuizAttempt::query()->whereIn('quiz_id', $ids);
        $total = (clone $q)->count();

        return [
            'total_quizzes'   => $ids->count(),
            'total_attempts'  => $total,
            'passed_attempts' => (clone $q)->where('passed', true)->count(),
            'average_score'   => $total > 0 ? (int) round((clone $q)->avg('score')) : null,
        ];
    }

    private function summarizeAttempts(int $quizId): array
    {
        $attempts = QuizAttempt::where('quiz_id', $quizId)->get();

        if ($attempts->isEmpty()) {
            return [
                'total_attempts'  => 0,
                'passed_attempts' => 0,
                'failed_attempts' => 0,
                'average_score'   => null,
                'best_score'      => null,
                'average_time'    => null,
            ];
        }

        $total = $attempts->count();
        $passed = $attempts->where('passed', true)->count();

        return [
            'total_attempts'  => $total,
            'passed_attempts' => $passed,
            'failed_attempts' => $total - $passed,
            'average_score'   => (int) round($attempts->avg('score')),
            'best_score'      => $attempts->max('score'),
            'average_time'    => $attempts->avg('time_taken') ? $this->formatSeconds((int) $attempts->avg('time_taken')) : null,
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
