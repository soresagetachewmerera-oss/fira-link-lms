<?php

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Course;
use Illuminate\Support\Str;

class SeoService
{
    /**
     * Get clean absolute canonical URL for current request.
     */
    public function canonicalUrl(?string $custom = null): string
    {
        if ($custom) {
            return $this->normalizeUrl($custom);
        }

        $rawBase = config('seo.canonical_domain', 'https://fira-link.com');
        $base = preg_replace('/:\d+$/', '', Str::replaceFirst('http://', 'https://', rtrim($rawBase, '/')));
        $path = '/' . ltrim(request()->path(), '/');

        // Clean home path
        if ($path === '/' || $path === '') {
            return $base . '/';
        }

        // Keep pagination query if present and valid
        $page = request()->query('page');
        $query = '';
        if ($page && is_numeric($page) && (int) $page > 1) {
            $query = '?page=' . (int) $page;
        }

        return $base . rtrim($path, '/') . $query;
    }

    /**
     * Normalize URL string.
     */
    public function normalizeUrl(string $url): string
    {
        if (!Str::startsWith($url, ['http://', 'https://'])) {
            $base = rtrim(config('seo.canonical_domain', config('app.url', 'https://fira-link.com')), '/');
            $url = $base . '/' . ltrim($url, '/');
        }

        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? 'fira-link.com';
        $path = $parsed['path'] ?? '/';
        
        // Remove trailing slash unless root
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $normalized = "{$scheme}://{$host}{$path}";

        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $queryParams);
            if (isset($queryParams['page']) && is_numeric($queryParams['page']) && (int) $queryParams['page'] > 1) {
                $normalized .= '?page=' . (int) $queryParams['page'];
            }
        }

        return $normalized;
    }

    /**
     * Generate Schema.org WebSite JSON-LD
     */
    public function webSiteSchema(): array
    {
        $base = rtrim(config('seo.canonical_domain', 'https://fira-link.com'), '/') . '/';

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $base . '#website',
            'url' => $base,
            'name' => config('seo.brand_name', 'Fira Link Platform'),
            'alternateName' => config('seo.alternate_names', ['Fira Link', 'FiraLink Platform']),
            'description' => config('seo.default_description'),
            'publisher' => [
                '@id' => $base . '#organization',
            ],
            'inLanguage' => 'en-US',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $base . 'courses?search={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * Generate Schema.org EducationalOrganization JSON-LD
     */
    public function organizationSchema(): array
    {
        $base = rtrim(config('seo.canonical_domain', 'https://fira-link.com'), '/') . '/';
        $contact = config('seo.contact', []);
        $social = array_values(config('seo.social_links', []));

        return [
            '@context' => 'https://schema.org',
            '@type' => 'EducationalOrganization',
            '@id' => $base . '#organization',
            'name' => config('seo.brand_name', 'Fira Link Platform'),
            'legalName' => config('seo.legal_name', 'Fira Link Business Solution'),
            'url' => $base,
            'logo' => [
                '@type' => 'ImageObject',
                '@id' => $base . '#logo',
                'url' => $base . 'logo.png',
                'caption' => config('seo.brand_name', 'Fira Link Platform'),
                'width' => 512,
                'height' => 512,
            ],
            'image' => $base . 'og-image.png',
            'description' => 'Fira Link Platform is the specialized digital trade and business development platform of Fira Link Business Solution, providing practical import-export, customs compliance, and port logistics training for Ethiopia and East Africa.',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $contact['address'] ?? 'Bole Sub-City',
                'addressLocality' => $contact['city'] ?? 'Addis Ababa',
                'addressCountry' => 'ET',
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => $contact['phone'] ?? '+251 911 123456',
                'contactType' => $contact['contact_type'] ?? 'customer service',
                'email' => $contact['email'] ?? 'info@firalink.com',
                'availableLanguage' => ['English', 'Amharic'],
            ],
            'sameAs' => $social,
        ];
    }

    /**
     * Generate Schema.org SiteNavigationElement JSON-LD
     */
    public function siteNavigationSchema(): array
    {
        $base = rtrim(config('seo.canonical_domain', 'https://fira-link.com'), '/');
        $navItems = config('seo.navigation', []);
        $elements = [];

        foreach ($navItems as $index => $item) {
            $elements[] = [
                '@type' => 'SiteNavigationElement',
                'position' => $index + 1,
                'name' => $item['name'],
                'url' => $base . $item['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $elements,
        ];
    }

    /**
     * Generate Schema.org BreadcrumbList JSON-LD
     */
    public function breadcrumbSchema(array $crumbs): array
    {
        $base = rtrim(config('seo.canonical_domain', 'https://fira-link.com'), '/');
        $items = [];

        // Always start with Home
        $items[] = [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => $base . '/',
        ];

        $pos = 2;
        foreach ($crumbs as $key => $value) {
            if (is_array($value) && isset($value['name'])) {
                $name = $value['name'];
                $url = $value['url'] ?? $base;
            } else {
                $name = is_string($key) ? $key : ($value['name'] ?? 'Page');
                $url = is_string($value) ? $value : ($value['url'] ?? $base);
            }

            if (strtolower(trim((string) $name)) === 'home') {
                continue;
            }

            $fullUrl = Str::startsWith($url, 'http') ? $url : $base . '/' . ltrim($url, '/');
            $items[] = [
                '@type' => 'ListItem',
                'position' => $pos,
                'name' => (string) $name,
                'item' => $fullUrl,
            ];
            $pos++;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * Generate Schema.org Course JSON-LD (strictly real data, no fake reviews)
     */
    public function courseSchema(Course $course): array
    {
        $base = rtrim(config('seo.canonical_domain', 'https://fira-link.com'), '/');
        $url = $base . '/courses/' . $course->id;

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            '@id' => $url . '#course',
            'name' => $course->title,
            'description' => Str::limit(strip_tags((string) $course->description), 250),
            'url' => $url,
            'provider' => [
                '@type' => 'EducationalOrganization',
                'name' => config('seo.brand_name', 'Fira Link Platform'),
                'sameAs' => $base . '/',
            ],
            'inLanguage' => 'en',
            'hasCourseInstance' => [
                '@type' => 'CourseInstance',
                'courseMode' => 'Online',
                'courseWorkload' => 'Self-paced practical modules',
            ],
        ];

        // Add real pricing offer if available
        $price = $course->sale_price ?? $course->regular_price;
        if ($price && (float) $price > 0) {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => (float) $price,
                'priceCurrency' => 'ETB',
                'availability' => 'https://schema.org/InStock',
                'url' => $url,
                'validFrom' => $course->created_at ? $course->created_at->format('Y-m-d') : date('Y-m-d'),
            ];
        } elseif ($course->pricing_model === 'free') {
            $schema['offers'] = [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'ETB',
                'availability' => 'https://schema.org/InStock',
                'url' => $url,
            ];
        }

        return $schema;
    }

    /**
     * Generate Schema.org FAQPage JSON-LD
     */
    public function faqSchema(array $faqs): array
    {
        $entities = [];

        foreach ($faqs as $item) {
            if (!empty($item['question']) && !empty($item['answer'])) {
                $entities[] = [
                    '@type' => 'Question',
                    'name' => strip_tags($item['question']),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($item['answer']),
                    ],
                ];
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /**
     * Generate Schema.org Article JSON-LD for Blog Posts
     */
    public function articleSchema(BlogPost $post): array
    {
        $base = rtrim(config('seo.canonical_domain', 'https://fira-link.com'), '/');
        $url = $base . '/blog/' . $post->slug;

        $imageUrl = $post->featured_image
            ? (Str::startsWith($post->featured_image, 'http') ? $post->featured_image : $base . '/' . ltrim($post->featured_image, '/'))
            : $base . '/og-image.png';

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            '@id' => $url . '#article',
            'headline' => $post->title,
            'description' => Str::limit(strip_tags((string) ($post->excerpt ?: $post->content)), 200),
            'image' => $imageUrl,
            'datePublished' => ($post->published_at ?? $post->created_at)?->toIso8601String() ?? date('c'),
            'dateModified' => ($post->updated_at ?? $post->created_at)?->toIso8601String() ?? date('c'),
            'author' => [
                '@type' => 'Person',
                'name' => $post->author_name ?: 'Fira Link Trade Faculty',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('seo.brand_name', 'Fira Link Platform'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $base . '/logo.png',
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $url,
            ],
        ];
    }
}
