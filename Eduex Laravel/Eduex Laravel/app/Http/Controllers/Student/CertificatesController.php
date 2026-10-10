<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Certificates Controller
 *
 * This controller handles the certificates functionality.
 *
 * @package App\Http\Controllers\Student
 */
class CertificatesController extends Controller
{
    public function index(): View
    {
        $user = $this->currentUser();

        $completedEnrollments = Enrollment::with('course.instructor')
            ->where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_COMPLETED)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Enrollment $enrollment) use ($user) {
                $course = $enrollment->course;

                return [
                    'id' => $enrollment->id,
                    'course_id' => $course?->id,
                    'course_title' => $course?->title,
                    'instructor_name' => $course?->instructor->name ?? 'Instructor',
                    'completed_at' => $enrollment->updated_at,
                    'certificate_number' => sprintf(
                        'CERT-%06d-%s',
                        $enrollment->id,
                        strtoupper(substr(md5($user->id . ($course?->id ?? '')), 0, 6))
                    ),
                ];
            });

        return view('student.certificates', [
            'completedEnrollments' => $completedEnrollments,
            'user' => $user,
        ]);
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

