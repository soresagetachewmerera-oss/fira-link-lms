<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\User;
use App\Models\AdminNotificationHistory;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class NotificationController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.notifications.create');
    }

    public function create()
    {
        $courses = Course::select('id', 'title')->get();
        $instructors = User::whereHas('roles', function ($q) {
            $q->where('name', 'instructor');
        })->orWhere('role', 'instructor')->select('id', 'name')->get();

        $sentNotifications = AdminNotificationHistory::with('sender')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.notifications.create', compact('courses', 'instructors', 'sentNotifications'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'type' => 'required|in:text,course,image,url,author',
            'course_id' => 'required_if:type,course',
            'url' => 'required_if:type,url',
            'author_id' => 'required_if:type,author',
            'image' => 'nullable|image|max:2048',
            'target_audience' => 'required|in:all,student,instructor',
        ]);

        $title = $request->input('title', 'New Notification');
        $message = $request->input('message');
        $type = $request->input('type');
        $targetAudience = $request->input('target_audience', 'all');
        $targetUrl = null;
        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('notifications', 'public');
        }

        switch ($type) {
            case 'course':
                $targetUrl = route('courses.show', $request->course_id);
                break;
            case 'author':
                $targetUrl = route('instructors.show', $request->author_id);
                break;
            case 'url':
                $targetUrl = $request->url;
                break;
            case 'image':
                if ($imagePath) {
                    $targetUrl = asset('storage/' . $imagePath);
                }
                break;
        }

        $query = User::where('role', '!=', 'admin');

        if ($targetAudience === 'student') {
            $query->where('role', 'student');
        } elseif ($targetAudience === 'instructor') {
            $query->where('role', 'instructor');
        }

        $users = $query->get();

        if ($users->count() > 0) {
            Notification::send($users, new GeneralNotification($title, $message, $type, $imagePath, $targetUrl));
        }

        // Save to history
        AdminNotificationHistory::create([
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'target_audience' => $targetAudience,
            'image' => $imagePath,
            'url' => $targetUrl,
            'recipient_count' => $users->count(),
            'sent_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Notification sent successfully to ' . $users->count() . ' users.');
    }

    public function resend($id)
    {
        $history = AdminNotificationHistory::findOrFail($id);

        $query = User::where('role', '!=', 'admin');

        if ($history->target_audience === 'student') {
            $query->where('role', 'student');
        } elseif ($history->target_audience === 'instructor') {
            $query->where('role', 'instructor');
        }

        $users = $query->get();

        if ($users->count() > 0) {
            Notification::send($users, new GeneralNotification(
                $history->title,
                $history->message,
                $history->type,
                $history->image,
                $history->url
            ));
        }

        // Save new history entry for resend
        AdminNotificationHistory::create([
            'title' => $history->title,
            'message' => $history->message,
            'type' => $history->type,
            'target_audience' => $history->target_audience,
            'image' => $history->image,
            'url' => $history->url,
            'recipient_count' => $users->count(),
            'sent_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Notification resent successfully to ' . $users->count() . ' users.');
    }
}