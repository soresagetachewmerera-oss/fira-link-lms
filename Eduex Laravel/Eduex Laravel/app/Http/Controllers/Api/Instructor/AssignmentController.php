<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AssignmentController extends Controller
{
    /**
     * List all assignments for the authenticated instructor with summary.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $courseId = $request->integer('course_id');

        $assignmentsQuery = Assignment::query()
            ->with(['topic.course:id,title,instructor_id,featured_image'])
            ->withCount([
                'submissions as submissions_count',
                'submissions as pending_submissions_count' => fn($q) => $q->where('status', AssignmentSubmission::STATUS_PENDING),
                'submissions as graded_submissions_count' => fn($q) => $q->where('status', AssignmentSubmission::STATUS_GRADED),
                'submissions as returned_submissions_count' => fn($q) => $q->where('status', AssignmentSubmission::STATUS_RETURNED),
            ])
            ->whereHas('topic.course', fn($q) => $q->where('instructor_id', $user->id));

        if ($courseId) {
            $assignmentsQuery->whereHas('topic.course', fn($q) => $q->where('id', $courseId));
        }

        // Build summary from the same query scope
        $assignmentIds = (clone $assignmentsQuery)->select('assignments.id')->pluck('assignments.id');
        $summary = $this->summarizeAssignments($assignmentIds);

        $assignments = $assignmentsQuery->orderBy('title')->paginate(15);

        $assignments->getCollection()->transform(function (Assignment $assignment) {
            $raw = $assignment->description ?? $assignment->instructions ?? '';
            $assignment->short_description = Str::limit(strip_tags($raw), 140);
            $assignment->course_title = optional($assignment->topic->course)->title ?? 'Unknown Course';
            $assignment->course_image = optional($assignment->topic->course)->featured_image;
            return $assignment;
        });

        // Courses list for filter dropdown
        $courses = $user->courses()->select(['id', 'title'])->orderBy('title')->get();

        return response()->json([
            'assignments' => $assignments,
            'summary'     => $summary,
            'courses'     => $courses,
        ]);
    }

    /**
     * Show a single assignment with its submissions.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $status = $request->input('status');

        $assignment = Assignment::with(['topic.course:id,title,instructor_id'])
            ->withCount([
                'submissions as submissions_count',
                'submissions as pending_submissions_count' => fn($q) => $q->where('status', AssignmentSubmission::STATUS_PENDING),
                'submissions as graded_submissions_count' => fn($q) => $q->where('status', AssignmentSubmission::STATUS_GRADED),
                'submissions as returned_submissions_count' => fn($q) => $q->where('status', AssignmentSubmission::STATUS_RETURNED),
            ])
            ->findOrFail($id);

        // Authorize
        if (!$assignment->topic || !$assignment->topic->course || $assignment->topic->course->instructor_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $submissionsQuery = AssignmentSubmission::query()
            ->with(['user:id,name,email,profile_photo', 'files'])
            ->where('assignment_id', $assignment->id);

        if ($status) {
            $submissionsQuery->where('status', $status);
        }

        $submissions = $submissionsQuery->orderByDesc('submitted_at')->paginate(15);

        $submissions->getCollection()->transform(function ($submission) {
            if ($submission->user) {
                $submission->user->email = $this->maskEmail($submission->user->email);
            }
            return $submission;
        });

        return response()->json([
            'assignment'  => $assignment,
            'submissions' => $submissions,
        ]);
    }

    /**
     * Show a single submission detail.
     */
    public function showSubmission(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $submission = AssignmentSubmission::with([
            'assignment.topic.course:id,title,instructor_id',
            'user:id,name,email,profile_photo',
            'files',
        ])->findOrFail($id);

        // Authorize
        $course = optional($submission->assignment->topic)->course;
        if (!$course || $course->instructor_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Append file URLs
        $submission->files->transform(function ($file) {
            $file->file_url = $file->file_url; // triggers accessor
            return $file;
        });

        if ($submission->user) {
            $submission->user->email = $this->maskEmail($submission->user->email);
        }

        return response()->json([
            'submission' => $submission,
        ]);
    }

    /**
     * Grade / update a submission.
     */
    public function updateSubmission(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $submission = AssignmentSubmission::with('assignment.topic.course')->findOrFail($id);

        // Authorize
        $course = optional($submission->assignment->topic)->course;
        if (!$course || $course->instructor_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status'           => 'required|in:pending,graded,returned',
            'grade'            => 'nullable|integer|min:0|max:100',
            'instructor_notes' => 'nullable|string|max:2000',
        ]);

        $submission->status = $validated['status'];
        $submission->grade = $validated['grade'] ?? null;
        $submission->instructor_notes = $validated['instructor_notes'] ?? null;
        $submission->save();

        return response()->json([
            'message'    => 'Submission updated successfully.',
            'submission' => $submission->fresh(['user:id,name,email,profile_photo', 'files']),
        ]);
    }

    private function summarizeAssignments($assignmentIds): array
    {
        $ids = collect($assignmentIds)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [
                'total_assignments'    => 0,
                'total_submissions'    => 0,
                'pending_submissions'  => 0,
                'graded_submissions'   => 0,
                'returned_submissions' => 0,
            ];
        }

        $q = AssignmentSubmission::query()->whereIn('assignment_id', $ids);

        return [
            'total_assignments'    => $ids->count(),
            'total_submissions'    => (clone $q)->count(),
            'pending_submissions'  => (clone $q)->where('status', AssignmentSubmission::STATUS_PENDING)->count(),
            'graded_submissions'   => (clone $q)->where('status', AssignmentSubmission::STATUS_GRADED)->count(),
            'returned_submissions' => (clone $q)->where('status', AssignmentSubmission::STATUS_RETURNED)->count(),
        ];
    }
    
    /**
     * Mask email address for privacy
     */
    private function maskEmail($email)
    {
        if (!$email) return '';
        $parts = explode('@', $email);
        if (count($parts) < 2) return $email;
        
        $name = $parts[0];
        $domain = $parts[1];
        
        $len = strlen($name);
        if ($len <= 2) {
            return $name . '***@' . $domain;
        }
        
        return substr($name, 0, 1) . str_repeat('*', min($len - 2, 5)) . substr($name, -1) . '@' . $domain;
    }
}
