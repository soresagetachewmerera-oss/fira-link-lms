<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Assignments Controller
 *
 * This controller handles the assignments functionality.
 *
 * @package App\Http\Controllers\Student
 */
class AssignmentsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->currentUser();

        $courseId = $request->integer('course_id');
        $status = $request->input('status');
        $search = trim((string) $request->input('search'));

        $courses = Enrollment::with('course:id,title')
            ->where('user_id', $user->id)
            ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])
            ->get()
            ->pluck('course')
            ->filter()
            ->unique('id')
            ->values();

        $baseQuery = Assignment::query()
            ->with(['topic.course:id,title'])
            ->whereHas('topic.course.enrollments', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED]);
            })
            ->whereHas('submissions', fn ($query) => $query->where('user_id', $user->id));

        if ($courseId) {
            $baseQuery->whereHas('topic.course', fn ($query) => $query->where('id', $courseId));
        }

        if ($search !== '') {
            $baseQuery->where('title', 'like', '%' . $search . '%');
        }

        $allAssignmentIds = (clone $baseQuery)->select('assignments.id')->pluck('assignments.id');

        $statusFilter = $this->normalizeStatus($status);

        if ($statusFilter) {
            $baseQuery->whereHas('submissions', function ($query) use ($user, $statusFilter) {
                $query->where('user_id', $user->id)->where('status', $statusFilter);
            });
        }

        $assignments = $baseQuery
            ->withCount('files')
            ->with(['submissions' => function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orderByDesc('submitted_at')
                    ->orderByDesc('created_at');
            }])
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        $statusLabels = [
            AssignmentSubmission::STATUS_PENDING => 'Pending Review',
            AssignmentSubmission::STATUS_GRADED => 'Graded',
            AssignmentSubmission::STATUS_RETURNED => 'Returned for Revision',
        ];

        $statusClasses = [
            AssignmentSubmission::STATUS_PENDING => 'pending',
            AssignmentSubmission::STATUS_GRADED => 'graded',
            AssignmentSubmission::STATUS_RETURNED => 'returned',
        ];

        $assignments->getCollection()->transform(function (Assignment $assignment) use ($statusLabels, $statusClasses) {
            /** @var AssignmentSubmission|null $latestSubmission */
            $latestSubmission = $assignment->submissions->first();

            $assignment->student_submission = [
                'status' => $latestSubmission->status,
                'status_label' => $statusLabels[$latestSubmission->status] ?? ucfirst($latestSubmission->status),
                'status_class' => $statusClasses[$latestSubmission->status] ?? 'pending',
                'grade' => $latestSubmission->grade,
                'submitted_at' => optional($latestSubmission->submitted_at ?? $latestSubmission->created_at)?->format('M d, Y h:i A'),
            ];

            $rawDescription = $assignment->description ?? $assignment->instructions ?? 'No description provided.';
            $assignment->short_description = Str::limit(strip_tags($rawDescription), 140);

            return $assignment;
        });

        $summary = $this->buildSummary($allAssignmentIds, $user->id);

        return view('student.assignments.index', [
            'user' => $user,
            'assignments' => $assignments,
            'courses' => $courses,
            'selectedCourseId' => $courseId,
            'statusFilter' => $statusFilter,
            'statusOptions' => [
                '' => 'All statuses',
                AssignmentSubmission::STATUS_PENDING => 'Pending Review',
                AssignmentSubmission::STATUS_GRADED => 'Graded',
                AssignmentSubmission::STATUS_RETURNED => 'Returned',
            ],
            'summary' => $summary,
            'searchTerm' => $search,
        ]);
    }

    private function normalizeStatus(?string $status): ?string
    {
        $status = $status !== null ? trim($status) : null;

        $allowed = [
            AssignmentSubmission::STATUS_PENDING,
            AssignmentSubmission::STATUS_GRADED,
            AssignmentSubmission::STATUS_RETURNED,
        ];

        if ($status === null || $status === '') {
            return null;
        }

        return in_array($status, $allowed, true) ? $status : null;
    }

    private function buildSummary($assignmentIds, int $userId): array
    {
        $ids = collect($assignmentIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [
                'total_submissions' => 0,
                'pending_assignments' => 0,
                'graded_assignments' => 0,
                'returned_assignments' => 0,
            ];
        }

        $submissions = AssignmentSubmission::query()
            ->where('user_id', $userId)
            ->whereIn('assignment_id', $ids)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('assignment_id')
            ->map(fn ($group) => $group->first());

        $submitted = $submissions->count();
        $pending = $submissions->where('status', AssignmentSubmission::STATUS_PENDING)->count();
        $graded = $submissions->where('status', AssignmentSubmission::STATUS_GRADED)->count();
        $returned = $submissions->where('status', AssignmentSubmission::STATUS_RETURNED)->count();

        return [
            'total_submissions' => $submitted,
            'pending_assignments' => $pending,
            'graded_assignments' => $graded,
            'returned_assignments' => $returned,
        ];
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
