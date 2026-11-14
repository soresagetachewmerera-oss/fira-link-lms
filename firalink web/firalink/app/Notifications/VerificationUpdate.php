<?php

namespace App\Notifications;

use App\Models\Organization;
use App\Models\VerificationDocument;
use Illuminate\Notifications\Notification;

/**
 * In-app notice to a business owner about a verification change.
 * Events: in_review, approved, needs_changes, rejected, expired, expiring, tier_changed.
 * Stored with English and Amharic text plus the event key, so the app can show either.
 * Never contains document numbers or files.
 */
class VerificationUpdate extends Notification
{
    public function __construct(
        public string $event,
        public Organization $organization,
        public ?VerificationDocument $document = null,
        public array $params = [],
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    private function texts(string $locale): array
    {
        $replace = [
            'business' => $this->organization->name,
            'document' => $this->document ? __('verification.doc_types.' . $this->document->document_type, [], $locale) : '',
            'reason' => $this->params['reason'] ?? '',
            'days' => $this->params['days'] ?? config('verification.expected_review_business_days'),
            'tier' => $this->params['tier'] ?? '',
        ];

        $key = $this->event;
        $messageKey = $key === 'tier_changed' && (int) ($this->params['tier'] ?? 0) === 0 ? 'tier_lost' : $key;

        return [
            'title' => __("verification.notifications.{$key}_title", [], $locale),
            'message' => __("verification.notifications.{$messageKey}", $replace, $locale),
        ];
    }

    public function toArray(object $notifiable): array
    {
        $en = $this->texts('en');
        $am = $this->texts('am');

        return [
            'type' => 'verification',
            'event' => $this->event,
            'title' => $en['title'],
            'message' => $en['message'],
            'title_am' => $am['title'],
            'message_am' => $am['message'],
            'organization_id' => $this->organization->id,
            'document_type' => $this->document?->document_type,
            'params' => array_intersect_key($this->params, array_flip(['days', 'tier', 'previous_tier', 'reason_code'])),
            'url' => null,
        ];
    }
}
