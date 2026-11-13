<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            ['name' => 'Main Navigation', 'location' => 'main-navigation'],
            ['name' => 'Mobile Navigation', 'location' => 'mobile-navigation'],
            ['name' => 'Footer Links', 'location' => 'footer-links'],
            ['name' => 'Quick Links', 'location' => 'quick-links'],
        ];

        foreach ($menus as $menuData) {
            Menu::updateOrCreate(
                ['location' => $menuData['location']],
                [
                    'name' => $menuData['name'],
                    'slug' => Str::slug($menuData['name']),
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }

        $mainItems = [
            ['title' => 'Home', 'url' => '/'],
            ['title' => 'Courses', 'url' => '/courses'],
            ['title' => 'Events', 'url' => '/events'],
            ['title' => 'Tours', 'url' => '/tours'],
            ['title' => 'Free Tools', 'url' => '/free-tools'],
        ];

        $footerItems = [
            ['title' => 'Courses', 'url' => '/courses'],
            ['title' => 'Events', 'url' => '/events'],
            ['title' => 'Tours', 'url' => '/tours'],
            ['title' => 'About Us', 'url' => '/about'],
            ['title' => 'Blog', 'url' => '/blog'],
            ['title' => 'Contact Us', 'url' => '/contact'],
        ];

        $quickItems = [
            ['title' => 'Privacy Policy', 'route_name' => 'pages.show', 'route_parameters' => ['customPage' => 'privacy-policy']],
            ['title' => 'Terms & Conditions', 'route_name' => 'pages.show', 'route_parameters' => ['customPage' => 'terms-and-conditions']],
            ['title' => 'GDPR Compliance', 'route_name' => 'pages.show', 'route_parameters' => ['customPage' => 'gdpr-compliance']],
            ['title' => 'FAQs', 'url' => '/faq'],
            ['title' => 'Testimonials', 'url' => '/testimonials'],
        ];

        $this->seedMenuItems('main-navigation', $mainItems);
        $this->seedMenuItems('mobile-navigation', $mainItems);
        $this->seedMenuItems('footer-links', $footerItems);
        $this->seedMenuItems('quick-links', $quickItems);
    }

    private function seedMenuItems(string $location, array $items): void
    {
        $menu = Menu::where('location', $location)->first();

        if (! $menu) {
            return;
        }

        $menu->allItems()->delete();

        foreach ($items as $index => $item) {
            MenuItem::create([
                'menu_id' => $menu->id,
                'title' => $item['title'],
                'url' => $item['url'] ?? null,
                'route_name' => $item['route_name'] ?? null,
                'route_parameters' => $item['route_parameters'] ?? null,
                'target' => '_self',
                'order' => $index,
                'is_active' => true,
            ]);
        }
    }
}
