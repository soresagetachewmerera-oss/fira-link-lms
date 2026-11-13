<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\LiveClass;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Student Live Class Controller
 *
 * Lists live classes from courses the student is enrolled in.
 *
 * @package App\Http\Controllers\Student
 */
class LiveClassController extends Controller
{
    /**
     * Display all live classes for the student's enrolled courses.
     */
    public function index()
    {
        $user = $this->currentUser();

        $enrolledCourseIds = Enrollment::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'completed'])
            ->pluck('course_id');

        $liveClasses = LiveClass::whereIn('course_id', $enrolledCourseIds)
            ->with(['course', 'instructor', 'lesson'])
            ->orderByRaw("FIELD(status, 'live', 'scheduled', 'ended', 'cancelled')")
            ->orderBy('scheduled_at', 'desc')
            ->paginate(15);

        return view('student.live-classes.index', compact('user', 'liveClasses'));
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
