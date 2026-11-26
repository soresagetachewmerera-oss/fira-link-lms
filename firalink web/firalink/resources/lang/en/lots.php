<?php

return [
    'levels' => [
        'claimed' => 'Claimed',
        'documented' => 'Documented',
        'lab_backed' => 'Lab-backed',
        'inspected' => 'Inspected',
    ],

    'kinds' => [
        'photo_bags' => 'Bags with lot code',
        'photo_beans' => 'Green beans close-up',
        'photo_warehouse' => 'Warehouse view',
        'lab_report' => 'Lab or cupping report',
    ],

    'checks' => [
        'lot_code_visible' => 'The lot code is visible on the bags',
        'lab_contacted' => 'I confirmed the report with the lab that issued it',
    ],

    'reasons' => [
        'blurry_photo' => 'Photo is blurry. Retake it.',
        'lot_code_not_visible' => 'The lot code is not visible on the bags.',
        'wrong_subject' => 'The photo does not show what was asked.',
        'not_taken_in_app' => 'Take the photo inside the app.',
        'lab_not_confirmed' => 'The lab could not confirm this report.',
        'number_unreadable' => 'The report number cannot be read.',
    ],

    'statuses' => [
        'draft' => 'Draft',
        'active' => 'Published',
        'paused' => 'Paused',
        'archived' => 'Removed by Fira Link',
        'sold_out' => 'Sold out',
    ],

    'errors' => [
        'kind_unknown' => 'This evidence type is not part of a lot.',
        'not_in_app' => 'Take this photo inside the Fira Link app. Gallery photos are not accepted.',
        'location_invalid' => 'The photo location is not valid.',
        'lab_name' => 'Enter the lab name.',
        'image_processing' => 'Photos cannot be processed right now. Try again later.',
        'not_allowed' => 'Only the owner or a manager of this business can change its lots.',
        'seller_role' => 'Only businesses that sell coffee for export can add lots. Check your business activities.',
        'tier_required' => 'Your lots stay hidden until Tier 1 verification is approved.',
        'business_required' => 'Create or switch to a business workspace first.',
        'not_editable' => 'This lot was removed by Fira Link and cannot be changed.',
        'admin_activate_blocked' => 'This lot cannot be made active: the business is not a verified seller, or the lot is missing required details.',
        'total_below_taken' => ':bags bags of this lot are held for accepted quotes or already sold. The total cannot be lower.',
        'lot_reserved' => 'Bags of this lot are held for an accepted quote. Wait until that deal is signed or cancelled.',
        'export_licence_required' => 'Your lots stay hidden from buyers until Fira Link approves your ECTA exporter certificate.',
    ],

    'notifications' => [
        'approved_title' => 'Lot evidence approved',
        'approved' => ':evidence for :lot was approved.',
        'needs_changes_title' => 'Lot evidence needs changes',
        'needs_changes' => ':evidence for :lot needs changes: :reason',
        'rejected_title' => 'Lot evidence rejected',
        'rejected' => ':evidence for :lot was rejected: :reason',
        'expired_title' => 'Lot evidence expired',
        'expired' => ':evidence for :lot expired. Take new photos to keep its level.',
        'expiring_title' => 'Renew lot evidence',
        'expiring' => ':evidence for :lot expires in :days days. Take new photos to keep its level.',
    ],

    'admin' => [
        'tab_business' => 'Business documents',
        'tab_lots' => 'Lot evidence',
        'lot' => 'Lot',
        'lot_code' => 'Lot code',
        'evidence' => 'Evidence',
        'level' => 'Level',
        'captured' => 'Taken in the app',
        'location' => 'Location',
        'no_location' => 'No location sent',
        'open_lot' => 'Lot page',
        'queue_empty' => 'No lot evidence is waiting for review.',
        'lab_name' => 'Lab',
        'report_number' => 'Report number',
    ],
];
