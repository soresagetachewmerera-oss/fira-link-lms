<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fira Link Academy - Production SEO Configuration
    |--------------------------------------------------------------------------
    */

    'brand_name' => 'Fira Link Academy',
    'legal_name' => 'Fira Link Business Solution',
    'alternate_names' => ['Fira Link', 'FiraLink Academy', 'Fira Link Business Solution'],
    'primary_color' => '#6D1A95',
    'tagline' => 'Learn Import & Export Business in Ethiopia',

    // Canonical base URL (defaults to production domain)
    'canonical_domain' => env('CANONICAL_DOMAIN', 'https://fira-link.com'),

    // Default Meta
    'default_title' => 'Fira Link Academy | Learn Import & Export Business in Ethiopia',
    'default_description' => 'Master practical import-export, customs clearance, port logistics, and global trade in Ethiopia and East Africa with Fira Link Academy. Explore programs today.',
    'default_og_image' => '/og-image.png',

    // Real Social Profiles (No placeholders)
    'social_links' => [
        'tiktok'    => 'https://www.tiktok.com/@firaol_kebede_25',
        'telegram'  => 'https://t.me/firalink',
        'linkedin'  => 'https://www.linkedin.com/company/fira-link-business-solution',
        'facebook'  => 'https://www.facebook.com/firalinkacademy',
        'youtube'   => 'https://www.youtube.com/@firalink',
    ],

    // Corporate Contact
    'contact' => [
        'address'        => 'Bole Sub-City, Addis Ababa, Ethiopia',
        'city'           => 'Addis Ababa',
        'country'        => 'Ethiopia',
        'email'          => 'info@firalink.com',
        'support_email'  => 'support@firalink.com',
        'phone'          => '+251 911 123456',
        'contact_type'   => 'customer service',
    ],

    // Verification & Analytics
    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION', ''),
    'ga_measurement_id'        => env('GA_MEASUREMENT_ID', ''),

    // Primary Navigation Elements for JSON-LD SiteNavigationElement
    'navigation' => [
        ['name' => 'Home',       'url' => '/'],
        ['name' => 'Programs',   'url' => '/programs'],
        ['name' => 'Courses',    'url' => '/courses'],
        ['name' => 'Free Tools', 'url' => '/free-tools'],
        ['name' => 'Pricing',    'url' => '/pricing'],
        ['name' => 'Events',     'url' => '/events'],
        ['name' => 'Tours',      'url' => '/tours'],
        ['name' => 'Blog',       'url' => '/blog'],
        ['name' => 'About',      'url' => '/about'],
        ['name' => 'Contact',    'url' => '/contact'],
        ['name' => 'FAQ',        'url' => '/faq'],
    ],

];
