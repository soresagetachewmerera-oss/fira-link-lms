<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpdateAssignmentSubmissionRequest;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Assignments Controller
 *
 * This controller handles the assignments functionality.
 *
 * @package App\Http\Controllers\Instructor
 */
class AssignmentsController extends Controller
{
    /**
     * Display the assignments index
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\View
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $courses = $user->courses()
            ->select(['id', 'title'])
            ->orderBy('title')
            ->get();

        $courseId = $request->integer('course_id');

        $assignmentsQuery = Assignment::query()
            ->with([
                'topic.course:id,title,instructor_id',
            ])
            ->withCount([
                'submissions as submissions_count',
                'submissions as pending_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_PENDING),
                'submissions as graded_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_GRADED),
                'submissions as returned_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_RETURNED),
            ])
            ->whereHas('topic.course', function ($query) use ($user) {
                $query->where('instructor_id', $user->id);
            });

        if ($courseId) {
            $assignmentsQuery->whereHas('topic.course', function ($query) use ($courseId) {
                $query->where('id', $courseId);
            });
        }

        $assignmentIds = (clone $assignmentsQuery)
            ->select('assignments.id')
            ->pluck('assignments.id');

        $summary = $this->summarizeAssignments($assignmentIds);

        $assignments = $assignmentsQuery
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        $assignments->getCollection()->transform(function (Assignment $assignment) {
            $raw = $assignment->description ?? $assignment->instructions ?? 'No description provided.';
            $assignment->short_description = Str::limit(strip_tags($raw), 140);

            return $assignment;
        });

        return view('instructor.assignments.index', [
            'user' => $user,
            'assignments' => $assignments,
            'courses' => $courses,
            'selectedCourseId' => $courseId,
            'summary' => $summary,
        ]);
    }

    /**
     * Display the assignment show page
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Assignment $assignment
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\View
     */
    public function show(Request $request, Assignment $assignment): View|RedirectResponse
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $assignment->loadMissing('topic.course');
        $this->authorizeAssignment($assignment, $user->id);

        $assignment->loadCount([
            'submissions as submissions_count',
            'submissions as pending_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_PENDING),
            'submissions as graded_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_GRADED),
            'submissions as returned_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_RETURNED),
        ]);

        $status = $request->input('status');
        $search = $request->input('search');

        $submissionsQuery = AssignmentSubmission::query()
            ->with(['user', 'files', 'enrollment'])
            ->where('assignment_id', $assignment->id)
            ->whereHas('assignment.topic.course', function ($query) use ($user) {
                $query->where('instructor_id', $user->id);
            });

        if ($status) {
            $submissionsQuery->where('status', $status);
        }

        if ($search) {
            $submissionsQuery->whereHas('user', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $submissions = $submissionsQuery
            ->orderByDesc('submitted_at')
            ->paginate(10)
            ->withQueryString();

        $statusOptions = [
            AssignmentSubmission::STATUS_PENDING => 'Pending',
            AssignmentSubmission::STATUS_GRADED => 'Graded',
            AssignmentSubmission::STATUS_RETURNED => 'Returned',
        ];

        $statusClasses = [
            AssignmentSubmission::STATUS_PENDING => 'pending',
            AssignmentSubmission::STATUS_GRADED => 'graded',
            AssignmentSubmission::STATUS_RETURNED => 'returned',
        ];

        $submissions->getCollection()->transform(function (AssignmentSubmission $submission) use ($statusClasses) {
            $submission->status_class = $statusClasses[$submission->status] ?? 'pending';

            return $submission;
        });

        return view('instructor.assignments.show', [
            'user' => $user,
            'assignment' => $assignment,
            'submissions' => $submissions,
            'statusFilter' => $status,
            'searchTerm' => $search,
            'statusOptions' => $statusOptions,
            'statusClasses' => $statusClasses,
        ]);
    }

    /**
     * Display the assignment submission show page
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\AssignmentSubmission $submission
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\View
     */
    public function showSubmission(Request $request, AssignmentSubmission $submission): View|RedirectResponse
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $submission->loadMissing([
            'assignment.topic.course',
            'user',
            'files',
        ]);

        $this->authorizeAssignment($submission->assignment, $user->id);

        $submission->assignment->loadCount([
            'submissions as submissions_count',
            'submissions as pending_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_PENDING),
            'submissions as graded_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_GRADED),
            'submissions as returned_submissions_count' => fn($query) => $query->where('status', AssignmentSubmission::STATUS_RETURNED),
        ]);

        return view('instructor.assignments.submissions.show', [
            'user' => $user,
            'submission' => $submission,
            'assignment' => $submission->assignment,
            'statusOptions' => [
                AssignmentSubmission::STATUS_PENDING => 'Pending',
                AssignmentSubmission::STATUS_GRADED => 'Graded',
                AssignmentSubmission::STATUS_RETURNED => 'Returned',
            ],
            'statusClasses' => [
                AssignmentSubmission::STATUS_PENDING => 'pending',
                AssignmentSubmission::STATUS_GRADED => 'graded',
                AssignmentSubmission::STATUS_RETURNED => 'returned',
            ],
        ]);
    }

    /**
     * Update the assignment submission
     *
     * @param \App\Http\Requests\Instructor\UpdateAssignmentSubmissionRequest $request
     * @param \App\Models\AssignmentSubmission $submission
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSubmission(UpdateAssignmentSubmissionRequest $request, AssignmentSubmission $submission): RedirectResponse
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $submission->loadMissing('assignment.topic.course');
        $this->authorizeAssignment($submission->assignment, $user->id);

        $validated = $request->validated();

        $submission->status = $validated['status'];
        $submission->grade = $validated['grade'] ?? null;
        $submission->instructor_notes = $validated['instructor_notes'] ?? null;
        $submission->save();

        ToastMagic::success('Submission updated successfully.');

        return redirect()->back();
    }

    /**
     * Authorize the assignment
     *
     * @param \App\Models\Assignment $assignment
     * @param int $instructorId
     * @return void
     */
    private function authorizeAssignment(Assignment $assignment, int $instructorId): void
    {
        $assignment->loadMissing('topic.course');

        if (!$assignment->topic || !$assignment->topic->course || $assignment->topic->course->instructor_id != $instructorId) {
            abort(403, 'You are not authorized to manage this assignment.');
        }
    }

    /**
     * @param \Illuminate\Support\Collection<int,int>|\Illuminate\Contracts\Support\Arrayable<int,int>|array<int,int> $assignmentIds
     */
    private function summarizeAssignments($assignmentIds): array
    {
        $ids = collect($assignmentIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [
                'total_assignments' => 0,
                'total_submissions' => 0,
                'pending_submissions' => 0,
                'graded_submissions' => 0,
                'returned_submissions' => 0,
            ];
        }

        $submissionsQuery = AssignmentSubmission::query()
            ->whereIn('assignment_id', $ids);

        return [
            'total_assignments' => $ids->count(),
            'total_submissions' => (clone $submissionsQuery)->count(),
            'pending_submissions' => (clone $submissionsQuery)->where('status', AssignmentSubmission::STATUS_PENDING)->count(),
            'graded_submissions' => (clone $submissionsQuery)->where('status', AssignmentSubmission::STATUS_GRADED)->count(),
            'returned_submissions' => (clone $submissionsQuery)->where('status', AssignmentSubmission::STATUS_RETURNED)->count(),
        ];
    }
}

