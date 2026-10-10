<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
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
        $conversations = $this->chatService->getConversations($user->id, 'student', true);

        // Attach unread counts
        $conversations->getCollection()->transform(function ($conv) use ($user) {
            $conv->unread_count = $conv->unreadCountFor($user->id);
            return $conv;
        });

        return view('student.messages.index', [
            'conversations' => $conversations,
            'tab'           => 'inbox',
            'user'          => $user,
        ]);
    }

    /**
     * Sent requests — pending acceptance by instructor.
     */
    public function requests()
    {
        $user = Auth::user();
        $conversations = $this->chatService->getConversations($user->id, 'student', false);

        return view('student.messages.index', [
            'conversations' => $conversations,
            'tab'           => 'requests',
            'user'          => $user,
        ]);
    }

    /**
     * Show a conversation / chat window.
     */
    public function show(Conversation $conversation)
    {
        $user = Auth::user();

        // Authorization: student must own this conversation
        if ($conversation->student_id !== $user->id) {
            abort(403);
        }

        $messages = $this->chatService->getMessages($conversation->id, $user->id);
        $conversation->load(['instructor:id,name,profile_photo']);

        return view('student.messages.show', [
            'conversation' => $conversation,
            'messages'     => $messages,
            'user'         => $user,
        ]);
    }

    /**
     * Start a new conversation with an instructor.
     */
    public function startConversation(Request $request)
    {
        $request->validate([
            'instructor_id' => 'required|exists:users,id',
            'message'       => 'required_without:files|nullable|string|max:2000',
            'files.*'       => 'nullable|file|max:10240',
        ]);

        $user = Auth::user();
        $instructorId = $request->input('instructor_id');

        // Verify the target is actually an instructor
        $instructor = User::where('id', $instructorId)->where('role', 'instructor')->firstOrFail();

        $conversation = $this->chatService->getOrCreateConversation($user->id, $instructor->id);

        // Send the first message if provided
        if ($request->filled('message') || $request->hasFile('files')) {
            $this->chatService->sendMessage(
                $conversation->id,
                $user->id,
                $request->input('message'),
                $request->file('files', [])
            );
        }

        return response()->json([
            'success'         => true,
            'conversation_id' => $conversation->id,
            'is_accepted'     => $conversation->is_accepted,
            'redirect'        => route('student.messages.show', $conversation->id),
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

        if ($conversation->student_id !== $user->id) {
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

        if ($conversation->student_id !== $user->id) {
            abort(403);
        }

        $lastId = (int) $request->query('last_id', 0);
        $messages = $this->chatService->pollMessages($conversation->id, $user->id, $lastId);

        return response()->json([
            'messages' => $messages->values(),
        ]);
    }

    /**
     * Get total unread count (for badge via AJAX).
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $count = $this->chatService->getUnreadCount($user->id, 'student');

        return response()->json(['count' => $count]);
    }

    /**
     * Search instructors by name (for new message modal).
     */
    public function searchInstructors(Request $request)
    {
        $q = $request->query('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        return User::where('role', 'instructor')
            ->where('name', 'like', '%' . $q . '%')
            ->select('id', 'name', 'profile_photo', 'specialization')
            ->limit(10)
            ->get();
    }
}
