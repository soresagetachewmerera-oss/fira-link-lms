<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function __construct(private ChatService $chatService)
    {
    }

    /**
     * Inbox — accepted conversations.
     */
    public function index()
    {
        $user = Auth::user();
        $conversations = $this->chatService->getConversations($user->id, 'instructor', true);

        $conversations->getCollection()->transform(function ($conv) use ($user) {
            $conv->unread_count = $conv->unreadCountFor($user->id);
            return $conv;
        });

        $requestCount = $this->chatService->getRequestCount($user->id);

        return view('instructor.messages.index', [
            'conversations' => $conversations,
            'tab'           => 'inbox',
            'user'          => $user,
            'requestCount'  => $requestCount,
        ]);
    }

    /**
     * Start or jump to a conversation with a specific student.
     */
    public function startConversation(Request $request)
    {
        $user = Auth::user();
        $studentId = $request->query('student_id');

        if (!$studentId) {
            return redirect()->route('instructor.messages.index');
        }

        $conversation = $this->chatService->getOrCreateConversation((int)$studentId, $user->id);

        return redirect()->route('instructor.messages.show', $conversation->id);
    }

    /**
     * Message requests — from non-enrolled students.
     */
    public function requests()
    {
        $user = Auth::user();
        $conversations = $this->chatService->getConversations($user->id, 'instructor', false);

        $conversations->getCollection()->transform(function ($conv) use ($user) {
            $conv->unread_count = $conv->unreadCountFor($user->id);
            return $conv;
        });

        $requestCount = $this->chatService->getRequestCount($user->id);

        return view('instructor.messages.index', [
            'conversations' => $conversations,
            'tab'           => 'requests',
            'user'          => $user,
            'requestCount'  => $requestCount,
        ]);
    }

    /**
     * Show a conversation / chat window.
     */
    public function show(Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->instructor_id !== $user->id) {
            abort(403);
        }

        $messages = $this->chatService->getMessages($conversation->id, $user->id);
        $conversation->load(['student:id,name,profile_photo']);

        // Get courses this student is enrolled in from this instructor
        $enrolledCourses = \App\Models\Enrollment::where('user_id', $conversation->student_id)
            ->whereHas('course', function ($q) use ($user) {
                $q->where('instructor_id', $user->id);
            })
            ->with('course:id,title,featured_image')
            ->get()
            ->pluck('course')
            ->filter();

        return view('instructor.messages.show', [
            'conversation'    => $conversation,
            'messages'        => $messages,
            'user'            => $user,
            'enrolledCourses' => $enrolledCourses,
        ]);
    }

    /**
     * Send a message in an existing conversation.
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        $request->validate([
            'message' => 'required_without:files|nullable|string|max:2000',
            'files.*' => 'nullable|file|max:10240',
        ]);

        $user = Auth::user();

        if ($conversation->instructor_id !== $user->id) {
            abort(403);
        }

        $message = $this->chatService->sendMessage(
            $conversation->id,
            $user->id,
            $request->input('message'),
            $request->file('files', [])
        );

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Long/short poll for new messages.
     */
    public function poll(Request $request, Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->instructor_id !== $user->id) {
            abort(403);
        }

        $lastId = (int) $request->query('last_id', 0);
        $messages = $this->chatService->pollMessages($conversation->id, $user->id, $lastId);

        return response()->json([
            'messages' => $messages->values(),
        ]);
    }

    /**
     * Accept a message request.
     */
    public function accept(Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->instructor_id !== $user->id) {
            abort(403);
        }

        $this->chatService->acceptRequest($conversation->id, $user->id);

        return response()->json(['success' => true]);
    }

    /**
     * Decline a message request.
     */
    public function decline(Conversation $conversation)
    {
        $user = Auth::user();

        if ($conversation->instructor_id !== $user->id) {
            abort(403);
        }

        $this->chatService->declineRequest($conversation->id, $user->id);

        return response()->json(['success' => true]);
    }

    /**
     * Get total unread count (for badge via AJAX).
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $count = $this->chatService->getUnreadCount($user->id, 'instructor');
        $requestCount = $this->chatService->getRequestCount($user->id);

        return response()->json([
            'count'         => $count,
            'request_count' => $requestCount,
        ]);
    }
}
