<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Course Category Controller
 *
 * This controller handles the management of course categories.
 *
 * @package App\Http\Controllers\Admin
 */
class CourseCategoryController extends Controller
{
    /**
     * Display all course categories
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $query = CourseCategory::query()
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
            'total' => CourseCategory::count(),
            'active' => CourseCategory::where('is_active', true)->count(),
            'inactive' => CourseCategory::where('is_active', false)->count(),
        ];

        return view('admin.course-categories.index', [
            'categories' => $categories,
            'statistics' => $statistics,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Display the create course category form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        $nextOrder = (int) CourseCategory::max('display_order') + 1;

        $category = new CourseCategory([
            'is_active' => true,
            'display_order' => $nextOrder,
        ]);

        return view('admin.course-categories.create', [
            'category' => $category,
            'formDefaults' => $this->formDefaults($category),
        ]);
    }

    /**
     * Store a new course category
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['slug'] = $this->resolveSlug($data['name'], $data['slug'] ?? null);

        CourseCategory::create($data);

        ToastMagic::success('Course category created successfully.');

        return redirect()->route('admin.course-categories.index');
    }

    /**
     * Display a course category details
     *
     * @param \App\Models\CourseCategory $courseCategory
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(CourseCategory $courseCategory): RedirectResponse
    {
        return redirect()->route('admin.course-categories.edit', $courseCategory);
    }

    /**
     * Display the edit course category form
     *
     * @param \App\Models\CourseCategory $courseCategory
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(CourseCategory $courseCategory): View
    {
        return view('admin.course-categories.edit', [
            'category' => $courseCategory,
            'formDefaults' => $this->formDefaults($courseCategory),
        ]);
    }

    /**
     * Update a course category
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\CourseCategory $courseCategory
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, CourseCategory $courseCategory): RedirectResponse
    {
        $data = $this->validatedData($request, $courseCategory->id);
        $data['slug'] = $this->resolveSlug($data['name'], $data['slug'] ?? null, $courseCategory->id);

        $courseCategory->update($data);

        ToastMagic::success('Course category updated successfully.');

        return redirect()->route('admin.course-categories.edit', $courseCategory);
    }

    /**
     * Delete a course category
     *
     * @param \App\Models\CourseCategory $courseCategory
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(CourseCategory $courseCategory): RedirectResponse
    {
        Course::where('course_category_id', $courseCategory->id)
            ->update(['course_category_id' => null]);

        $courseCategory->delete();

        ToastMagic::success('Course category deleted.');

        return redirect()->route('admin.course-categories.index');
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
                Rule::unique('course_categories', 'slug')->ignore($categoryId),
            ],
            'icon' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'in:0,1,true,false,on,off'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'slug.regex' => 'Slug may only contain lowercase letters, numbers, and hyphens.',
        ]);

        $data['is_active'] = filter_var($request->input('is_active', $data['is_active'] ?? true), FILTER_VALIDATE_BOOLEAN);
        $data['display_order'] = $data['display_order'] ?? 0;

        return $data;
    }

    /**
     * Get the form defaults
     *
     * @param \App\Models\CourseCategory $category
     * @return array
     */
    private function formDefaults(CourseCategory $category): array
    {
        return [
            'is_active' => (bool) old('is_active', $category->is_active ?? true),
            'display_order' => (int) old('display_order', $category->display_order ?? 0),
        ];
    }

    /**
     * Resolve the slug
     *
     * @param string $name
     * @param string|null $slug
     * @param int|null $ignoreId
     * @return string
     */
    private function resolveSlug(string $name, ?string $slug = null, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($slug ?: $name);
        $finalSlug = $baseSlug ?: Str::slug(Str::random(8));

        $query = CourseCategory::query()->where('slug', $finalSlug);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $counter = 1;
        while ($query->exists()) {
            $candidate = $baseSlug . '-' . $counter;
            $query = CourseCategory::query()->where('slug', $candidate);
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
