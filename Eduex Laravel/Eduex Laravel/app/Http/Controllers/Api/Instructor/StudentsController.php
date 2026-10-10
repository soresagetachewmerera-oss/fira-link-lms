<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StudentsController extends Controller
{
    public function index(Request $request)
    {
        $instructor = Auth::user();
        $courseId = $request->query('course');
        $search = $request->query('search');
        $sort = $request->query('sort', 'newest');

        // Get instructor's course IDs
        $instructorCourseIds = Course::where('instructor_id', $instructor->id)->pluck('id');

        // Query enrollments for these courses
        $query = Enrollment::whereIn('course_id', $instructorCourseIds);

        if ($courseId && $courseId !== 'all') {
            $query->where('course_id', $courseId);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $enrollments = $query->with(['user', 'course'])->get();

        // Group by user to avoid duplicate students in the list
        $studentsData = $enrollments->groupBy('user_id')->map(function ($userEnrollments) {
            $user = $userEnrollments->first()->user;
            
            // Calculate average progress across all instructor's courses this student is enrolled in
            $totalProgress = 0;
            foreach ($userEnrollments as $enrollment) {
                $totalLessons = Lesson::whereHas('topic', function($q) use ($enrollment) {
                    $q->where('course_id', $enrollment->course_id);
                })->count();
                
                $completedLessons = LessonProgress::where('user_id', $user->id)
                    ->where('enrollment_id', $enrollment->id)
                    ->where('is_completed', true)
                    ->count();
                
                $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
                $totalProgress += $progress;
            }
            
            $avgProgress = count($userEnrollments) > 0 ? round($totalProgress / count($userEnrollments)) : 0;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $this->maskEmail($user->email),
                'avatar' => $user->profile_photo ? asset('storage/' . $user->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name),
                'enrolled_courses' => count($userEnrollments),
                'enrollment_date' => $userEnrollments->min('created_at')->format('Y-m-d'),
                'progress' => $avgProgress,
                'last_active' => $user->last_login_at ? Carbon::parse($user->last_login_at)->format('Y-m-d') : 'Never',
                'status' => 'active'
            ];
        })->values()->toArray();

        // Apply sorting
        switch ($sort) {
            case 'oldest':
                usort($studentsData, fn($a, $b) => strcmp($a['enrollment_date'], $b['enrollment_date']));
                break;
            case 'name_asc':
                usort($studentsData, fn($a, $b) => strcmp($a['name'], $b['name']));
                break;
            case 'progress_desc':
                usort($studentsData, fn($a, $b) => $b['progress'] <=> $a['progress']);
                break;
            case 'newest':
            default:
                usort($studentsData, fn($a, $b) => strcmp($b['enrollment_date'], $a['enrollment_date']));
                break;
        }

        // Pagination for API (Manual)
        $perPage = 15;
        $page = $request->query('page', 1);
        $total = count($studentsData);
        $pagedData = array_slice($studentsData, ($page - 1) * $perPage, $perPage);

        return response()->json([
            'success' => true,
            'students' => $pagedData,
            'pagination' => [
                'current_page' => (int)$page,
                'last_page' => ceil($total / $perPage),
                'total' => $total,
                'per_page' => $perPage
            ],
            'courses' => Course::where('instructor_id', $instructor->id)->select('id', 'title')->get()
        ]);
    }

    public function show($id)
    {
        $instructor = Auth::user();
        $student = User::findOrFail($id);

        // Get instructor's course IDs
        $instructorCourseIds = Course::where('instructor_id', $instructor->id)->pluck('id');

        // Get student's enrollments for these courses
        $enrollments = Enrollment::where('user_id', $id)
            ->whereIn('course_id', $instructorCourseIds)
            ->with('course')
            ->get();

        $courses = $enrollments->map(function ($enrollment) use ($id) {
            $totalLessons = Lesson::whereHas('topic', function($q) use ($enrollment) {
                $q->where('course_id', $enrollment->course_id);
            })->count();
            
            $completedLessons = LessonProgress::where('user_id', $id)
                ->where('enrollment_id', $enrollment->id)
                ->where('is_completed', true)
                ->count();
            
            $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

            return [
                'id' => $enrollment->course->id,
                'title' => $enrollment->course->title,
                'thumbnail' => $enrollment->course->thumbnail,
                'progress' => $progress,
                'enrollment_date' => $enrollment->created_at->format('M d, Y'),
                'lessons_count' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'is_live_course' => (bool)$enrollment->course->is_live_course,
            ];
        });

        $totalEnrollments = count($enrollments);
        $avgProgress = $totalEnrollments > 0 ? round($courses->sum('progress') / $totalEnrollments) : 0;

        return response()->json([
            'success' => true,
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $this->maskEmail($student->email),
                'avatar' => $student->profile_photo ? asset('storage/' . $student->profile_photo) : 'https://ui-avatars.com/api/?name=' . urlencode($student->name),
                'bio' => $student->bio,
                'phone' => $student->phone,
                'last_active' => $student->last_login_at ? Carbon::parse($student->last_login_at)->format('M d, Y') : 'Never',
            ],
            'stats' => [
                'total_courses' => $totalEnrollments,
                'avg_progress' => $avgProgress,
                'completed_courses' => $courses->where('progress', 100)->count(),
                'avg_score' => 0 // Placeholder
            ],
            'enrolled_courses' => $courses
        ]);
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
