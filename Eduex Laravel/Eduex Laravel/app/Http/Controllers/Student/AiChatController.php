<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AiChatMessage;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiChatController extends Controller
{
    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Display the chat interface with history
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $messages = AiChatMessage::where('user_id', $user->id)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($request->wantsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data' => $messages,
            ]);
        }

        return view('student.ai-chat.index', compact('messages', 'user'));
    }

    /**
     * Handle message submission
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $userMessageText = $request->input('message');

        // Save user message
        $userMessage = AiChatMessage::create([
            'user_id' => $user->id,
            'message' => $userMessageText,
            'is_ai' => false,
        ]);

        // Get chat history for context (limit to last 10 messages to save tokens)
        $history = AiChatMessage::where('user_id', $user->id)
            ->where('id', '<', $userMessage->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->reverse()
            ->values()
            ->all();

        // Get AI response
        $aiResponseText = $this->geminiService->generateContent($userMessageText, $history);

        // Save AI response
        $aiMessage = AiChatMessage::create([
            'user_id' => $user->id,
            'message' => $aiResponseText,
            'is_ai' => true,
        ]);

        return response()->json([
            'user_message' => $userMessage,
            'ai_message' => $aiMessage,
        ]);
    }
}
