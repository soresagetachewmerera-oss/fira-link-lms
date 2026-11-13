<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Course;
use App\Models\Event;
use App\Models\Tour;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class SitemapController extends Controller
{
    /**
     * Generate dynamic XML sitemap for Google and search engines.
     */
    public function index(): Response
    {
        $baseUrl = rtrim(config('app.url', 'https://fira-link.com'), '/');

        $staticPages = [
            ['loc' => $baseUrl . '/', 'priority' => '1.0', 'changefreq' => 'daily', 'lastmod' => Carbon::now()->toIso8601String()],
            ['loc' => $baseUrl . '/courses', 'priority' => '0.9', 'changefreq' => 'daily', 'lastmod' => Carbon::now()->subDays(1)->toIso8601String()],
            ['loc' => $baseUrl . '/programs', 'priority' => '0.9', 'changefreq' => 'weekly', 'lastmod' => Carbon::now()->subDays(2)->toIso8601String()],
            ['loc' => $baseUrl . '/free-tools', 'priority' => '0.9', 'changefreq' => 'daily', 'lastmod' => Carbon::now()->toIso8601String()],
            ['loc' => $baseUrl . '/about', 'priority' => '0.8', 'changefreq' => 'monthly', 'lastmod' => Carbon::now()->subDays(5)->toIso8601String()],
            ['loc' => $baseUrl . '/pricing', 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => Carbon::now()->subDays(3)->toIso8601String()],
            ['loc' => $baseUrl . '/events', 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => Carbon::now()->subDays(2)->toIso8601String()],
            ['loc' => $baseUrl . '/tours', 'priority' => '0.8', 'changefreq' => 'weekly', 'lastmod' => Carbon::now()->subDays(2)->toIso8601String()],
            ['loc' => $baseUrl . '/blog', 'priority' => '0.8', 'changefreq' => 'daily', 'lastmod' => Carbon::now()->subDays(1)->toIso8601String()],
            ['loc' => $baseUrl . '/faq', 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => Carbon::now()->subDays(7)->toIso8601String()],
            ['loc' => $baseUrl . '/contact', 'priority' => '0.7', 'changefreq' => 'monthly', 'lastmod' => Carbon::now()->subDays(7)->toIso8601String()],
            ['loc' => $baseUrl . '/privacy', 'priority' => '0.5', 'changefreq' => 'monthly', 'lastmod' => Carbon::now()->subMonths(1)->toIso8601String()],
            ['loc' => $baseUrl . '/terms', 'priority' => '0.5', 'changefreq' => 'monthly', 'lastmod' => Carbon::now()->subMonths(1)->toIso8601String()],
        ];

        // Published Courses
        $courses = Course::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->get(['id', 'updated_at']);

        // Published Events
        $events = Event::query()
            ->where('is_published', true)
            ->get(['id', 'slug', 'updated_at']);

        // Published Tours
        $tours = Tour::query()
            ->where('is_published', true)
            ->get(['id', 'slug', 'updated_at']);

        // Published Blog Posts
        $posts = BlogPost::query()
            ->where('is_published', true)
            ->get(['id', 'slug', 'updated_at', 'published_at']);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($staticPages as $page) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($page['loc'], ENT_XML1) . "</loc>\n";
            $xml .= "    <lastmod>" . $page['lastmod'] . "</lastmod>\n";
            $xml .= "    <changefreq>" . $page['changefreq'] . "</changefreq>\n";
            $xml .= "    <priority>" . $page['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }

        foreach ($courses as $c) {
            $lastmod = $c->updated_at ? $c->updated_at->toIso8601String() : Carbon::now()->toIso8601String();
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($baseUrl . '/courses/' . $c->id, ENT_XML1) . "</loc>\n";
            $xml .= "    <lastmod>" . $lastmod . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        foreach ($events as $ev) {
            $lastmod = $ev->updated_at ? $ev->updated_at->toIso8601String() : Carbon::now()->toIso8601String();
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($baseUrl . '/events/' . $ev->slug, ENT_XML1) . "</loc>\n";
            $xml .= "    <lastmod>" . $lastmod . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        foreach ($tours as $t) {
            $lastmod = $t->updated_at ? $t->updated_at->toIso8601String() : Carbon::now()->toIso8601String();
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($baseUrl . '/tours/' . $t->slug, ENT_XML1) . "</loc>\n";
            $xml .= "    <lastmod>" . $lastmod . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        foreach ($posts as $p) {
            $lastmod = ($p->updated_at ?? $p->published_at) ? ($p->updated_at ?? $p->published_at)->toIso8601String() : Carbon::now()->toIso8601String();
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($baseUrl . '/blog/' . $p->slug, ENT_XML1) . "</loc>\n";
            $xml .= "    <lastmod>" . $lastmod . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.7</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
