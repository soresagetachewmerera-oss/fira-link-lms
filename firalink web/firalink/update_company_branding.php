<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\FrontendSetting;
use App\Services\SettingsRepository;
use Illuminate\Support\Facades\Cache;

echo "Updating Company Information and Branding in Database...\n";

// 1. Footer Branding
FrontendSetting::setValue('footer.branding', [
    'about_text' => 'Fira Link Business Solution is Ethiopia’s premier import-export training and business development hub, dedicated to building global trade capabilities and empowering entrepreneurs.',
    'tagline' => 'Art of Excellence!!',
    'copyright' => '© ' . date('Y') . ' Fira Link Business Solution. All rights reserved.',
]);
echo "✓ footer.branding updated.\n";

// 2. Footer Contact
FrontendSetting::setValue('footer.contact', [
    'email' => 'info@firalink.com',
    'phone' => '+251 911 123456',
    'address' => 'Bole Sub-City, Addis Ababa, Ethiopia',
    'hours' => 'Mon - Sat: 8:30am - 5:30pm (EAT)',
    'website' => 'https://fira-link.com',
]);
echo "✓ footer.contact updated.\n";

// 3. Footer Social Links (remove outdated eduex links)
FrontendSetting::setValue('footer.social_links', []);
echo "✓ footer.social_links cleaned.\n";

// 4. About Page Hero (clean Amharic characters)
$aboutHero = FrontendSetting::getValue('pages.about.hero', []);
$aboutHero['subheading'] = 'About Fira Link Business Solution';
$aboutHero['author_title'] = 'Art of Excellence!!';
FrontendSetting::setValue('pages.about.hero', $aboutHero);
echo "✓ pages.about.hero updated.\n";

// 5. Contact Page Methods
FrontendSetting::setValue('pages.contact.methods', [
    [
        'type' => 'email',
        'label' => 'Email Us',
        'value' => 'info@firalink.com',
        'icon' => 'fa-solid fa-envelope',
        'link' => 'mailto:info@firalink.com',
    ],
    [
        'type' => 'phone',
        'label' => 'Call Us',
        'value' => '+251 911 123456',
        'icon' => 'fa-solid fa-phone',
        'link' => 'tel:+251911123456',
    ],
    [
        'type' => 'address',
        'label' => 'Our Office',
        'value' => 'Bole Sub-City, Addis Ababa, Ethiopia',
        'icon' => 'fa-solid fa-location-dot',
        'link' => 'https://maps.google.com/?q=Bole+Sub-City,+Addis+Ababa,+Ethiopia',
    ],
]);
echo "✓ pages.contact.methods updated.\n";

// 6. Settings Repository Updates
$repo = app(SettingsRepository::class);
$repo->set('platform.general', array_merge($repo->get('platform.general', []), [
    'site_name' => 'Fira Link Business Solution',
    'tagline' => 'Art of Excellence!!',
]), 'general', 'array');

$repo->set('contact.details', [
    'email' => 'info@firalink.com',
    'support_email' => 'info@firalink.com',
    'phone' => '+251 911 123456',
    'support_phone' => '+251 911 123456',
    'address' => 'Bole Sub-City, Addis Ababa, Ethiopia',
    'city' => 'Addis Ababa',
    'country' => 'Ethiopia',
    'website' => 'https://fira-link.com',
], 'contact', 'array');
echo "✓ SettingsRepository platform and contact updated.\n";

// Clear Caches
Cache::flush();
echo "✓ Cache flushed successfully.\n";
echo "All company details and branding values updated successfully!\n";
