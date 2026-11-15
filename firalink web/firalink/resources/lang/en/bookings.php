<?php

return [
    'free_confirmed' => 'Your place is confirmed.',
    'slip_in_review' => 'Slip received. Your booking shows "In review" until Fira Link confirms the payment with the bank.',
    'chapa_next' => 'Complete the payment with Chapa to confirm.',
    'ticket_confirmed' => 'Payment confirmed. Your ticket is ready.',
    'tour_confirmed' => 'Payment confirmed. Your places are held.',

    'purposes' => [
        'event_ticket' => 'Event ticket',
        'tour_deposit' => 'Tour deposit',
        'tour_balance' => 'Tour balance',
    ],

    'admin' => [
        'confirmed' => 'Payment confirmed. The booking is updated.',
        'rejected' => 'Payment rejected. The person is told to try again.',
    ],

    'errors' => [
        'payment_in_review' => 'A bank slip for this payment is already waiting for confirmation.',
        'slip_required' => 'Upload your bank slip.',
        'payment_method' => 'Choose Chapa or bank transfer.',
        'not_pending' => 'This payment is not waiting for confirmation.',
        'reference_used' => 'This bank reference was already used for another payment.',
        'event_ended' => 'This event has ended.',
        'seats_left' => 'Only :n seat(s) left.',
        'places_left' => 'Only :n place(s) left on this date.',
        'member_only' => 'The member ticket is for Academy members.',
        'date_not_offered' => 'Choose one of the tour dates.',
        'balance_not_due' => 'This balance cannot be paid now.',
        'failed' => 'The booking could not be saved. Try again.',
        'not_found' => 'Booking not found.',
        'verify_failed' => 'Chapa could not be reached. Try again in a moment.',
        'not_paid' => 'The payment was not completed.',
    ],

    'admin_flow' => [
        'title' => 'App details (flows)',
        'category' => 'Category',
        'cat_trade_fair' => 'Trade fair',
        'cat_training' => 'Training',
        'cat_cupping' => 'Cupping',
        'cat_networking' => 'Networking',
        'member_price' => 'Member ticket price (Academy members)',
        'service_fee' => 'Service fee per ticket',
        'is_online' => 'Online event',
        'agenda' => 'Agenda (one line each: 09:00 | Session title)',
        'refund_policy' => 'Refund policy',
        'organizer_fira_id' => 'Organizer business (Fira ID)',
        'organized_by_fira_link' => 'Organized by Fira Link',
        'host_fira_id' => 'Host farm or cooperative (Fira ID)',
        'deposit_percent' => 'Deposit now (%)',
        'balance_due_days' => 'Balance due (days before the tour)',
        'dates' => 'Tour dates (one per line, YYYY-MM-DD)',
        'safety_notes' => 'Safety: emergency contact and what to bring',
        'save' => 'Save details',
        'saved' => 'Saved.',
        'fira_id_unknown' => 'No business with this Fira ID has passed Tier 1.',
    ],
];
