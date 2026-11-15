<?php

return [
    // Deposit share paid at "Payment secured"; the balance is paid when documents are released.
    'deposit_percent' => (int) env('DEAL_DEPOSIT_PERCENT', 30),
];
