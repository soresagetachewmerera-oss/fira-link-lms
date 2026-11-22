<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sms_ethiopia' => [
        'api_url'   => env('SMS_ETHIOPIA_API_URL', 'https://smsethiopia.com/api/sms/send'),
        'api_key'   => env('SMS_ETHIOPIA_API_KEY', ''),
        'sender_id' => env('SMS_ETHIOPIA_SENDER_ID', 'FiraLink'),
        'enabled'   => env('SMS_ETHIOPIA_ENABLED', true),
        // Fixed 6-digit sign-in code for development. Only honoured when APP_ENV=local
        // (checked in OtpService::testCodeActive()); ignored in every other environment.
        'test_code' => env('AUTH_TEST_OTP_CODE'),
    ],

    'firebase' => [
        'credentials' => env('FIREBASE_CREDENTIALS', storage_path('app/firebase/firebase_credentials.json')),
    ],

    'chapa' => [
        // Off by default: the old Chapa keys were committed to GitHub. While off, no new Chapa checkout
        // starts anywhere (courses, events, tours, plans) and webhooks are refused; bank slips still work.
        // Payments already started can still be verified server to server. Turn on only after rotating the keys.
        'enabled' => (bool) env('CHAPA_ENABLED', false),
    ],

    'gemini' => [
        'enabled' => (bool) env('GEMINI_ENABLED', false),
        'api_key' => env('GEMINI_API_KEY'),
        'model'   => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],

];
