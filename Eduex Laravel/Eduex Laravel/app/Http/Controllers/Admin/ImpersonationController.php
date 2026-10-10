<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Devrabiul\ToastMagic\Facades\ToastMagic;

/**
 * Impersonation Controller
 *
 * This controller handles the impersonation of users.
 *
 * @package App\Http\Controllers\Admin
 */
class ImpersonationController extends Controller
{
    /**
     * Impersonate an instructor
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function impersonate($id)
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            ToastMagic::error('Only admins can impersonate users.');
            return back();
        }

        $instructor = User::where('role', 'instructor')->findOrFail($id);

        if (!$instructor->is_approved) {
            ToastMagic::error('You can only impersonate approved instructors.');
            return back();
        }

        session(['impersonating_admin_id' => Auth::id()]);
        session(['impersonating_instructor_id' => $instructor->id]);

        Auth::login($instructor);

        ToastMagic::success("You are now viewing as {$instructor->name}.");

        return redirect()->route('instructor.dashboard');
    }

    /**
     * Impersonate a student
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function impersonateStudent($id)
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            ToastMagic::error('Only admins can impersonate users.');
            return back();
        }

        $student = User::where('role', 'student')->findOrFail($id);

        session(['impersonating_admin_id' => Auth::id()]);
        session(['impersonating_student_id' => $student->id]);

        Auth::login($student);

        ToastMagic::success("You are now viewing as {$student->name}.");

        return redirect()->route('student.dashboard');
    }

    /**
     * Stop impersonating and return to admin account
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function stopImpersonating()
    {
        $adminId = session('impersonating_admin_id');

        if (!$adminId) {
            ToastMagic::warning('No active impersonation session found.');
            return redirect()->route('admin.dashboard');
        }

        $admin = User::findOrFail($adminId);

        session()->forget('impersonating_admin_id');
        session()->forget('impersonating_instructor_id');
        session()->forget('impersonating_student_id');

        Auth::login($admin);

        ToastMagic::info('You have returned to your admin account.');

        return redirect()->route('admin.dashboard');
    }
}
