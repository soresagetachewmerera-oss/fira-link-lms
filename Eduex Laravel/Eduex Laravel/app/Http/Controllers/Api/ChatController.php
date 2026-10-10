<?php

namespace App\Http\Controllers\Api;

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

        $conversations->getCollection()->transform(function ($conv) use ($user) {
            $conv->unread_count = $conv->unreadCountFor($user->id);
            return $conv;
        });

        return response()->json([
            'data' => $conversations->items(),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page'    => $conversations->lastPage(),
                'total'        => $conversations->total(),
            ],
        ]);
    }

    /**
     * Sent requests — pending acceptance by instructor.
     */
    public function requests()
    {
        $user = Auth::user();
        $conversations = $this->chatService->getConversations($user->id, 'student', false);

        $conversations->getCollection()->transform(function ($conv) use ($user) {
            $conv->unread_count = $conv->unreadCountFor($user->id);
            return $conv;
        });

        return response()->json([
            'data' => $conversations->items(),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page'    => $conversations->lastPage(),
                'total'        => $conversations->total(),
            ],
        ]);
    }

    /**
     * Get total unread count.
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $count = $this->chatService->getUnreadCount($user->id, 'student');

        return response()->json(['count' => $count]);
    }

    /**
     * Search instructors by name.
     */
    public function searchInstructors(Request $request)
    {
        $q = $request->query('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $instructors = User::where('role', 'instructor')
            ->where('name', 'like', '%' . $q . '%')
            ->select('id', 'name', 'profile_photo', 'specialization')
            ->limit(10)
            ->get();

        return response()->json($instructors);
    }

    /**
     * Start or get a conversation with an instructor.
     */
    public function startConversation(Request $request)
    {
        $request->validate([
            'instructor_id' => 'required|exists:users,id',
            'message'       => 'nullable|string|max:2000',
        ]);

        $user = Auth::user();
        $instructorId = $request->input('instructor_id');

        User::where('id', $instructorId)->where('role', 'instructor')->firstOrFail();

        $conversation = $this->chatService->getOrCreateConversation($user->id, $instructorId);

        if ($request->filled('message') || $request->hasFile('files')) {
            $this->chatService->sendMessage(
                $conversation->id,
                $user->id,
                $request->input('message'),
                $request->file('files', [])
            );
        }

        $conversation->load(['instructor:id,name,profile_photo']);

        return response()->json([
            'success'      => true,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Get messages for a conversation.
     */
    public function show(int $id)
    {
        $user = Auth::user();
        $conversation = Conversation::where('id', $id)
            ->where('student_id', $user->id)
            ->with(['instructor:id,name,profile_photo'])
            ->firstOrFail();

        $messages = $this->chatService->getMessages($conversation->id, $user->id);

        return response()->json([
            'conversation' => $conversation,
            'messages'     => $messages,
        ]);
    }

    /**
     * Send a message.
     */
    public function sendMessage(Request $request, int $id)
    {
        $request->validate([
            'message' => 'required_without:files|nullable|string|max:2000',
            'files.*' => 'nullable|file|max:10240',
        ]);

        $user = Auth::user();
        $conversation = Conversation::where('id', $id)
            ->where('student_id', $user->id)
            ->firstOrFail();

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
     * Poll for new messages (short poll for mobile).
     */
    public function poll(Request $request, int $id)
    {
        $user = Auth::user();
        $conversation = Conversation::where('id', $id)
            ->where('student_id', $user->id)
            ->firstOrFail();

        $lastId = (int) $request->query('last_id', 0);
        $messages = $this->chatService->pollMessages($conversation->id, $user->id, $lastId);

        return response()->json([
            'messages' => $messages->values(),
        ]);
    }
}
