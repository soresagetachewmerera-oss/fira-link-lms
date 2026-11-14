<?php

/*
|--------------------------------------------------------------------------
| Verification tiers (slice 3)
|--------------------------------------------------------------------------
| Document requirements are still being confirmed with ECTA, so they live here, not in code.
| Add document types freely; never rename a key that is already stored (verification_documents.
| document_type). Labels are translated in lang/{en,am}/verification.php and in the app by key.
|
| Tier 1  legal identity      all Tier 1 documents for the business's entity type, approved, unexpired
| Tier 2  export operations   Tier 1 + all Tier 2 documents for the business role
| Tier 3  vetted by Fira Link Tier 2 + an active Tier 3 grant (checklist below)
| The tier is computed by App\Services\Verification\TierService. Clients and plans never set it.
*/

return [

    'document_types' => [
        // Tier 1: legal identity
        'trade_license' => ['tier' => 1, 'expires' => true, 'number' => true],
        'cooperative_certificate' => ['tier' => 1, 'expires' => true, 'number' => true],
        'association_registration' => ['tier' => 1, 'expires' => true, 'number' => true],
        'certificate_of_incorporation' => ['tier' => 1, 'expires' => false, 'number' => true],
        'tin_certificate' => ['tier' => 1, 'expires' => false, 'number' => true],
        // The owner ID number is also stored as a keyed hash (ID_HASH_KEY) to find one owner behind several businesses.
        'owner_id' => ['tier' => 1, 'expires' => true, 'number' => true, 'owner_id_hash' => true],
        // No account number is stored; the reviewer confirms the account name matches the business.
        'bank_letter' => ['tier' => 1, 'expires' => false, 'number' => false, 'checks' => ['account_name_matches']],

        // Tier 2: export operations
        'ecta_exporter_certificate' => ['tier' => 2, 'expires' => true, 'number' => true],
        'certified_lab' => ['tier' => 2, 'expires' => true, 'number' => true, 'fields' => ['lab_name']],
        'taster_certificate' => ['tier' => 2, 'expires' => true, 'number' => true, 'fields' => ['taster_name']],
        'warehouse' => ['tier' => 2, 'expires' => false, 'number' => false, 'fields' => ['warehouse_address'], 'camera_only' => true, 'min_pages' => 2],
        'supplier_operating_license' => ['tier' => 2, 'expires' => true, 'number' => true],
    ],

    'tiers' => [
        1 => [
            'being_confirmed' => false,
            // Default Tier 1 set; entity types below replace the business licence document.
            'documents' => ['trade_license', 'tin_certificate', 'owner_id', 'bank_letter'],
            'licence_by_entity_type' => [
                'cooperative' => 'cooperative_certificate',
                'cooperative_union' => 'cooperative_certificate',
                'association' => 'association_registration',
                'foreign_company_branch' => 'certificate_of_incorporation',
            ],
        ],
        2 => [
            'being_confirmed' => true,
            // Buyers and brokers have Tier 1 only for now.
            'documents_by_role' => [
                'exporter' => ['ecta_exporter_certificate', 'certified_lab', 'taster_certificate', 'warehouse'],
                'supplier' => ['supplier_operating_license'],
                'broker' => [],
                'buyer' => [],
            ],
        ],
        3 => [
            'valid_months' => (int) env('VERIFICATION_TIER3_VALID_MONTHS', 12),
            // Every item must be confirmed before the grant works; answers go to the audit log.
            'checklist' => ['documents_match', 'call_made', 'no_open_complaints', 'past_deal_or_inspection'],
        ],
    ],

    'upload' => [
        'max_pages' => 10,
    ],

    // Saved reasons for "ask for changes" and "reject" (translated in lang/*/verification.php).
    'reasons' => ['blurry_photo', 'edges_cut_off', 'name_mismatch', 'expired', 'wrong_document', 'number_unreadable'],

    'expected_review_business_days' => (int) env('VERIFICATION_REVIEW_DAYS', 3),
    'reminder_days_before_expiry' => [30, 7],

    // Dedicated secret for the owner-ID keyed hash. Never derived from APP_KEY, never committed.
    // Without it, owner ID uploads are refused (fail closed).
    'id_hash_key' => env('ID_HASH_KEY'),

    'admin_email' => env('VERIFICATION_ADMIN_EMAIL'),
    'daily_summary_time' => env('VERIFICATION_SUMMARY_TIME', '08:00'),
    'timezone' => 'Africa/Addis_Ababa',

    // Step-up window for Tier 2 and Tier 3 approvals.
    'step_up_minutes' => 10,

    // Public seller lookup (by Fira ID only).
    'public_lookup_per_minute' => (int) env('VERIFICATION_PUBLIC_LOOKUP_PER_MINUTE', 30),

    /*
    | Lot evidence levels (slice 4). The level is computed by App\Services\Lots\LotLevelService
    | from evidence a reviewer approved; nobody picks it. Inspected is defined but not reachable
    | until there is an inspector flow.
    */
    'lots' => [
        'levels' => ['claimed', 'documented', 'lab_backed', 'inspected'],
        'photo_kinds' => ['photo_bags', 'photo_beans', 'photo_warehouse'],
        // Documented: every photo kind approved. Lab-backed: Documented plus an approved lab report.
        'lab_report_checks' => ['lab_contacted'],
        'photo_kind_checks' => ['photo_bags' => ['lot_code_visible']],
        'documented_valid_days' => (int) env('LOT_EVIDENCE_VALID_DAYS', 30),
        // In-app photos only: the EXIF capture time must exist and be this recent when uploaded.
        'photo_max_age_hours' => 24,
        'photo_mimes' => ['image/jpeg', 'image/png'],
        'reminder_days_before_expiry' => [7],
        'reasons' => ['blurry_photo', 'lot_code_not_visible', 'wrong_subject', 'not_taken_in_app', 'lab_not_confirmed', 'number_unreadable'],
    ],
];
