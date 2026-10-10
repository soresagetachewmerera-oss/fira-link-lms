<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Blog Controller
 *
 * This controller handles the blog functionality.
 *
 * @package App\Http\Controllers\Front
 */
class BlogController extends Controller
{
    /**
     * Display the blog index
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $postQuery = BlogPost::published()->with('categoryRelation');
        $search = trim((string) $request->query('q', ''));
        $categorySlug = trim((string) $request->query('category', ''));

        if ($search !== '') {
            $postQuery->where(function ($query) use ($search) {
                $query
                    ->where('title', 'like', '%' . $search . '%')
                    ->orWhere('excerpt', 'like', '%' . $search . '%')
                    ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        $activeCategory = null;
        if ($categorySlug !== '') {
            $activeCategory = BlogCategory::query()
                ->where('slug', $categorySlug)
                ->where('is_active', true)
                ->first();

            if ($activeCategory) {
                $postQuery->where(function ($builder) use ($activeCategory) {
                    $builder->where('blog_category_id', $activeCategory->id)
                        ->orWhere(function ($legacy) use ($activeCategory) {
                            $legacy->whereNull('blog_category_id')
                                ->where('category', $activeCategory->name);
                        });
                });
            } else {
                $legacyLabel = Str::headline(str_replace('-', ' ', $categorySlug));
                $postQuery->where('category', $legacyLabel);
            }
        }

        $posts = $postQuery
            ->orderByDesc('published_at')
            ->paginate(6)
            ->withQueryString();

        $meta = frontend_meta('blog', null, []);

        return view('blog.index', [
            'pageTitle' => $meta['title'] ?? 'Insights & Stories',
            'metaDescription' => $meta['description'] ?? null,
            'posts' => $posts,
            'recentPosts' => $this->recentPosts(),
            'categories' => $this->categories(),
            'activeCategory' => $activeCategory?->slug ?? ($categorySlug !== '' ? $categorySlug : null),
            'activeCategoryLabel' => $activeCategory?->name ?? ($categorySlug !== '' ? Str::headline(str_replace('-', ' ', $categorySlug)) : null),
            'searchTerm' => $search !== '' ? $search : null,
        ]);
    }

    /**
     * Display a blog post
     *
     * @param string $slug
     * @return \Illuminate\Contracts\View\View
     */
    public function show(string $slug): View
    {
        $post = BlogPost::published()
            ->with(['categoryRelation'])
            ->withCount('approvedComments')
            ->where('slug', $slug)
            ->firstOrFail();

        return view('blog.show', [
            'pageTitle' => $post->title,
            'metaDescription' => $post->meta_description ?? $post->excerpt,
            'post' => $post,
            'shareUrl' => urlencode(route('blog.show', $post->slug)),
            'shareText' => urlencode($post->title),
            'recentPosts' => $this->recentPosts(),
            'categories' => $this->categories(),
            'relatedPosts' => $this->relatedPosts($post),
            'comments' => $this->formattedComments($post),
            'commentsTitle' => sprintf(
                '%02d %s',
                $post->approved_comments_count,
                Str::plural('Comment', $post->approved_comments_count)
            ),
        ]);
    }

    /**
     * Get the recent posts
     *
     * @return \Illuminate\Support\Collection
     */
    private function recentPosts(): Collection
    {
        return BlogPost::published()
            ->with('categoryRelation')
            ->orderByDesc('published_at')
            ->limit(4)
            ->get();
    }

    /**
     * Get the blog categories
     *
     * @return \Illuminate\Support\Collection
     */
    private function categories(): Collection
    {
        return BlogCategory::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->withCount(['posts as published_posts_count' => fn ($query) => $query->published()])
            ->get()
            ->filter(fn ($category) => $category->published_posts_count > 0)
            ->map(fn ($category) => [
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => (int) $category->published_posts_count,
                'color' => $category->color,
            ])
            ->values();
    }

    /**
     * Get the related posts
     *
     * @param \App\Models\BlogPost $post
     * @return \Illuminate\Support\Collection
     */
    private function relatedPosts(BlogPost $post): Collection
    {
        return BlogPost::published()
            ->with('categoryRelation')
            ->where('id', '!=', $post->id)
            ->when($post->blog_category_id, fn ($query, $categoryId) => $query->where('blog_category_id', $categoryId))
            ->when(!$post->blog_category_id && $post->category, fn ($query) => $query->where('category', $post->category))
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();
    }

    /**
     * Format the comments
     *
     * @param \App\Models\BlogPost $post
     * @return \Illuminate\Support\Collection
     */
    private function formattedComments(BlogPost $post): Collection
    {
        return $post->approvedComments()
            ->whereNull('parent_id')
            ->with(['replies' => fn ($query) => $query->approved()])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'name' => $comment->author_name,
                    'email_hash' => md5(strtolower(trim($comment->author_email))),
                    'website' => $comment->author_website,
                    'body' => $comment->body,
                    'date' => $comment->created_at->format('F j, Y | g:i a'),
                    'replies' => $comment->replies->map(function ($reply) {
                        return [
                            'id' => $reply->id,
                            'name' => $reply->author_name,
                            'email_hash' => md5(strtolower(trim($reply->author_email))),
                            'website' => $reply->author_website,
                            'body' => $reply->body,
                            'date' => $reply->created_at->format('F j, Y | g:i a'),
                        ];
                    }),
                ];
            });
    }
}
