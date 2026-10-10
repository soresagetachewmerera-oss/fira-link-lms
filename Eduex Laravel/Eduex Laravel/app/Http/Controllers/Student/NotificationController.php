<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $notifications = $user->notifications()->paginate(15);
        // Mark all as read when viewing the list? Or strictly one by one.
// Usually viewing the list doesn't mark them as read, clicking does.

        return view('student.notifications.index', compact('notifications', 'user'));
    }

    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $data = $notification->data;
        $url = $data['url'] ?? null;

        if ($url) {
            return redirect($url);
        }

        return redirect()->back();
    }

    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return redirect()->back();
    }
}