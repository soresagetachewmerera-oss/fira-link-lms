<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Assignment Submission Controller
 *
 * This controller handles the management of assignment submissions.
 *
 * @package App\Http\Controllers\Admin
 */
class AssignmentSubmissionController extends Controller
{
    /**
     * Display all assignment submissions
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $query = AssignmentSubmission::query()
            ->with([
                'user:id,name,email,profile_photo',
                'assignment:id,title,course_topic_id',
                'assignment.topic:id,title,course_id',
                'assignment.topic.course:id,title,instructor_id',
                'assignment.topic.course.instructor:id,name',
                'enrollment:id,course_id,user_id,status',
            ])
            ->withCount('files');

        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('id', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('assignment', function ($assignmentQuery) use ($search) {
                        $assignmentQuery->where('title', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('assignment.topic.course', function ($courseQuery) use ($search) {
                        $courseQuery->where('title', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($status = $request->query('status')) {
            $validStatuses = [
                AssignmentSubmission::STATUS_PENDING,
                AssignmentSubmission::STATUS_GRADED,
                AssignmentSubmission::STATUS_RETURNED,
            ];

            if (in_array($status, $validStatuses, true)) {
                $query->where('status', $status);
            }
        }

        if ($courseId = $request->query('course_id')) {
            $query->whereHas('assignment.topic.course', function ($courseQuery) use ($courseId) {
                $courseQuery->where('id', $courseId);
            });
        }

        if ($assignmentId = $request->query('assignment_id')) {
            $query->where('assignment_id', $assignmentId);
        }

        if ($studentId = $request->query('student_id')) {
            $query->where('user_id', $studentId);
        }

        if ($instructorId = $request->query('instructor_id')) {
            $query->whereHas('assignment.topic.course', function ($courseQuery) use ($instructorId) {
                $courseQuery->where('instructor_id', $instructorId);
            });
        }

        if ($dateFrom = $request->query('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->query('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $submissions = $query
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $totalSubmissions = AssignmentSubmission::count();
        $pendingSubmissions = AssignmentSubmission::where('status', AssignmentSubmission::STATUS_PENDING)->count();
        $gradedSubmissions = AssignmentSubmission::where('status', AssignmentSubmission::STATUS_GRADED)->count();
        $returnedSubmissions = AssignmentSubmission::where('status', AssignmentSubmission::STATUS_RETURNED)->count();
        $averageGradeRaw = AssignmentSubmission::whereNotNull('grade')->avg('grade');
        $averageGrade = $averageGradeRaw !== null ? round((float) $averageGradeRaw, 1) : null;
        $todaySubmissions = AssignmentSubmission::whereDate('created_at', Carbon::today())->count();

        $courses = Course::orderBy('title')->get(['id', 'title']);
        $assignments = Assignment::with([
            'topic:id,course_id,title',
            'topic.course:id,title,instructor_id',
        ])->orderBy('title')->get(['id', 'course_topic_id', 'title']);
        $students = User::where('role', 'student')->orderBy('name')->get(['id', 'name']);
        $instructors = User::where('role', 'instructor')->orderBy('name')->get(['id', 'name']);

        $pageTitle = 'Assignment Submissions';
        $breadcrumbItems = [
            ['name' => 'Dashboard', 'route' => 'admin.dashboard'],
            ['name' => 'Assignment Submissions', 'route' => null],
        ];

        return view('admin.assignment-submissions.index', compact(
            'submissions',
            'pageTitle',
            'breadcrumbItems',
            'courses',
            'assignments',
            'students',
            'instructors',
            'totalSubmissions',
            'pendingSubmissions',
            'gradedSubmissions',
            'returnedSubmissions',
            'averageGrade',
            'todaySubmissions'
        ));
    }

    /**
     * Display a assignment submission details
     *
     * @param \App\Models\AssignmentSubmission $assignmentSubmission
     * @return \Illuminate\Contracts\View\View
     */
    public function show(AssignmentSubmission $assignmentSubmission): View
    {
        $assignmentSubmission->load([
            'user',
            'assignment.topic.course.instructor',
            'assignment.topic.course',
            'files',
            'enrollment.course',
        ]);

        $pageTitle = 'Assignment Submission #' . $assignmentSubmission->id;
        $breadcrumbItems = [
            ['name' => 'Dashboard', 'route' => 'admin.dashboard'],
            ['name' => 'Assignment Submissions', 'route' => 'admin.assignment-submissions.index'],
            ['name' => 'Submission #' . $assignmentSubmission->id, 'route' => null],
        ];

        return view('admin.assignment-submissions.show', [
            'submission' => $assignmentSubmission,
            'pageTitle' => $pageTitle,
            'breadcrumbItems' => $breadcrumbItems,
        ]);
    }
}

