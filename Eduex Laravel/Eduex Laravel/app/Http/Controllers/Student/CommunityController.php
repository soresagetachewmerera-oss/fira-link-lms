<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CommunityAnswer;
use App\Models\CommunityQuestion;
use App\Models\CommunityVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

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

        return view('student.community.index', [
            'questions' => $questions,
            'filter' => $filter,
            'user' => $user
        ]);
    }

    public function show($id)
    {
        $user = Auth::user();

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

        return view('student.community.show', [
            'question' => $question,
            'user' => $user
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'tags' => 'nullable|string', // Web usually sends comma-separated tags
        ]);

        $tags = $request->tags ? array_map('trim', explode(',', $request->tags)) : [];

        $question = CommunityQuestion::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'description' => $request->description,
            'tags' => $tags,
        ]);

        return redirect()->route('student.community.show', $question->id)
            ->with('success', 'Question posted successfully');
    }

    public function storeAnswer(Request $request, $questionId)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $question = CommunityQuestion::findOrFail($questionId);

        CommunityAnswer::create([
            'user_id' => Auth::id(),
            'question_id' => $question->id,
            'content' => $request->input('content'),
        ]);

        return redirect()->back()->with('success', 'Answer posted successfully');
    }

    public function markAnswerAccepted($id)
    {
        $answer = CommunityAnswer::with('question')->findOrFail($id);

        if ($answer->question->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        CommunityAnswer::where('question_id', $answer->question_id)
            ->update(['is_accepted' => false]);

        $answer->update(['is_accepted' => true]);

        return redirect()->back()->with('success', 'Answer marked as accepted');
    }

    public function vote(Request $request, $type, $id)
    {
        $request->validate([
            'vote' => 'required|in:1,-1,0',
        ]);

        $userId = Auth::id();
        $votableType = $type === 'question' ? CommunityQuestion::class : CommunityAnswer::class;

        if ($request->vote == 0) {
            CommunityVote::where([
                'user_id' => $userId,
                'votable_id' => $id,
                'votable_type' => $votableType,
            ])->delete();

            return response()->json(['success' => true, 'message' => 'Vote removed']);
        }

        CommunityVote::updateOrCreate(
            [
                'user_id' => $userId,
                'votable_id' => $id,
                'votable_type' => $votableType,
            ],
            ['vote' => $request->vote]
        );

        return response()->json(['success' => true, 'message' => 'Vote recorded']);
    }
}
