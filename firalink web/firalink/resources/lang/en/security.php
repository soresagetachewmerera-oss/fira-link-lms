<?php

return [
    'notice' => [
        'password_changed_title' => 'Your password was changed',
        'password_changed' => 'The password of your Fira Link account was changed on :when (from :ip). Other devices were signed out.',
        'email_changed_title' => 'Your email address was changed',
        'email_changed' => 'The email address of your Fira Link account was changed on :when to an address at :domain.',
        'phone_changed_title' => 'Your phone number was changed',
        'phone_changed' => 'The phone number of your Fira Link account was changed on :when to :phone.',
        'deletion_requested_title' => 'Account deletion requested',
        'deletion_requested' => 'A request to delete your Fira Link account was made on :when. An admin reviews it before anything is removed.',
        'not_you' => 'If this was not you, reset your password now and contact Fira Link support.',
    ],

    'errors' => [
        'phone_change_needs_code' => 'Change your phone number with a code sent to the new number.',
        'phone_unavailable' => 'This phone number cannot be used. Check it or use another number.',
    ],

    // Staff "confirm it is you" page (admin and reviewer actions that change money, settings or users).
    'confirm' => [
        'title' => 'Confirm it is you',
        'intro' => 'This action needs a fresh confirmation. Enter your authenticator code or your password. It stays valid for :minutes minutes.',
        'code' => 'Authenticator code',
        'password' => 'Or your password',
        'submit' => 'Confirm',
        'invalid' => 'That code or password is not correct.',
        'needed' => 'For your security, confirm it is you, then repeat the action within :minutes minutes.',
        'confirmed' => 'Confirmed. Repeat the action now.',
    ],

    'bookings' => [
        'book_in_app' => 'Book this tour in the Fira Link app. Booking on the website is not available.',
    ],
];
