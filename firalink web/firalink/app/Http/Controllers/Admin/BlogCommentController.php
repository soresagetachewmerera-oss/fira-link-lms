<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogComment;
use App\Models\BlogPost;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Blog Comment Controller
 *
 * This controller handles the management of blog comments.
 *
 * @package App\Http\Controllers\Admin
 */
class BlogCommentController extends Controller
{
    /**
     * Display all blog comments
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $query = BlogComment::query()
            ->with('post')
            ->orderByDesc('created_at');

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $postId = $request->query('post_id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('author_name', 'like', '%' . $search . '%')
                    ->orWhere('author_email', 'like', '%' . $search . '%')
                    ->orWhere('body', 'like', '%' . $search . '%');
            });
        }

        if ($status === 'approved') {
            $query->where('is_approved', true);
        } elseif ($status === 'pending') {
            $query->where('is_approved', false);
        }

        if (!empty($postId)) {
            $query->where('blog_post_id', $postId);
        }

        $comments = $query->paginate(20)->withQueryString();

        $statistics = [
            'total' => BlogComment::count(),
            'approved' => BlogComment::where('is_approved', true)->count(),
            'pending' => BlogComment::where('is_approved', false)->count(),
        ];

        $posts = BlogPost::orderBy('title')->pluck('title', 'id');

        return view('admin.blog-comments.index', [
            'comments' => $comments,
            'statistics' => $statistics,
            'posts' => $posts,
            'search' => $search,
            'status' => $status,
            'postId' => $postId,
        ]);
    }

    /**
     * Toggle the approval status of a blog comment
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\BlogComment $comment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggle(Request $request, BlogComment $comment): RedirectResponse
    {
        $approve = $request->boolean('approve', ! $comment->is_approved);

        $comment->update([
            'is_approved' => $approve,
        ]);

        ToastMagic::success($approve ? 'Comment approved.' : 'Comment marked as pending.');

        return back();
    }

    /**
     * Delete a blog comment
     *
     * @param \App\Models\BlogComment $comment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(BlogComment $comment): RedirectResponse
    {
        $comment->delete();

        ToastMagic::success('Comment deleted.');

        return back();
    }
}
