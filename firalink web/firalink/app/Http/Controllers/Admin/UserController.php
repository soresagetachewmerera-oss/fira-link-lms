<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Support\Facades\Auth;

/**
 * User Controller
 *
 * This controller handles the management of users.
 *
 * @package App\Http\Controllers\Admin
 */
class UserController extends Controller
{
    /**
     * Display all users
     */
    public function index()
    {
        $users = User::with('roles')
            ->latest()
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Display user details
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);
        
        return view('admin.users.show', compact('user'));
    }

    /**
     * Delete a user
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->id === (Auth::user()->id ?? null)) {
            ToastMagic::error('You cannot delete your own account.');
            return back();
        }

        $userName = $user->name;
        $user->delete();

        ToastMagic::success("User '{$userName}' has been deleted successfully.");
        return back();
    }
}

