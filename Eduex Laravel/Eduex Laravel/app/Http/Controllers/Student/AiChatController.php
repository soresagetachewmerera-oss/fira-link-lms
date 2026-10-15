<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AiChatMessage;
use App\Models\User;
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
     * Check Fira AI status
     */
    public function status()
    {
        return response()->json([
            'success' => true,
            'status' => $this->geminiService->getStatus(),
        ]);
    }

    /**
     * Test connection with Google Gemini AI
     */
    public function testConnection()
    {
        $result = $this->geminiService->testConnection();
        return response()->json($result);
    }

    /**
     * Display the chat interface with history
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user() ?: auth('sanctum')->user();
        $userId = $user ? $user->id : 1;
        $messages = AiChatMessage::where('user_id', $userId)
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
     * Display public Fira AI interface accessible by any user or guest
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function publicIndex(Request $request)
    {
        $user = Auth::user();
        $userId = $user ? $user->id : null;
        $messages = $userId
            ? AiChatMessage::where('user_id', $userId)->orderBy('created_at', 'asc')->take(50)->get()
            : collect([]);

        $initialPrompt = $request->query('prompt', '');

        return view('pages.ai-chat', compact('messages', 'user', 'initialPrompt'));
    }

    /**
     * Handle message submission
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        $userMessageText = $request->input('message') ?? $request->input('prompt') ?? $request->input('text') ?? $request->input('query');

        if (empty($userMessageText) || !is_string($userMessageText) || trim($userMessageText) === '') {
            return response()->json([
                'success' => false,
                'message' => 'The message field is required.',
                'errors' => ['message' => ['The message field is required.']]
            ], 422);
        }

        $userMessageText = trim($userMessageText);

        $user = Auth::user() ?: auth('sanctum')->user();
        $userId = $user ? $user->id : (User::first()?->id ?? 1);

        // Save user message
        $userMessage = null;
        try {
            $userMessage = AiChatMessage::create([
                'user_id' => $userId,
                'message' => $userMessageText,
                'is_ai' => false,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Could not save user AI message: ' . $e->getMessage());
        }

        // Get chat history for context (limit to last 4 messages to save tokens and avoid quota limits)
        $history = [];
        try {
            $history = AiChatMessage::where('user_id', $userId)
                ->when($userMessage, fn($q) => $q->where('id', '<', $userMessage->id))
                ->orderBy('created_at', 'desc')
                ->take(4)
                ->get()
                ->reverse()
                ->values()
                ->map(function ($msg) {
                    if (is_string($msg->message) && strlen($msg->message) > 350) {
                        $msg->message = mb_substr($msg->message, 0, 350) . '...';
                    }
                    return $msg;
                })
                ->all();
        } catch (\Throwable $e) {
            \Log::warning('Could not fetch AI chat history: ' . $e->getMessage());
        }

        // Get AI response
        $aiResponseText = $this->geminiService->generateContent($userMessageText, $history);

        // Save AI response
        $aiMessage = null;
        try {
            $aiMessage = AiChatMessage::create([
                'user_id' => $userId,
                'message' => $aiResponseText,
                'is_ai' => true,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Could not save AI reply message: ' . $e->getMessage());
            $aiMessage = (object)[
                'id' => rand(1000, 9999),
                'user_id' => $userId,
                'message' => $aiResponseText,
                'is_ai' => true,
                'created_at' => now(),
            ];
        }

        if (!$userMessage) {
            $userMessage = (object)[
                'id' => rand(100, 999),
                'user_id' => $userId,
                'message' => $userMessageText,
                'is_ai' => false,
                'created_at' => now(),
            ];
        }

        // Contextual action card detection
        $actionCard = null;
        $combinedText = strtolower($userMessageText . ' ' . $aiResponseText);

        if (str_contains($combinedText, 'calculat') || str_contains($combinedText, 'profit') || str_contains($combinedText, 'margin') || str_contains($combinedText, 'break-even') || str_contains($combinedText, 'fob price')) {
            $actionCard = [
                'type' => 'calculator',
                'title' => 'Coffee Export Profit Calculator',
                'description' => 'Calculate deterministic export costs, net margins, and break-even FOB prices.',
                'action_label' => 'Open Calculator',
                'url' => route('student.coffee-toolkit.calculator'),
                'route' => '/coffee-toolkit/calculator',
            ];
        } elseif (str_contains($combinedText, 'offer') || str_contains($combinedText, 'quotation') || str_contains($combinedText, 'spec sheet') || str_contains($combinedText, 'proforma')) {
            $actionCard = [
                'type' => 'offer',
                'title' => 'Coffee Export Offer Generator',
                'description' => 'Generate a formal Ethiopian export offer sheet with PDF download.',
                'action_label' => 'Create Offer Sheet',
                'url' => route('student.coffee-toolkit.offer-generator'),
                'route' => '/coffee-toolkit/offer-generator',
            ];
        } elseif (str_contains($combinedText, 'readiness') || str_contains($combinedText, 'ready to export') || str_contains($combinedText, 'ecta license') || str_contains($combinedText, 'compliance')) {
            $actionCard = [
                'type' => 'readiness',
                'title' => 'Export Readiness Assessment',
                'description' => 'Evaluate your licensing, sourcing, quality, and logistics score.',
                'action_label' => 'Take Assessment',
                'url' => route('student.coffee-toolkit.readiness'),
                'route' => '/coffee-toolkit/readiness',
            ];
        } elseif (str_contains($combinedText, 'differential') || str_contains($combinedText, 'arabica c') || str_contains($combinedText, 'market benchmark') || str_contains($combinedText, 'ecx price')) {
            $actionCard = [
                'type' => 'market',
                'title' => 'Coffee Market Intelligence',
                'description' => 'View ICE Arabica C benchmarks, Ethiopian differentials, and ECX price trends.',
                'action_label' => 'View Market Intelligence',
                'url' => route('student.coffee-toolkit.market-intelligence'),
                'route' => '/coffee-toolkit/market-intelligence',
            ];
        }

        return response()->json([
            'user_message' => $userMessage,
            'ai_message' => $aiMessage,
            'action_card' => $actionCard,
        ]);
    }
}
