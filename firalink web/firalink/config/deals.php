<?php

return [
    // Deposit share paid at "Payment secured"; the balance is paid when documents are released.
    'deposit_percent' => (int) env('DEAL_DEPOSIT_PERCENT', 30),

    // Days an accepted quote holds the lot's bags while the contract is signed. After that the hold is
    // released and an unsigned deal room is cancelled (docs/product/07_coffee_b2b_decisions.md, Q6b).
    'reservation_hold_days' => (int) env('DEAL_RESERVATION_HOLD_DAYS', 7),
];
