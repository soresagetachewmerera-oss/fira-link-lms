<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseTopic;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Assignment;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\Payment;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Course Controller
 *
 * This controller handles the management of courses.
 *
 * @package App\Http\Controllers\Admin
 */
class CourseController extends Controller
{
    /**
     * Display all courses
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $query = Course::with(['instructor', 'enrollments']);

        if (request()->has('search') && request()->search) {
            $search = request()->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if (request()->has('status') && request()->status) {
            if (request()->status === 'archived') {
                $query->where('status', 'archived');
            } else {
                $query->where('status', request()->status);
            }
        } else {
            $query->where('status', '!=', 'archived');
        }

        if (request()->has('visibility') && request()->visibility) {
            $query->where('visibility', request()->visibility);
        }

        if (request()->has('pricing_model') && request()->pricing_model) {
            $query->where('pricing_model', request()->pricing_model);
        }

        $courses = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $pendingCount = Course::where('status', 'pending')->count();

        return view('admin.courses.index', compact('courses', 'pendingCount'));
    }

    /**
     * Display pending courses for approval
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function pending()
    {
        $courses = Course::where('status', 'pending')
            ->with('instructor')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.courses.pending', compact('courses'));
    }

    /**
     * Approve a course
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve($id)
    {
        $course = Course::findOrFail($id);

        $request = request();
        $reason = $request->input('reason', '');

        $course->status = 'published';
        $course->approval_reason = $reason;
        $course->rejection_reason = null;
        $course->save();

        $message = "Course '{$course->title}' has been approved and published successfully.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }

        ToastMagic::success($message);
        return back();
    }

    /**
     * Reject a course
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject($id)
    {
        $course = Course::findOrFail($id);

        $request = request();
        $reason = $request->input('reason', '');

        $courseTitle = $course->title;
        $course->status = 'draft';
        $course->rejection_reason = $reason;
        $course->approval_reason = null;
        $course->save();

        $message = "Course '{$courseTitle}' has been rejected and set back to draft.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }

        ToastMagic::info($message);
        return back();
    }

    /**
     * Display course details
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        $course = Course::with([
            'instructor',
            'topics.lessons',
            'topics.quizzes',
            'topics.assignments',
            'enrollments',
            'reviews.user',
            'reviews.replies.user'
        ])->findOrFail($id);

        $lessonCount = $course->topics->sum(function ($topic) {
            return $topic->lessons->count();
        });

        $quizCount = $course->topics->sum(function ($topic) {
            return $topic->quizzes->count();
        });

        $assignmentCount = $course->topics->sum(function ($topic) {
            return $topic->assignments->count();
        });

        $totalDuration = $course->topics->sum(function ($topic) {
            return $topic->lessons->sum(function ($lesson) {
                return $lesson->duration ?? 0;
            });
        });

        $reviews = $course->reviews;
        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;

        return view('admin.courses.show', compact(
            'course',
            'lessonCount',
            'quizCount',
            'assignmentCount',
            'totalDuration',
            'totalReviews',
            'averageRating'
        ));
    }

    /**
     * Update course status
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateStatus($id)
    {
        $course = Course::findOrFail($id);
        $request = request();

        $newStatus = $request->input('status');
        $oldStatus = $course->status;

        $validStatuses = ['draft', 'pending', 'published', 'archived'];
        if (!in_array($newStatus, $validStatuses)) {
            ToastMagic::error('Invalid status selected.');
            return back();
        }

        $course->status = $newStatus;

        if ($newStatus === 'archived') {
            $course->deleted_date = now();
        } else {
            $course->deleted_date = null;
        }

        if ($newStatus !== 'published' && $oldStatus === 'pending') {
            $course->approval_reason = null;
        }
        if ($newStatus !== 'draft' && $oldStatus === 'pending') {
            $course->rejection_reason = null;
        }

        $course->save();

        $statusLabels = [
            'draft' => 'Draft',
            'pending' => 'Pending',
            'published' => 'Published',
            'archived' => 'Archived'
        ];

        ToastMagic::success("Course '{$course->title}' status has been changed to {$statusLabels[$newStatus]}.");
        return back();
    }

    /**
     * Archive a course (soft delete)
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $course = Course::findOrFail($id);
        $courseTitle = $course->title;

        $course->status = 'archived';
        $course->deleted_date = now();
        $course->save();

        ToastMagic::success("Course '{$courseTitle}' has been archived successfully.");
        return back();
    }
    /**
     * Permanently delete a course and all related data (3-step confirmation)
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function fullDelete($id)
    {
        $course = Course::with(['topics.lessons', 'topics.quizzes.questions', 'topics.assignments.files', 'enrollments.payment', 'reviews'])->findOrFail($id);
        $courseTitle = $course->title;

        try {
            DB::beginTransaction();

            // 1. Delete associated media files for the course
            if ($course->featured_image) {
                Storage::disk('public')->delete($course->featured_image);
            }
            if ($course->intro_video) {
                Storage::disk('public')->delete($course->intro_video);
            }

            // 2. Cascade delete topics, lessons, quizzes, assignments
            foreach ($course->topics as $topic) {
                // Delete lessons and their videos
                foreach ($topic->lessons as $lesson) {
                    if ($lesson->video_path) {
                        Storage::disk('public')->delete($lesson->video_path);
                    }
                    $lesson->progress()->delete();
                    $lesson->delete();
                }

                // Delete quizzes, questions, and attempts
                foreach ($topic->quizzes as $quiz) {
                    foreach ($quiz->questions as $question) {
                        $question->delete();
                    }
                    
                    foreach ($quiz->attempts as $attempt) {
                        $attempt->answers()->delete();
                        $attempt->delete();
                    }
                    $quiz->delete();
                }

                // Delete assignments, files, and submissions
                foreach ($topic->assignments as $assignment) {
                    foreach ($assignment->files as $file) {
                        if ($file->file_path) {
                            Storage::disk('public')->delete($file->file_path);
                        }
                        $file->delete();
                    }
                    foreach ($assignment->submissions as $submission) {
                        // Delete submission files
                        foreach ($submission->files as $subFile) {
                            if ($subFile->file_path) {
                                Storage::disk('public')->delete($subFile->file_path);
                            }
                            $subFile->delete();
                        }
                        $submission->delete();
                    }
                    $assignment->delete();
                }

                $topic->delete();
            }

            // 3. Delete enrollments and their related data
            foreach ($course->enrollments as $enrollment) {
                if ($enrollment->payment && !$enrollment->payment->bundle_id) {
                    if ($enrollment->payment->receipt_file) {
                        Storage::disk('public')->delete($enrollment->payment->receipt_file);
                    }
                    $enrollment->payment->delete();
                }
                
                $enrollment->lessonProgress()->delete();
                $enrollment->quizAttempts()->delete();
                $enrollment->assignmentSubmissions()->delete();
                $enrollment->delete();
            }

            // 4. Delete reviews and replies
            foreach ($course->reviews as $review) {
                $review->replies()->delete();
                $review->delete();
            }

            // 5. Remove from bundles
            DB::table('bundle_courses')->where('course_id', $course->id)->delete();

            // 6. Finally delete the course record
            $course->delete();

            DB::commit();

            ToastMagic::success("Course '{$courseTitle}' and all its related data have been permanently deleted.");
            return redirect()->route('admin.courses.index');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Course Deletion Error: " . $e->getMessage());
            ToastMagic::error("An error occurred while deleting the course: " . $e->getMessage());
            return back();
        }
    }
}

