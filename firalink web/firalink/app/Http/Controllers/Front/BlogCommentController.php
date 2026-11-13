<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BlogComment;
use App\Models\BlogPost;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Blog Comment Controller
 *
 * This controller handles the blog comment functionality.
 *
 * @package App\Http\Controllers\Front
 */
class BlogCommentController extends Controller
{
    /**
     * Store a new blog comment
     *
     * @param \Illuminate\Http\Request $request
     * @param string $slug
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, string $slug): RedirectResponse
    {
        $post = BlogPost::published()->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'url', 'max:255'],
            'comment' => ['required', 'string', 'min:5'],
            'parent_id' => [
                'nullable',
                Rule::exists(BlogComment::class, 'id')->where(fn ($query) => $query->where('blog_post_id', $post->id)),
            ],
        ]);

        $post->comments()->create([
            'parent_id' => $validated['parent_id'] ?? null,
            'author_name' => $validated['name'],
            'author_email' => $validated['email'],
            'author_website' => $validated['website'] ?? null,
            'body' => $validated['comment'],
            'is_approved' => true,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500),
        ]);

        ToastMagic::success('Thanks for your comment! It is now visible on the page.');

        return redirect()->route('blog.show', $post->slug);
    }
}
