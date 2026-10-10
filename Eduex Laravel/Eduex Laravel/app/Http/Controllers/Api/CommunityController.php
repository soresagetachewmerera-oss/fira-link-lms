<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommunityAnswer;
use App\Models\CommunityQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\CommunityVote;

class CommunityController extends Controller
{
    /**
     * Display a listing of community questions.
     */
    public function index(Request $request)
    {
        $user = $request->user('sanctum');

        $query = CommunityQuestion::with(['user:id,name,profile_photo'])
            ->withCount('answers')
            ->withSum('votes', 'vote');

        if ($user) {
            $query->with([
                'votes' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }
            ]);
        }

        // Filter
        $filter = $request->get('filter', 'all');
        if ($filter === 'unanswered') {
            $query->has('answers', '=', 0);
        } elseif ($filter === 'my_questions') {
            $query->where('user_id', $user ? $user->id : null);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $questions = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $questions
        ]);
    }

    /**
     * Store a newly created question in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'tags' => 'nullable|array',
        ]);

        $question = CommunityQuestion::create([
            'user_id' => Auth::id(),
            'title' => strip_tags($request->title),
            'description' => strip_tags($request->description),
            'tags' => $request->tags,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Question posted successfully',
            'data' => $question->load('user:id,name,profile_photo')
        ], 201);
    }

    /**
     * Display the specified question.
     */
    public function show($id)
    {
        $user = request()->user('sanctum');

        $query = CommunityQuestion::with(['user:id,name,profile_photo', 'answers.user:id,name,profile_photo'])
            ->withCount('answers')
            ->withSum('votes', 'vote');

        if ($user) {
            $query->with([
                'votes' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }
            ]);

            $query->with([
                'answers' => function ($q) use ($user) {
                    $q->withSum('votes', 'vote')
                        ->with([
                            'votes' => function ($v) use ($user) {
                                $v->where('user_id', $user->id);
                            }
                        ]);
                }
            ]);
        } else {
            $query->with([
                'answers' => function ($q) {
                    $q->withSum('votes', 'vote');
                }
            ]);
        }

        $question = $query->findOrFail($id);
        $question->increment('views');

        return response()->json([
            'success' => true,
            'data' => $question
        ]);
    }

    /**
     * Store a newly created answer in storage.
     */
    public function storeAnswer(Request $request, $questionId)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $question = CommunityQuestion::findOrFail($questionId);

        $answer = CommunityAnswer::create([
            'user_id' => Auth::id(),
            'question_id' => $question->id,
            'content' => strip_tags($request->input('content')),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Answer posted successfully',
            'data' => $answer->load('user:id,name,profile_photo')
        ], 201);
    }

    /**
     * Mark an answer as accepted.
     */
    public function markAnswerAccepted($id)
    {
        $answer = CommunityAnswer::with('question')->findOrFail($id);

        // Ensure current user is the author of the question
        if ($answer->question->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Reset other accepted answers for this question
        CommunityAnswer::where('question_id', $answer->question_id)
            ->update(['is_accepted' => false]);

        $answer->update(['is_accepted' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Answer marked as accepted'
        ]);
    }

    /**
     * Vote on a question or answer.
     */
    public function vote(Request $request, $type, $id)
    {
        $request->validate([
            'vote' => 'required|in:1,-1,0',
        ]);

        $userId = Auth::id();
        $votableType = $type === 'question' ? CommunityQuestion::class : CommunityAnswer::class;

        $votable = $votableType::findOrFail($id);

        if ($request->vote == 0) {
            CommunityVote::where([
                'user_id' => $userId,
                'votable_id' => $id,
                'votable_type' => $votableType,
            ])->delete();

            return response()->json([
                'success' => true,
                'message' => 'Vote removed',
            ]);
        }

        CommunityVote::updateOrCreate(
            [
                'user_id' => $userId,
                'votable_id' => $id,
                'votable_type' => $votableType,
            ],
            ['vote' => $request->vote]
        );

        return response()->json([
            'success' => true,
            'message' => 'Vote recorded',
        ]);
    }
}
