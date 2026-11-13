<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD'],

    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        'https://app.fira-link.com',
        'http://app.fira-link.com',
        'https://fira-link.com',
        'https://www.fira-link.com',
        'https://miniapp.fira-link.com',
        'http://miniapp.fira-link.com',
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],

    'allowed_origins_patterns' => [
        '#^https?://([a-zA-Z0-9-]+\.)?fira-link\.com$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Length', 'Content-Range'],

    'max_age' => 86400,

    'supports_credentials' => true,

];
