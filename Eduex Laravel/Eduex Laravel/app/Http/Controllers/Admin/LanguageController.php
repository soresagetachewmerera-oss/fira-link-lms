<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLanguageRequest;
use App\Http\Requests\StoreTranslationKeyRequest;
use App\Services\LanguageManagerService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

/**
 * Controller to manage language files and translations in the Admin panel.
 */
class LanguageController extends Controller
{
    protected LanguageManagerService $languageService;

    public function __construct(LanguageManagerService $languageService)
    {
        $this->languageService = $languageService;
    }

    /**
     * Display all system languages.
     */
    public function index(): View
    {
        $languages = $this->languageService->getSupportedLocales();

        return view('admin.languages.index', [
            'languages' => $languages,
        ]);
    }

    /**
     * Show form to create a new language.
     */
    public function create(): View
    {
        $existing = $this->languageService->getSupportedLocales();
        $allLocales = $this->languageService->getPredefinedLocales();

        // Only show languages that don't already exist
        $availableToCreate = array_diff_key($allLocales, $existing);

        return view('admin.languages.create', [
            'availableLocales' => $availableToCreate,
        ]);
    }

    /**
     * Store a new language directory.
     */
    public function store(StoreLanguageRequest $request): RedirectResponse
    {
        $code = strtolower($request->input('code'));

        $success = $this->languageService->createLanguage($code);

        if ($success) {
            $locales = $this->languageService->getPredefinedLocales();
            $langName = $locales[$code]['name'] ?? strtoupper($code);
            ToastMagic::success("Language '{$langName}' created successfully.");
        } else {
            ToastMagic::error("Failed to create language.");
        }

        return redirect()->route('admin.languages.index');
    }

    /**
     * Delete a language directory.
     */
    public function destroy(string $locale): RedirectResponse
    {
        if ($locale === 'en') {
            ToastMagic::error("The default English language cannot be deleted.");
            return redirect()->route('admin.languages.index');
        }

        $success = $this->languageService->deleteLanguage($locale);

        if ($success) {
            ToastMagic::success("Language locale '{$locale}' deleted successfully.");
        } else {
            ToastMagic::error("Failed to delete language locale.");
        }

        return redirect()->route('admin.languages.index');
    }

    /**
     * Edit translation keys and values for a locale.
     */
    public function editTranslations(string $locale, Request $request): View|RedirectResponse
    {
        $languages = $this->languageService->getSupportedLocales();

        if (!array_key_exists($locale, $languages)) {
            ToastMagic::error("Language locale '{$locale}' not found.");
            return redirect()->route('admin.languages.index');
        }

        $groups = $this->languageService->getTranslationFiles($locale);

        if (empty($groups)) {
            ToastMagic::error("No translation files found for this language.");
            return redirect()->route('admin.languages.index');
        }

        $group = $request->query('group', 'frontend');
        if (!in_array($group, $groups)) {
            $group = $groups[0];
        }

        $translations = $this->languageService->getTranslations($locale, $group);

        // Search/Filter translations if search term provided
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $translations = array_filter($translations, function ($value, $key) use ($search) {
                return stripos($key, $search) !== false || stripos((string)$value, $search) !== false;
            }, ARRAY_FILTER_USE_BOTH);
        }

        // Paginate translations array
        $perPage = 30;
        $currentPage = LengthAwarePaginator::resolveCurrentPage('page');
        $itemCollection = collect($translations);
        $sliced = $itemCollection->slice(($currentPage - 1) * $perPage, $perPage)->all();

        $paginatedTranslations = new LengthAwarePaginator(
            $sliced,
            $itemCollection->count(),
            $perPage,
            $currentPage,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        return view('admin.languages.translations', [
            'locale' => $locale,
            'languageDetails' => $languages[$locale],
            'groups' => $groups,
            'currentGroup' => $group,
            'search' => $search,
            'translations' => $paginatedTranslations,
        ]);
    }

    /**
     * Update translation values in the PHP file.
     */
    public function updateTranslations(string $locale, Request $request): RedirectResponse
    {
        $group = $request->input('group');
        $groups = $this->languageService->getTranslationFiles($locale);

        if (!in_array($group, $groups)) {
            ToastMagic::error("Invalid translation group.");
            return redirect()->back();
        }

        // Get existing translations
        $existing = $this->languageService->getTranslations($locale, $group);

        // Get submitted translations
        $newValues = $request->input('values', []);

        // Merge submitted changes with existing file values (maintains paginated keys that weren't visible in the form)
        $merged = array_merge($existing, $newValues);

        $this->languageService->saveTranslations($locale, $group, $merged);

        ToastMagic::success("Translations updated successfully.");

        // Redirect back, preserving query parameters like page/search
        return redirect()->to(route('admin.languages.translations.edit', [
            'locale' => $locale,
            'group' => $group,
            'page' => $request->input('page'),
            'search' => $request->input('search'),
        ]));
    }

    /**
     * Add a new translation key to all languages.
     */
    public function addTranslationKey(string $locale, StoreTranslationKeyRequest $request): RedirectResponse
    {
        $group = $request->input('group');
        $key = trim($request->input('key'));
        $value = trim($request->input('value'));

        $this->languageService->addKeyToAllLocales($group, $key, $value);

        ToastMagic::success("Translation key '{$key}' added successfully to all languages.");

        return redirect()->route('admin.languages.translations.edit', [
            'locale' => $locale,
            'group' => $group,
        ]);
    }
}
