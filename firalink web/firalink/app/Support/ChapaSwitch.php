<?php

namespace App\Support;

/**
 * Server switch for Chapa (CHAPA_ENABLED, default off). Every place that starts a Chapa checkout or accepts a
 * Chapa webhook asks this first and fails closed. Verifying a payment that already started stays allowed.
 */
final class ChapaSwitch
{
    public static function enabled(): bool
    {
        return (bool) config('services.chapa.enabled', false);
    }

    /** Simulated checkouts exist only for local development and the test suite, never on a server. */
    public static function simulationAllowed(): bool
    {
        return app()->environment('local', 'testing');
    }

    public static function offMessage(): string
    {
        return __('bookings.errors.chapa_off');
    }
}
