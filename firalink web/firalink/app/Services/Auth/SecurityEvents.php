<?php

namespace App\Services\Auth;

use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\SecurityNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

/**
 * One place for sensitive account changes: an audit-log entry (who, what, when, IP; never secrets)
 * and a notice to the person (in the app, push, and email when available).
 * Delivery failures (mail server down, no push token) never undo the change.
 */
class SecurityEvents
{
    /**
     * @param string $event one of SecurityNotice::EVENTS
     * @param array $state safe facts for the audit log (masked phone, email domain, revoked token count)
     * @param bool $audit false when the caller already wrote its own audit entry for this change
     * @param string|null $alsoMail an extra address to tell (the OLD address after an email change)
     */
    public static function record(User $user, string $event, Request $request, array $state = [], bool $audit = true, ?string $alsoMail = null): void
    {
        if ($audit) {
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'security.' . $event,
                'auditable_type' => User::class,
                'auditable_id' => $user->id,
                'before_state' => null,
                'after_state' => $state ?: null,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]);
        }

        $params = array_intersect_key($state, array_flip(['phone_masked', 'email_domain'])) + ['ip' => $request->ip()];

        try {
            $user->notify(new SecurityNotice($event, $params));
        } catch (\Throwable $e) {
            report($e);
        }

        if ($alsoMail) {
            try {
                Notification::route('mail', $alsoMail)->notify(new SecurityNotice($event, $params));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
