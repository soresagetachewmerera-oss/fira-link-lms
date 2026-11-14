<?php

return [
    'errors' => [
        'business_required' => 'Create or switch to a business workspace first.',
        'not_allowed' => 'Only the owner or a manager of this business can do this.',
        'buyer_role' => 'Only buyer and broker businesses can post requests.',
        'tier_required' => 'Tier 1 verification is required first. Paid plans never buy verification.',
        'sellers_only' => 'Only verified seller businesses can see and answer requests.',
        'own_request' => 'You cannot quote your own request.',
        'request_closed' => 'This request is closed.',
        'lot_required' => 'Attach one of your published lots.',
        'volume_too_high' => 'This lot has :bags bags available.',
        'contact_details' => 'Remove phone numbers, emails, links and messenger names. Keep the deal inside Fira Link.',
        'quote_limit' => 'Your plan\'s monthly quote limit is reached.',
    ],

    'blockers' => [
        'REQUEST_CLOSED' => 'This request is closed.',
        'QUOTE_NOT_OPEN' => 'This quote is no longer open.',
        'LOT_REQUIRED' => 'This quote has no lot attached, so it cannot be accepted.',
        'SELLER_NOT_VERIFIED' => 'The seller is not verified at the moment, so this quote cannot be accepted.',
        'BUYER_NOT_VERIFIED' => 'Your business needs an active Tier 1 verification to accept quotes.',
        'BELOW_MINIMUM' => 'This price is below the ECTA minimum export price, so it cannot be accepted.',
    ],

    'notifications' => [
        'quote_received_title' => 'New quote',
        'quote_received' => ':seller sent a quote for ":rfq".',
        'quote_accepted_title' => 'Quote accepted',
        'quote_accepted' => 'Your quote for ":rfq" was accepted. Open the deal room to continue.',
        'quote_not_selected_title' => 'Quote not selected',
        'quote_not_selected' => 'The buyer chose another quote for ":rfq".',
        'quote_declined_title' => 'Quote declined',
        'quote_declined' => 'The buyer declined your quote for ":rfq".',
    ],
];
