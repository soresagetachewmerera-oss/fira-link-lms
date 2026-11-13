<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Pending Controller
 *
 * This controller handles the pending functionality.
 *
 * @package App\Http\Controllers\Instructor
 */
class PendingController extends Controller
{
    /**
     * Display pending approval message
     */
    public function index()
    {
        $user = $this->currentUser();
        $approval_reason = $user->approval_reason;
        $rejection_reason = $user->rejection_reason;
        $is_approved = $user->is_approved;

        if($is_approved) {
            return redirect()->route('instructor.dashboard')->with('success', 'Your instructor application has been approved.');
        } else {
            return view('instructor.pending-approval', compact('user', 'approval_reason', 'rejection_reason', 'is_approved'));
        }

    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}

