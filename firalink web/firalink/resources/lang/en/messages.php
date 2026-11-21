<?php

/*
 * Catalogue message texts (messaging 2026-11-21). One block per NotificationType key:
 * messages.<key>.<part>, part = subject, preheader, eyebrow, heading, body, cta, notice, help,
 * push_title, push_body, sms. Each group (A to K) is added in its own slice, in English and Amharic.
 *
 * Rules: subjects under 50 characters, no codes or amounts in subjects or previews, no exclamation
 * marks, no hype. Push and in-app texts are safe on a lock screen: no amounts, names, reasons or codes.
 * Badge words ("Verified", tier) never appear here as a fact: the server decides them.
 */
return [
    'fallback' => [
        'title' => 'Fira Link',
        'body' => 'You have an update. Open Fira Link to see it.',
    ],

    // Admin preview page (admins only)
    'preview' => [
        'title' => 'Message templates',
        'intro' => 'Every message Fira Link sends, with sample data. Nothing is sent from this page.',
        'group' => 'Group :group',
        'type' => 'Message',
        'class' => 'Class',
        'channels' => 'Channels',
        'open' => 'Preview',
        'not_written' => 'not written yet',
        'text' => 'Plain text',
        'push' => 'Push',
    ],

    'layout' => [
        'code_label' => 'Your code',
        'button_fallback' => 'If the button does not work, copy this link into your browser:',
        'valid_until' => 'Valid until',
        'time_addis' => ':time, :date (Addis Ababa time)',
        'help' => 'Questions? Contact Fira Link support from our website.',
        'why_account' => 'You got this email because you have a Fira Link account.',
        'why_updates' => 'You got this email because you asked for updates from Fira Link.',
        'why_marketing' => 'You got this email because you subscribed to news from Fira Link.',
        'why_address' => 'You got this email because this address was given for a Fira Link account.',
        'never_ask' => 'Fira Link will never ask for your password or a code by email, phone or chat.',
        'support' => 'Support',
        'language_am' => 'ፊራ ሊንክ በአማርኛ',
        'language_en' => 'Fira Link in English',
        'preferences' => 'Email settings',
    ],

    'security' => [
        'otp_sign_in' => [
            'subject' => 'Your Fira Link sign-in code',
            'preheader' => 'Use this code to finish signing in. It expires in :minutes minutes.',
            'eyebrow' => 'Sign in',
            'heading' => 'Your sign-in code',
            'body' => 'Enter this code in Fira Link to finish signing in. It works once and expires in :minutes minutes.',
            'notice' => 'Never share this code. Fira Link staff will never ask for it. If you did not try to sign in, ignore this email; your account stays safe.',
        ],
        'otp_sign_up' => [
            'subject' => 'Confirm your email for Fira Link',
            'preheader' => 'Use this code to finish creating your account.',
            'eyebrow' => 'Create your account',
            'heading' => 'Confirm your email address',
            'body' => 'Enter this code in Fira Link to confirm this email address and finish creating your account. It expires in :minutes minutes.',
            'notice' => 'Never share this code. If you did not sign up for Fira Link, ignore this email.',
        ],
        'otp_password_reset' => [
            'subject' => 'Reset your Fira Link password',
            'preheader' => 'Use this code to choose a new password.',
            'eyebrow' => 'Password reset',
            'heading' => 'Your password reset code',
            'body' => 'Someone asked to reset the password of your Fira Link account. Enter this code to choose a new password. It expires in :minutes minutes.',
            'notice' => 'If this was not you, do not use the code. Your password stays the same. Never share this code with anyone.',
        ],
        'otp_step_up' => [
            'subject' => 'Confirm it is you on Fira Link',
            'preheader' => 'Use this code to confirm a sensitive change.',
            'eyebrow' => 'Security check',
            'heading' => 'Confirm it is you',
            'body' => 'You are about to make a sensitive change in Fira Link, such as bank details or a password. Enter this code to confirm it is you. It expires in :minutes minutes.',
            'notice' => 'If you are not making a change right now, do not use the code, and change your password.',
        ],
        'otp_contact_change' => [
            'subject' => 'Confirm your new email for Fira Link',
            'preheader' => 'Use this code to confirm your new contact details.',
            'eyebrow' => 'Contact details',
            'heading' => 'Confirm your new email address',
            'body' => 'Enter this code in Fira Link to confirm this email address for your account. It expires in :minutes minutes.',
            'notice' => 'If you did not ask for this change, ignore this email. Nothing changes without the code.',
        ],
        'password_reset_link' => [
            'subject' => 'Reset your Fira Link password',
            'preheader' => 'Choose a new password with the link inside. It expires in :minutes minutes.',
            'eyebrow' => 'Password reset',
            'heading' => 'Choose a new password',
            'body' => 'Someone asked to reset the password of your Fira Link account on the website. Use the button to choose a new password. The link works once and expires in :minutes minutes.',
            'cta' => 'Choose a new password',
            'notice' => 'If this was not you, ignore this email. Your password stays the same.',
        ],
    ],

    'account' => [
        'welcome' => [
            'subject' => 'Welcome to Fira Link',
            'preheader' => 'Your account is ready. Here is where to start.',
            'eyebrow' => 'Welcome',
            'heading' => 'Your Fira Link account is ready',
            'body' => "Hello :name, your account is ready.\n\nOn Fira Link you can learn practical import and export skills, use free trade tools, and, once your business is reviewed, trade coffee with buyers and sellers.",
            'cta' => 'Open Fira Link',
            'push_title' => 'Welcome to Fira Link',
            'push_body' => 'Your account is ready. Explore courses and trade tools.',
        ],
    ],
];
