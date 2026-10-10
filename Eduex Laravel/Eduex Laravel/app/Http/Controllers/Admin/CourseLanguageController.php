<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseLanguage;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Course Language Controller
 *
 * This controller handles the management of course languages.
 *
 * @package App\Http\Controllers\Admin
 */
class CourseLanguageController extends Controller
{
    /**
     * Display all course languages
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $query = CourseLanguage::query()
            ->orderBy('display_order')
            ->orderBy('name');

        $search = trim((string) $request->query('search', ''));
        $status = $request->query('status');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhere('native_name', 'like', '%' . $search . '%');
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $languages = $query->paginate(15)->withQueryString();

        $statistics = [
            'total' => CourseLanguage::count(),
            'active' => CourseLanguage::where('is_active', true)->count(),
            'inactive' => CourseLanguage::where('is_active', false)->count(),
        ];

        return view('admin.course-languages.index', [
            'languages' => $languages,
            'statistics' => $statistics,
            'search' => $search,
            'status' => $status,
        ]);
    }

    /**
     * Display the create course language form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        $language = new CourseLanguage([
            'is_active' => true,
            'display_order' => (int) CourseLanguage::max('display_order') + 1,
        ]);

        return view('admin.course-languages.create', [
            'language' => $language,
            'formDefaults' => $this->formDefaults($language),
        ]);
    }

    /**
     * Store a new course language
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        CourseLanguage::create($data);

        ToastMagic::success('Course language created successfully.');

        return redirect()->route('admin.course-languages.index');
    }

    /**
     * Display a course language details
     *
     * @param \App\Models\CourseLanguage $courseLanguage
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(CourseLanguage $courseLanguage): RedirectResponse
    {
        return redirect()->route('admin.course-languages.edit', $courseLanguage);
    }

    /**
     * Display the edit course language form
     *
     * @param \App\Models\CourseLanguage $courseLanguage
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(CourseLanguage $courseLanguage): View
    {
        return view('admin.course-languages.edit', [
            'language' => $courseLanguage,
            'formDefaults' => $this->formDefaults($courseLanguage),
        ]);
    }

    /**
     * Update a course language
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\CourseLanguage $courseLanguage
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, CourseLanguage $courseLanguage): RedirectResponse
    {
        $data = $this->validatedData($request, $courseLanguage->id);

        $courseLanguage->update($data);

        ToastMagic::success('Course language updated successfully.');

        return redirect()->route('admin.course-languages.edit', $courseLanguage);
    }

    /**
     * Delete a course language
     *
     * @param \App\Models\CourseLanguage $courseLanguage
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(CourseLanguage $courseLanguage): RedirectResponse
    {
        Course::where('course_language_id', $courseLanguage->id)
            ->update(['course_language_id' => null]);

        $courseLanguage->delete();

        ToastMagic::success('Course language deleted.');

        return redirect()->route('admin.course-languages.index');
    }

    /**
     * Validate the request data
     *
     * @param \Illuminate\Http\Request $request
     * @param int|null $languageId
     * @return array
     */
    private function validatedData(Request $request, ?int $languageId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:20',
                'regex:/^[a-zA-Z\-]+$/',
                Rule::unique('course_languages', 'code')->ignore($languageId),
            ],
            'native_name' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'in:0,1,true,false,on,off'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'code.regex' => 'Language code may only contain letters and hyphens.',
        ]);

        $data['code'] = strtolower($data['code']);
        $data['is_active'] = filter_var($request->input('is_active', $data['is_active'] ?? true), FILTER_VALIDATE_BOOLEAN);
        $data['display_order'] = $data['display_order'] ?? 0;

        return $data;
    }

    /**
     * Get the form defaults
     *
     * @param \App\Models\CourseLanguage $language
     * @return array
     */
    private function formDefaults(CourseLanguage $language): array
    {
        return [
            'is_active' => (bool) old('is_active', $language->is_active ?? true),
            'display_order' => (int) old('display_order', $language->display_order ?? 0),
        ];
    }
}
