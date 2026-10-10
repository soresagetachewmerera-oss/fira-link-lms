<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomPage;
use App\Models\FrontendSetting;
use App\Services\ThemeService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Frontend Setting Controller
 *
 * This controller handles the management of frontend settings.
 *
 * @package App\Http\Controllers\Admin
 */
class FrontendSettingController extends Controller
{
    protected array $coreSeoPages = [
        'home' => 'Home Page',
        'courses' => 'Courses Listing',
        'faq' => 'FAQ',
        'events' => 'Events',
        'instructors' => 'Instructors',
        'about' => 'About',
        'blog' => 'Blog Index',
        'contact' => 'Contact',
        'testimonials' => 'Testimonials',
    ];

    /**
     * Display the edit frontend settings form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(): View
    {
        $branding = FrontendSetting::getValue('footer.branding', [
            'about_text' => null,
            'copyright' => null,
            'tagline' => null,
        ]);

        $contact = FrontendSetting::getValue('footer.contact', [
            'email' => null,
            'phone' => null,
            'address' => null,
            'hours' => null,
        ]);

        $socialLinks = FrontendSetting::getValue('footer.social_links', []);

        return view('admin.frontend.settings.edit', [
            'branding' => $branding,
            'contact' => $contact,
            'socialLinks' => $socialLinks,
            'maxSocialLinks' => 8,
        ]);
    }

    /**
     * Display the edit FAQ form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function faq(): View
    {
        $faqItems = FrontendSetting::getValue('pages.faq.items', []);

        return view('admin.frontend.settings.faq', [
            'faqItems' => $faqItems,
            'maxFaqItems' => 12,
        ]);
    }

    /**
     * Update the FAQ content
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateFaq(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'faqs' => ['nullable', 'array'],
            'faqs.*.question' => ['nullable', 'string', 'max:255'],
            'faqs.*.answer' => ['nullable', 'string'],
        ]);

        $faqItems = collect($validated['faqs'] ?? [])
            ->filter(fn($item) => filled($item['question'] ?? null) || filled($item['answer'] ?? null))
            ->map(fn($item) => [
                'question' => $item['question'] ?? '',
                'answer' => $item['answer'] ?? '',
            ])
            ->values()
            ->all();

        FrontendSetting::setValue('pages.faq.items', $faqItems);

        ToastMagic::success('FAQ content updated successfully.');

        return redirect()->route('admin.frontend.faq.edit');
    }

    /**
     * Display the edit SEO form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function seo(): View
    {
        $metaEntries = FrontendSetting::getValue('pages.seo.meta', []);
        $customPages = CustomPage::orderBy('title')->get();

        return view('admin.frontend.settings.seo', [
            'corePages' => $this->coreSeoPages,
            'customPages' => $customPages,
            'metaEntries' => $metaEntries,
        ]);
    }

    /**
     * Update the SEO content
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateSeo(Request $request): RedirectResponse
    {
        $metaInput = $request->input('meta', []);
        $validSlugs = collect($this->coreSeoPages)->keys()->merge(
            CustomPage::query()->pluck('slug')
        )->unique();

        $metaEntries = [];

        foreach ($validSlugs as $slug) {
            $title = data_get($metaInput, $slug . '.title');
            $description = data_get($metaInput, $slug . '.description');

            if (!filled($title) && !filled($description)) {
                continue;
            }

            $request->validate([
                "meta.$slug.title" => ['nullable', 'string', 'max:255'],
                "meta.$slug.description" => ['nullable', 'string', 'max:255'],
            ]);

            $metaEntries[$slug] = [
                'title' => $title ?? '',
                'description' => $description ?? '',
            ];
        }

        FrontendSetting::setValue('pages.seo.meta', $metaEntries);

        ToastMagic::success('SEO metadata updated successfully.');

        return redirect()->route('admin.frontend.seo.edit');
    }

    /**
     * Display the edit marquee form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function marquee(): View
    {
        $items = FrontendSetting::getValue('pages.marquee.items', []);

        return view('admin.frontend.settings.marquee', [
            'items' => $items,
            'maxItems' => 20,
        ]);
    }

    /**
     * Display the edit contact form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function contact(): View
    {
        return view('admin.frontend.settings.contact', [
            'hero' => FrontendSetting::getValue('pages.contact.hero', []),
            'methods' => FrontendSetting::getValue('pages.contact.methods', []),
            'form' => FrontendSetting::getValue('pages.contact.form', []),
            'map' => FrontendSetting::getValue('pages.contact.map', []),
            'maxMethods' => 8,
        ]);
    }

    /**
     * Update the contact content
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateContact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hero.eyebrow' => ['nullable', 'string', 'max:120'],
            'hero.heading' => ['nullable', 'string', 'max:180'],
            'hero.description' => ['nullable', 'string'],

            'methods' => ['nullable', 'array'],
            'methods.*.icon' => ['nullable', 'string', 'max:80'],
            'methods.*.label' => ['nullable', 'string', 'max:120'],
            'methods.*.type' => ['nullable', 'string', 'max:40'],
            'methods.*.value' => ['nullable', 'string', 'max:255'],
            'methods.*.link' => ['nullable', 'string', 'max:255'],

            'form.heading' => ['nullable', 'string', 'max:180'],
            'form.description' => ['nullable', 'string'],
            'form.submit_label' => ['nullable', 'string', 'max:80'],
            'form.success_message' => ['nullable', 'string', 'max:255'],
            'form.subject_prefix' => ['nullable', 'string', 'max:60'],

            'map.embed_url' => ['nullable', 'string'],
            'map.caption' => ['nullable', 'string', 'max:255'],
        ]);

        $methods = collect($validated['methods'] ?? [])
            ->map(function (array $method) {
                $value = trim($method['value'] ?? '');
                $label = trim($method['label'] ?? '');

                if ($value === '' && $label === '') {
                    return null;
                }

                return [
                    'icon' => trim($method['icon'] ?? ''),
                    'label' => $label !== '' ? $label : 'Contact Info',
                    'type' => trim($method['type'] ?? ''),
                    'value' => $value,
                    'link' => trim($method['link'] ?? ''),
                ];
            })
            ->filter()
            ->values()
            ->take(8)
            ->all();

        FrontendSetting::setValue('pages.contact.hero', $validated['hero'] ?? []);
        FrontendSetting::setValue('pages.contact.methods', $methods);
        FrontendSetting::setValue('pages.contact.form', $validated['form'] ?? []);
        FrontendSetting::setValue('pages.contact.map', $validated['map'] ?? []);

        ToastMagic::success('Contact page content updated successfully.');

        return redirect()->route('admin.frontend.contact.edit');
    }

    /**
     * Update the marquee content
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateMarquee(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.text' => ['nullable', 'string', 'max:120'],
            'items.*.image' => ['nullable', 'string', 'max:255'],
            'items.*.image_file' => ['nullable', 'mimetypes:image/jpeg,image/png,image/webp,image/svg+xml', 'max:2048'],
        ]);

        $itemsInput = $validated['items'] ?? [];
        $fileInput = $request->file('items', []);

        $items = [];

        foreach ($itemsInput as $key => $item) {
            $text = trim($item['text'] ?? '');

            if ($text === '') {
                continue;
            }

            $imagePath = trim($item['image'] ?? '');

            if (($fileInput[$key]['image_file'] ?? null) && $fileInput[$key]['image_file']->isValid()) {
                $storedPath = $fileInput[$key]['image_file']->store('marquee', 'public');
                $imagePath = 'storage/' . $storedPath;
            }

            $items[] = [
                'text' => $text,
                'image' => $imagePath,
            ];
        }

        FrontendSetting::setValue('pages.marquee.items', $items);

        ToastMagic::success('Marquee content updated successfully.');

        return redirect()->route('admin.frontend.marquee.edit');
    }

    /**
     * Display the edit homepage form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function homepage(): View
    {
        $hero = FrontendSetting::getValue('pages.home.hero', []);
        $testimonial = FrontendSetting::getValue('pages.home.testimonial', []);
        $blog = FrontendSetting::getValue('pages.home.blog', []);
        $about = FrontendSetting::getValue('pages.home.about', []);
        $discount = FrontendSetting::getValue('pages.home.discount', []);
        $features = FrontendSetting::getValue('pages.home.features', []);
        $cta = FrontendSetting::getValue('pages.home.cta', []);

        return view('admin.frontend.settings.home', [
            'hero' => $hero,
            'testimonial' => $testimonial,
            'blog' => $blog,
            'about' => $about,
            'discount' => $discount,
            'features' => $features,
            'cta' => $cta,
        ]);
    }

    /**
     * Update the homepage content
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateHomepage(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hero.preheading' => ['nullable', 'string', 'max:120'],
            'hero.heading' => ['nullable', 'string', 'max:180'],
            'hero.description' => ['nullable', 'string'],
            'hero.cta_label' => ['nullable', 'string', 'max:80'],
            'hero.cta_url' => ['nullable', 'string', 'max:255'],
            'testimonial.title' => ['nullable', 'string', 'max:120'],
            'testimonial.subtitle' => ['nullable', 'string'],
            'testimonial.cta_label' => ['nullable', 'string', 'max:80'],
            'testimonial.cta_url' => ['nullable', 'string', 'max:255'],
            'blog.title' => ['nullable', 'string', 'max:120'],
            'blog.subtitle' => ['nullable', 'string'],
            'blog.description' => ['nullable', 'string'],
            'about.subheading' => ['nullable', 'string', 'max:120'],
            'about.heading' => ['nullable', 'string', 'max:180'],
            'about.description' => ['nullable', 'string'],
            'about.cta_label' => ['nullable', 'string', 'max:80'],
            'about.cta_url' => ['nullable', 'string', 'max:255'],
            'about.author_name' => ['nullable', 'string', 'max:120'],
            'about.author_title' => ['nullable', 'string', 'max:120'],
            'discount.heading' => ['nullable', 'string', 'max:180'],
            'discount.description' => ['nullable', 'string'],
            'discount.primary_label' => ['nullable', 'string', 'max:80'],
            'discount.primary_url' => ['nullable', 'string', 'max:255'],
            'discount.secondary_label' => ['nullable', 'string', 'max:80'],
            'discount.secondary_url' => ['nullable', 'string', 'max:255'],
            'features.title' => ['nullable', 'string', 'max:120'],
            'features.subtitle' => ['nullable', 'string'],
            'features.description' => ['nullable', 'string'],
            'features.highlight_count' => ['nullable', 'integer', 'min:0'],
            'features.highlight_label' => ['nullable', 'string', 'max:120'],
            'features.items' => ['nullable', 'array'],
            'features.items.*.icon' => ['nullable', 'string', 'max:80'],
            'features.items.*.title' => ['nullable', 'string', 'max:120'],
            'features.items.*.description' => ['nullable', 'string'],
            'cta.heading' => ['nullable', 'string', 'max:180'],
            'cta.description' => ['nullable', 'string'],
            'cta.placeholder' => ['nullable', 'string', 'max:120'],
            'cta.button_label' => ['nullable', 'string', 'max:80'],
        ]);

        FrontendSetting::setValue('pages.home.hero', $validated['hero'] ?? []);
        FrontendSetting::setValue('pages.home.testimonial', $validated['testimonial'] ?? []);
        FrontendSetting::setValue('pages.home.blog', $validated['blog'] ?? []);
        FrontendSetting::setValue('pages.home.about', $validated['about'] ?? []);
        FrontendSetting::setValue('pages.home.discount', $validated['discount'] ?? []);
        FrontendSetting::setValue('pages.home.features', $validated['features'] ?? []);
        FrontendSetting::setValue('pages.home.cta', $validated['cta'] ?? []);

        ToastMagic::success('Homepage content updated successfully.');

        return redirect()->route('admin.frontend.home.edit');
    }

    /**
     * Display the edit about form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function about(): View
    {
        return view('admin.frontend.settings.about', [
            'hero' => FrontendSetting::getValue('pages.about.hero', []),
            'features' => FrontendSetting::getValue('pages.about.features', []),
            'highlights' => FrontendSetting::getValue('pages.about.highlights', []),
            'cta' => FrontendSetting::getValue('pages.about.cta', []),
        ]);
    }

    /**
     * Update the about content
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateAbout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hero.subheading' => ['nullable', 'string', 'max:120'],
            'hero.heading' => ['nullable', 'string', 'max:180'],
            'hero.description' => ['nullable', 'string'],
            'hero.cta_label' => ['nullable', 'string', 'max:80'],
            'hero.cta_url' => ['nullable', 'string', 'max:255'],
            'hero.author_name' => ['nullable', 'string', 'max:120'],
            'hero.author_title' => ['nullable', 'string', 'max:120'],
            'features.title' => ['nullable', 'string', 'max:120'],
            'features.subtitle' => ['nullable', 'string'],
            'features.description' => ['nullable', 'string'],
            'features.items' => ['nullable', 'array'],
            'features.items.*.icon' => ['nullable', 'string', 'max:80'],
            'features.items.*.text' => ['nullable', 'string'],
            'highlights.left' => ['nullable', 'array'],
            'highlights.left.*.icon' => ['nullable', 'string', 'max:80'],
            'highlights.left.*.count' => ['nullable'],
            'highlights.left.*.label' => ['nullable', 'string', 'max:120'],
            'highlights.right' => ['nullable', 'array'],
            'highlights.right.*.icon' => ['nullable', 'string', 'max:80'],
            'highlights.right.*.count' => ['nullable'],
            'highlights.right.*.label' => ['nullable', 'string', 'max:120'],
            'cta.heading' => ['nullable', 'string', 'max:180'],
            'cta.description' => ['nullable', 'string'],
            'cta.primary_label' => ['nullable', 'string', 'max:80'],
            'cta.primary_url' => ['nullable', 'string', 'max:255'],
            'cta.secondary_label' => ['nullable', 'string', 'max:80'],
            'cta.secondary_url' => ['nullable', 'string', 'max:255'],
        ]);

        FrontendSetting::setValue('pages.about.hero', $validated['hero'] ?? []);
        FrontendSetting::setValue('pages.about.features', $validated['features'] ?? []);
        FrontendSetting::setValue('pages.about.highlights', $validated['highlights'] ?? []);
        FrontendSetting::setValue('pages.about.cta', $validated['cta'] ?? []);

        ToastMagic::success('About page content updated successfully.');

        return redirect()->route('admin.frontend.about.edit');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'about_text' => ['nullable', 'string'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'copyright' => ['nullable', 'string', 'max:255'],
            'contact.email' => ['nullable', 'email', 'max:190'],
            'contact.phone' => ['nullable', 'string', 'max:80'],
            'contact.address' => ['nullable', 'string', 'max:255'],
            'contact.hours' => ['nullable', 'string', 'max:255'],
            'social_links' => ['nullable', 'array'],
            'social_links.*.label' => ['nullable', 'string', 'max:60'],
            'social_links.*.url' => ['nullable', 'string', 'max:255'],
            'social_links.*.icon' => ['nullable', 'string', 'max:60'],
        ]);

        $branding = [
            'about_text' => $validated['about_text'] ?? null,
            'tagline' => $validated['tagline'] ?? null,
            'copyright' => $validated['copyright'] ?? null,
        ];

        $contact = $validated['contact'] ?? [];

        $socialLinks = collect($validated['social_links'] ?? [])
            ->filter(fn($link) => filled($link['label'] ?? null) && filled($link['url'] ?? null))
            ->values()
            ->all();

        FrontendSetting::setValue('footer.branding', $branding);
        FrontendSetting::setValue('footer.contact', $contact);
        FrontendSetting::setValue('footer.social_links', $socialLinks);

        ToastMagic::success('Frontend settings updated successfully.');

        return redirect()
            ->route('admin.frontend.settings.edit');
    }

    /**
     * Display the theme management page.
     */
    public function theme(): View
    {
        $themeService = app(ThemeService::class);

        return view('admin.frontend.settings.theme', [
            'themes' => $themeService->getAvailableThemes(),
            'activeTheme' => $themeService->getActiveTheme(),
            'activeVariant' => $themeService->getActiveVariant(),
        ]);
    }

    /**
     * Update/activate the homepage theme and variant.
     */
    public function updateTheme(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['nullable', 'string', 'max:120'],
            'variant' => ['nullable', 'string', 'max:120'],
        ]);

        $themeService = app(ThemeService::class);
        $slug = $validated['theme'] ?? null;
        $variant = $validated['variant'] ?? 'index';

        try {
            if (empty($slug)) {
                $themeService->deactivate();
                ToastMagic::success('Reverted to the default homepage theme successfully.');
            } else {
                $themeService->activate($slug, $variant);
                
                $themes = $themeService->getAvailableThemes();
                $themeName = $themes[$slug]['name'] ?? $slug;
                $variantLabel = $themeService->getVariantLabel($slug, $variant);

                if ($slug === 'eduna' && filter_var(env('DEMO_MODE'), FILTER_VALIDATE_BOOLEAN)) {
                    ToastMagic::success("Theme '{$themeName}' (variant: '{$variantLabel}') activated successfully. It will automatically revert to default after 10 minutes in demo mode.");
                } else {
                    ToastMagic::success("Theme '{$themeName}' (variant: '{$variantLabel}') activated successfully.");
                }
            }
        } catch (\Exception $e) {
            ToastMagic::error($e->getMessage());
        }

        return redirect()->route('admin.frontend.theme.edit');
    }

    /**
     * Upload and install a theme ZIP file.
     */
    public function uploadTheme(Request $request): RedirectResponse
    {
        $request->validate([
            'theme_zip' => ['required', 'file', 'mimes:zip', 'max:102400'], // max 100MB
        ]);

        $zipFile = $request->file('theme_zip');
        $themeService = app(ThemeService::class);

        // Store ZIP temporarily
        $tempPath = $zipFile->storeAs('tmp', 'upload-' . Str::uuid() . '.zip');
        $absolutePath = Storage::path($tempPath);

        try {
            $meta = $themeService->installFromZip($absolutePath);
            ToastMagic::success("Theme '{$meta['name']}' uploaded and installed successfully.");
        } catch (\Exception $e) {
            // Cleanup temp file if still exists
            if (Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }
            ToastMagic::error('Installation failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.frontend.theme.edit');
    }

    /**
     * Delete an installed theme.
     */
    public function deleteTheme(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['required', 'string', 'max:120'],
        ]);

        $slug = $validated['theme'];
        $themeService = app(ThemeService::class);

        try {
            $themeService->deleteTheme($slug);
            ToastMagic::success("Theme '{$slug}' deleted successfully.");
        } catch (\Exception $e) {
            ToastMagic::error($e->getMessage());
        }

        return redirect()->route('admin.frontend.theme.edit');
    }
}
