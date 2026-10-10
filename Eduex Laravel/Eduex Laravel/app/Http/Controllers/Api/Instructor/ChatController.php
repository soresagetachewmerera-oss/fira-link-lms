<?php

namespace App\Http\Controllers\Api\Instructor;

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
            // Load the student as the other participant for the list
            $conv->student_info = $conv->student; 
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
     * Pending requests.
     */
    public function requests()
    {
        $user = Auth::user();
        $conversations = $this->chatService->getConversations($user->id, 'instructor', false);

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
     * Accept a request.
     */
    public function accept(int $id)
    {
        $user = Auth::user();
        $success = $this->chatService->acceptRequest($id, $user->id);

        return response()->json(['success' => $success]);
    }

    /**
     * Decline/Close a request.
     */
    public function decline(int $id)
    {
        $user = Auth::user();
        $success = $this->chatService->declineRequest($id, $user->id);

        return response()->json(['success' => $success]);
    }

    /**
     * Get messages for a conversation.
     */
    public function show(int $id)
    {
        $user = Auth::user();
        $conversation = Conversation::where('id', $id)
            ->where('instructor_id', $user->id)
            ->with(['student' => function ($q) use ($user) {
                $q->select('id', 'name', 'profile_photo')
                    ->with(['enrollments' => function ($eq) use ($user) {
                        $eq->whereHas('course', function ($cq) use ($user) {
                            $cq->where('instructor_id', $user->id);
                        })->with('course:id,title,featured_image');
                    }]);
            }])
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
            ->where('instructor_id', $user->id)
            ->where('is_accepted', true)
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
     * Poll for new messages.
     */
    public function poll(Request $request, int $id)
    {
        $user = Auth::user();
        $conversation = Conversation::where('id', $id)
            ->where('instructor_id', $user->id)
            ->firstOrFail();

        $lastId = (int) $request->query('last_id', 0);
        $messages = $this->chatService->pollMessages($conversation->id, $user->id, $lastId);

        return response()->json([
            'messages' => $messages->values(),
        ]);
    }

    /**
     * Start or get conversation with a student.
     */
    public function startConversation(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        $instructor = Auth::user();
        $studentId = $request->student_id;

        $conversation = $this->chatService->getOrCreateConversation($studentId, $instructor->id);
        $conversation->load('student');

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Get total unread count.
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $count = $this->chatService->getUnreadCount($user->id, 'instructor');
        $requestCount = $this->chatService->getRequestCount($user->id);

        return response()->json([
            'unread_count' => $count,
            'request_count' => $requestCount
        ]);
    }
}
