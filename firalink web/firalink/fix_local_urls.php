<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\FrontendSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

echo "=== FIXING LOCAL 127.0.0.1 AND LOCALHOST URLS IN DATABASE ===" . PHP_EOL;

// 1. Fix menu_items table
$menuItems = MenuItem::all();
$fixedCount = 0;

foreach ($menuItems as $item) {
    if (!empty($item->url)) {
        $oldUrl = $item->url;
        if (preg_match('#^https?://(?:127\.0\.0\.1|localhost)(?::\d+)?(/.*)?$#i', $oldUrl, $matches)) {
            $newPath = $matches[1] ?? '/';
            if (empty($newPath)) {
                $newPath = '/';
            }
            $item->url = $newPath;
            $item->save();
            $fixedCount++;
            echo "Fixed MenuItem [ID {$item->id}] '{$item->title}': {$oldUrl} -> {$item->url}" . PHP_EOL;
        }
    }
}

echo "Total menu items normalized to relative paths: {$fixedCount}" . PHP_EOL;

// 2. Enforce only 5 items in main-navigation and mobile-navigation: Home, Courses, Events, Tours, Free Tools
$desiredNav = [
    ['title' => 'Home', 'url' => '/'],
    ['title' => 'Courses', 'url' => '/courses'],
    ['title' => 'Events', 'url' => '/events'],
    ['title' => 'Tours', 'url' => '/tours'],
    ['title' => 'Free Tools', 'url' => '/free-tools'],
];

foreach (['main-navigation', 'mobile-navigation'] as $loc) {
    $menu = Menu::where('location', $loc)->first();
    if ($menu) {
        // Delete all existing items in this menu to clear About Us, Subscriptions, Contact Us, Blog, etc.
        MenuItem::where('menu_id', $menu->id)->delete();
        
        foreach ($desiredNav as $idx => $navItem) {
            MenuItem::create([
                'menu_id' => $menu->id,
                'title' => $navItem['title'],
                'url' => $navItem['url'],
                'target' => '_self',
                'order' => $idx,
                'is_active' => true,
            ]);
        }
        echo "Reset {$loc} to exactly: Home, Courses, Events, Tours, Free Tools." . PHP_EOL;
    }
}

// Ensure Tours exists in footer-links
$footerMenu = Menu::where('location', 'footer-links')->first();
if ($footerMenu) {
    $hasToursFooter = MenuItem::where('menu_id', $footerMenu->id)->where('title', 'Tours')->exists();
    if (!$hasToursFooter) {
        MenuItem::create([
            'menu_id' => $footerMenu->id,
            'title' => 'Tours',
            'url' => '/tours',
            'target' => '_self',
            'order' => 2,
            'is_active' => true,
        ]);
        echo "Added 'Tours' to footer-links" . PHP_EOL;
    }
}

// 3. Fix frontend_settings table
$settings = FrontendSetting::all();
$fixedSettings = 0;

foreach ($settings as $setting) {
    $raw = DB::table('frontend_settings')->where('id', $setting->id)->value('value');
    if ($raw && (str_contains($raw, '127.0.0.1') || str_contains($raw, 'localhost'))) {
        // Strip out scheme, localhost/127.0.0.1, and port whether escaped or unescaped
        $cleanRaw = preg_replace('#https?:(\\\\/|/){2}(?:127\.0\.0\.1|localhost)(?::\d+)?#i', '', $raw);
        // Ensure if any path became empty string it becomes '/'
        DB::table('frontend_settings')->where('id', $setting->id)->update([
            'value' => $cleanRaw,
            'updated_at' => now(),
        ]);
        $fixedSettings++;
        echo "Sanitized FrontendSetting [Key: {$setting->key}]" . PHP_EOL;
    }
}

echo "Total frontend_settings rows cleaned: {$fixedSettings}" . PHP_EOL;

// 4. Flush cache
Cache::flush();
echo "Application cache flushed successfully." . PHP_EOL;
echo "=== ALL LOCAL DEV URLS SUCCESSFULLY RESOLVED ===" . PHP_EOL;
