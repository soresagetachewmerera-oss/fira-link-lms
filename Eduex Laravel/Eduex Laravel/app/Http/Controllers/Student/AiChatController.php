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
