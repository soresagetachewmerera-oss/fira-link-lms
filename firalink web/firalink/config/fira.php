<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fira Link Academy Introduction Video Configuration
    |--------------------------------------------------------------------------
    |
    | Centralized configuration for the 2-minute introductory platform video.
    | You can customize the source URL (local asset or external CDN/VOD),
    | poster image, title, and descriptive copy here.
    |
    */

    'intro_video' => [
        'url'          => env('INTRO_VIDEO_URL', '/videos/fira-link-prime.mp4'),
        'poster'       => env('INTRO_VIDEO_POSTER', '/assets/front/img/video/fira_video_poster.jpg'),
        'duration'     => '2:00',
        'duration_iso' => 'PT1M20S',
        'badge'        => '2-MINUTE PLATFORM OVERVIEW',
        'title'        => 'Discover What You Can Do With Fira Link',
        'subtitle'     => 'See how Ethiopia\'s leading trade academy helps you master supplier sourcing, customs clearance, port logistics, and scale your import & export ventures.',
        'cta_label'    => 'Watch 2-Minute Introduction',
    ],

    /*
    |--------------------------------------------------------------------------
    | TikTok Learner Video Testimonials
    |--------------------------------------------------------------------------
    |
    | Real video reviews and student testimonials from @firaol_kebede_25 on TikTok.
    | Videos are embedded with optimized lazy-loading and accessible fallback controls.
    |
    */

    'learner_testimonials' => [
        [
            'id'        => '7599195800530373895',
            'name'      => 'Firaol Kebede',
            'role'      => 'Trade Entrepreneur',
            'course'    => 'Coffee Export Program',
            'thumbnail' => '/assets/front/img/testimonials/testimonial-1.jpg',
            'videoUrl'  => 'https://www.tiktok.com/@firaol_kebede_25/video/7599195800530373895',
            'embedUrl'  => 'https://www.tiktok.com/player/v1/7599195800530373895?autoplay=1',
            'duration'  => '1:24',
        ],
        [
            'id'        => '7599192497197157640',
            'name'      => 'Firaol Kebede',
            'role'      => 'Logistics Director',
            'course'    => 'Import Mastery Program',
            'thumbnail' => '/assets/front/img/testimonials/testimonial-2.jpg',
            'videoUrl'  => 'https://www.tiktok.com/@firaol_kebede_25/video/7599192497197157640',
            'embedUrl'  => 'https://www.tiktok.com/player/v1/7599192497197157640?autoplay=1',
            'duration'  => '1:48',
        ],
        [
            'id'        => '7570314633010105611',
            'name'      => 'Firaol Kebede',
            'role'      => 'Commercial Trader',
            'course'    => 'Customs & Logistics Operations',
            'thumbnail' => '/assets/front/img/testimonials/testimonial-3.jpg',
            'videoUrl'  => 'https://www.tiktok.com/@firaol_kebede_25/video/7570314633010105611',
            'embedUrl'  => 'https://www.tiktok.com/player/v1/7570314633010105611?autoplay=1',
            'duration'  => '2:05',
        ],
        [
            'id'        => '7599967022474185992',
            'name'      => 'Firaol Kebede',
            'role'      => 'Operations Lead',
            'course'    => 'Practical Port Corridors Training',
            'thumbnail' => '/assets/front/img/testimonials/testimonial-4.jpg',
            'videoUrl'  => 'https://www.tiktok.com/@firaol_kebede_25/video/7599967022474185992',
            'embedUrl'  => 'https://www.tiktok.com/player/v1/7599967022474185992?autoplay=1',
            'duration'  => '1:15',
        ],
        [
            'id'        => '7590378612356959499',
            'name'      => 'Firaol Kebede',
            'role'      => 'Agro-Commodity Exporter',
            'course'    => 'Export Strategy & Supplier Sourcing',
            'thumbnail' => '/assets/front/img/testimonials/testimonial-5.jpg',
            'videoUrl'  => 'https://www.tiktok.com/@firaol_kebede_25/video/7590378612356959499',
            'embedUrl'  => 'https://www.tiktok.com/player/v1/7590378612356959499?autoplay=1',
            'duration'  => '1:50',
        ],
        [
            'id'        => '7588756663088205068',
            'name'      => 'Firaol Kebede',
            'role'      => 'Trade Consultant',
            'course'    => 'Global Contracts & Community',
            'thumbnail' => '/assets/front/img/testimonials/testimonial-6.jpg',
            'videoUrl'  => 'https://www.tiktok.com/@firaol_kebede_25/video/7588756663088205068',
            'embedUrl'  => 'https://www.tiktok.com/player/v1/7588756663088205068?autoplay=1',
            'duration'  => '1:32',
        ],
    ],
    'tiktok_testimonials' => [], // Alias maintained for backward compatibility

    /*
    |--------------------------------------------------------------------------
    | Waitlist Configuration
    |--------------------------------------------------------------------------
    |
    | Dynamic data source for waitlist entrepreneur count.
    | Can be fed from environment variable, database, or external webhook/API.
    |
    */

    'waitlist' => [
        'count'   => env('WAITLIST_COUNT', null),
        'api_url' => env('WAITLIST_API_URL', null),
    ],

];
