<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Blog Category Controller
 *
 * This controller handles the management of blog categories.
 *
 * @package App\Http\Controllers\Admin
 */
class BlogCategoryController extends Controller
{
    /**
     * Display all blog categories
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $query = BlogCategory::query()
            ->orderBy('display_order')
            ->orderBy('name');

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $categories = $query->paginate(15)->withQueryString();

        $statistics = [
            'total' => BlogCategory::count(),
            'active' => BlogCategory::where('is_active', true)->count(),
            'inactive' => BlogCategory::where('is_active', false)->count(),
        ];

        return view('admin.blog-categories.index', [
            'categories' => $categories,
            'statistics' => $statistics,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Display the create blog category form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        $category = new BlogCategory([
            'is_active' => true,
            'display_order' => (int) BlogCategory::max('display_order') + 1,
        ]);

        return view('admin.blog-categories.create', [
            'category' => $category,
            'formDefaults' => $this->formDefaults($category),
        ]);
    }

    /**
     * Store a new blog category
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->resolveSlug($data['name'], $data['slug'] ?? null);

        BlogCategory::create($data);

        ToastMagic::success('Blog category created successfully.');

        return redirect()->route('admin.blog-categories.index');
    }

    /**
     * Display a blog category details
     *
     * @param \App\Models\BlogCategory $blogCategory
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(BlogCategory $blogCategory): RedirectResponse
    {
        return redirect()->route('admin.blog-categories.edit', $blogCategory);
    }

    /**
     * Display the edit blog category form
     *
     * @param \App\Models\BlogCategory $blogCategory
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(BlogCategory $blogCategory): View
    {
        return view('admin.blog-categories.edit', [
            'category' => $blogCategory,
            'formDefaults' => $this->formDefaults($blogCategory),
        ]);
    }

    /**
     * Update a blog category
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\BlogCategory $blogCategory
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, BlogCategory $blogCategory): RedirectResponse
    {
        $data = $this->validatedData($request, $blogCategory->id);
        $data['slug'] = $this->resolveSlug($data['name'], $data['slug'] ?? null, $blogCategory->id);

        $blogCategory->update($data);

        ToastMagic::success('Blog category updated successfully.');

        return redirect()->route('admin.blog-categories.edit', $blogCategory);
    }

    /**
     * Delete a blog category
     *
     * @param \App\Models\BlogCategory $blogCategory
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(BlogCategory $blogCategory): RedirectResponse
    {
        BlogPost::where('blog_category_id', $blogCategory->id)
            ->update(['blog_category_id' => null]);

        $blogCategory->delete();

        ToastMagic::success('Blog category deleted.');

        return redirect()->route('admin.blog-categories.index');
    }

    /**
     * Validate the request data
     *
     * @param \Illuminate\Http\Request $request
     * @param int|null $categoryId
     * @return array
     */
    private function validatedData(Request $request, ?int $categoryId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'nullable',
                'string',
                'max:140',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('blog_categories', 'slug')->ignore($categoryId),
            ],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'in:0,1,true,false,on,off'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'slug.regex' => 'Slug may only contain lowercase letters, numbers, and hyphens.',
        ]);

        $data['is_active'] = filter_var($request->input('is_active', $data['is_active'] ?? true), FILTER_VALIDATE_BOOLEAN);
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['color'] = $data['color'] ?? null;

        return $data;
    }

    /**
     * Get the form defaults
     *
     * @param \App\Models\BlogCategory $category
     * @return array
     */
    private function formDefaults(BlogCategory $category): array
    {
        return [
            'is_active' => (bool) old('is_active', $category->is_active ?? true),
            'display_order' => (int) old('display_order', $category->display_order ?? 0),
            'color' => old('color', $category->color),
        ];
    }

    private function resolveSlug(string $name, ?string $slug = null, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug ?: $name);
        $finalSlug = $baseSlug ?: Str::slug(Str::random(8));

        $query = BlogCategory::query()->where('slug', $finalSlug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $counter = 1;
        while ($query->exists()) {
            $candidate = $baseSlug . '-' . $counter;
            $query = BlogCategory::query()->where('slug', $candidate);
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }

            if (! $query->exists()) {
                $finalSlug = $candidate;
                break;
            }

            $counter++;
        }

        return $finalSlug;
    }
}
