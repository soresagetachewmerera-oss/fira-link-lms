<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $notifications = $user->notifications()->paginate(15);

        return view('instructor.notifications.index', compact('notifications', 'user'));
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
