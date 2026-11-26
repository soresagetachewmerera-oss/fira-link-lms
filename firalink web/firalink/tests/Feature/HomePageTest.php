<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Website home page (unslop pass 2026-11-21): renders, keeps the brand, and makes no claim the
 * platform cannot back today (market sources are off, Gemini is off).
 */
class HomePageTest extends TestCase
{
    use DatabaseTransactions;

    public function test_home_page_renders_with_its_sections_in_order(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $order = ['The trade platform built for Ethiopian coffee', 'Practical trade courses', 'Core pillars of Fira Link Platform', 'Explore Fira Link Platform'];
        $positions = array_map(fn ($t) => strpos($html, $t), $order);
        foreach ($positions as $i => $pos) {
            $this->assertNotFalse($pos, "Missing: {$order[$i]}");
        }
        $this->assertSame($positions, array_values(collect($positions)->sort()->all()), 'Sections changed order.');
        $this->assertStringContainsString('var(--fira-primary)', $html, 'Brand color token is used.');
    }

    public function test_home_page_has_no_slop_or_unbacked_claims(): void
    {
        $page = $this->get('/')->assertOk()->getContent();
        // The page's own content, from the hero to the end of the site links (not the shared header and footer).
        $start = strpos($page, '<section class="fira-hero"');
        $end = strpos($page, '</section>', strpos($page, 'Explore Fira Link Platform'));
        $html = substr($page, $start, $end - $start);

        foreach ([
            'wow fadeInUp', 'purple-highlight', 'fira-video-ambient-glow', 'parallaxie',
            'LEARNER STORIES', 'FIRA LINK ACADEMY CATALOG', 'Subscribed successfully!',
            'live ICO benchmark', 'live market data', 'AI trade advisor',
        ] as $gone) {
            $this->assertStringNotContainsString($gone, $html, $gone);
        }
    }
}
