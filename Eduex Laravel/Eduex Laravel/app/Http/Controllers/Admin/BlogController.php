<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Blog Controller
 *
 * This controller handles the management of blog posts.
 *
 * @package App\Http\Controllers\Admin
 */
class BlogController extends Controller
{
    /**
     * Display all blog posts
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $query = BlogPost::with('categoryRelation')->orderByDesc('published_at')->orderByDesc('created_at');

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');
        $categoryId = $request->query('category_id');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('title', 'like', '%' . $search . '%')
                    ->orWhere('excerpt', 'like', '%' . $search . '%')
                    ->orWhere('author_name', 'like', '%' . $search . '%');
            });
        }

        if ($status === 'published') {
            $query->where('is_published', true)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now());
        } elseif ($status === 'scheduled') {
            $query->where('is_published', true)
                ->whereNotNull('published_at')
                ->where('published_at', '>', now());
        } elseif ($status === 'draft') {
            $query->where(function ($builder) {
                $builder->where('is_published', false)
                    ->orWhereNull('published_at');
            });
        }

        if (!empty($categoryId)) {
            $query->where('blog_category_id', $categoryId);
        }

        $posts = $query->paginate(15)->withQueryString();

        $statistics = [
            'total' => BlogPost::count(),
            'published' => BlogPost::published()->count(),
            'scheduled' => BlogPost::where('is_published', true)->where('published_at', '>', now())->count(),
            'drafts' => BlogPost::where(function ($query) {
                $query->where('is_published', false)
                    ->orWhereNull('published_at');
            })->count(),
        ];

        return view('admin.blogs.index', [
            'posts' => $posts,
            'statistics' => $statistics,
            'search' => $search,
            'status' => $status,
            'categoryId' => $categoryId,
            'categoryOptions' => $this->blogCategoryOptions(),
        ]);
    }

    /**
     * Display the create blog post form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        $blog = new BlogPost([
            'is_published' => false,
            'published_at' => now(),
        ]);

        return view('admin.blogs.create', [
            'blog' => $blog,
            'categoryOptions' => $this->blogCategoryOptions(),
            'formDefaults' => $this->formDefaults($blog),
        ]);
    }

    /**
     * Store a new blog post
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $taxonomy = $this->resolveCategorySelection($request);
        $data['blog_category_id'] = $taxonomy['category']?->id;
        $data['category'] = $taxonomy['categoryLabel'];

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')->store('blog/images', 'public');
        }

        $data['tags'] = $this->formatTags($request->input('tags'));
        $data['is_published'] = $request->boolean('is_published');

        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $data['slug'] = $this->resolveSlug($data['title'], $data['slug'] ?? null);

        $blog = BlogPost::create($data);

        ToastMagic::success('Blog post created successfully.');

        return redirect()->route('admin.blog-posts.edit', $blog);
    }

    /**
     * Display a blog post details
     *
     * @param \App\Models\BlogPost $blogPost
     * @return \Illuminate\Contracts\View\View
     */
    public function show(BlogPost $blogPost): View
    {
        $blogPost->loadCount(['approvedComments', 'comments']);

        return view('admin.blogs.show', [
            'blog' => $blogPost,
            'recentComments' => $blogPost->comments()->latest()->limit(5)->get(),
        ]);
    }

    /**
     * Display the edit blog post form
     *
     * @param \App\Models\BlogPost $blogPost
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(BlogPost $blogPost): View
    {
        return view('admin.blogs.edit', [
            'blog' => $blogPost,
            'categoryOptions' => $this->blogCategoryOptions(),
            'formDefaults' => $this->formDefaults($blogPost),
        ]);
    }

    /**
     * Update a blog post
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\BlogPost $blogPost
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, BlogPost $blogPost): RedirectResponse
    {
        $data = $this->validatedData($request, $blogPost->id);

        $taxonomy = $this->resolveCategorySelection($request);
        $data['blog_category_id'] = $taxonomy['category']?->id;
        $data['category'] = $taxonomy['categoryLabel'];

        if ($request->hasFile('featured_image')) {
            $newPath = $request->file('featured_image')->store('blog/images', 'public');

            if ($blogPost->featured_image && Str::startsWith($blogPost->featured_image, 'blog/') && Storage::disk('public')->exists($blogPost->featured_image)) {
                Storage::disk('public')->delete($blogPost->featured_image);
            }

            $data['featured_image'] = $newPath;
        }

        $data['tags'] = $this->formatTags($request->input('tags'));
        $data['is_published'] = $request->boolean('is_published');

        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        $data['slug'] = $this->resolveSlug($data['title'], $request->input('slug'), $blogPost->id);

        $blogPost->update($data);

        ToastMagic::success('Blog post updated successfully.');

        return redirect()->route('admin.blog-posts.edit', $blogPost);
    }

    /**
     * Delete a blog post
     *
     * @param \App\Models\BlogPost $blogPost
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        if ($blogPost->featured_image && Str::startsWith($blogPost->featured_image, 'blog/') && Storage::disk('public')->exists($blogPost->featured_image)) {
            Storage::disk('public')->delete($blogPost->featured_image);
        }

        $blogPost->delete();

        ToastMagic::success('Blog post deleted.');

        return redirect()->route('admin.blog-posts.index');
    }

    /**
     * Get the categories for options
     *
     * @return \Illuminate\Support\Collection
     */
    private function categoriesForOptions(): Collection
    {
        return BlogPost::query()
            ->whereNotNull('category')
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    /**
     * Get the form defaults
     *
     * @param \App\Models\BlogPost $blog
     * @return array
     */
    private function formDefaults(BlogPost $blog): array
    {
        $oldPublished = request()->old('is_published');
        $isPublished = $oldPublished !== null
            ? filter_var($oldPublished, FILTER_VALIDATE_BOOLEAN)
            : (bool) $blog->is_published;

        return [
            'is_published' => $isPublished,
            'published_at' => request()->old(
                'published_at',
                optional($blog->published_at)->format('Y-m-d\TH:i')
            ),
            'reading_time_minutes' => request()->old('reading_time_minutes', $blog->reading_time_minutes),
            'tag_string' => request()->old('tags', implode(', ', $blog->tags ?? [])),
            'featured_image_url' => $this->featuredImageUrl($blog->featured_image),
            'selectedCategoryId' => request()->old('blog_category_id', $blog->blog_category_id),
            'selectedCategoryLabel' => request()->old('category_label', $blog->category),
        ];
    }

    /**
     * Get the featured image URL
     *
     * @param string|null $path
     * @return string|null
     */
    private function featuredImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, 'assets/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    }

    /**
     * Format the tags
     *
     * @param string|null $tags
     * @return array
     */
    private function formatTags(?string $tags): array
    {
        return collect(explode(',', (string) $tags))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Resolve the slug
     *
     * @param string $title
     * @param string|null $slug
     * @param int|null $ignoreId
     * @return string
     */
    private function resolveSlug(string $title, ?string $slug = null, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug ?: $title);
        $finalSlug = $baseSlug ?: Str::slug(Str::random(8));

        $query = BlogPost::query()->where('slug', $finalSlug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $counter = 1;
        while ($query->exists()) {
            $finalSlug = $baseSlug . '-' . $counter;
            $query = BlogPost::query()->where('slug', $finalSlug);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }
            $counter++;
        }

        return $finalSlug;
    }

    /**
     * Validate the request data
     *
     * @param \Illuminate\Http\Request $request
     * @param int|null $blogId
     * @return array
     */
    private function validatedData(Request $request, ?int $blogId = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique(BlogPost::class, 'slug')->ignore($blogId)],
            'blog_category_id' => ['nullable', 'exists:blog_categories,id'],
            'category_label' => ['nullable', 'string', 'max:255'],
            'author_name' => ['nullable', 'string', 'max:120'],
            'excerpt' => ['nullable', 'string', 'max:600'],
            'content' => ['required', 'string'],
            'featured_image' => ['nullable', 'image', 'max:5120'],
            'tags' => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            'reading_time_minutes' => ['nullable', 'integer', 'min:1', 'max:240'],
        ]);
    }

    /**
     * Get the blog category options
     *
     * @return \Illuminate\Support\Collection
     */
    private function blogCategoryOptions(): Collection
    {
        return BlogCategory::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Resolve the category selection
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    private function resolveCategorySelection(Request $request): array
    {
        $category = null;

        if ($request->filled('blog_category_id')) {
            $category = BlogCategory::find($request->input('blog_category_id'));
        }

        $label = $category?->name ?? $request->input('category_label');
        $label = $label !== null ? trim($label) : null;

        if ($label === '') {
            $label = null;
        }

        return [
            'category' => $category,
            'categoryLabel' => $label,
        ];
    }
}
