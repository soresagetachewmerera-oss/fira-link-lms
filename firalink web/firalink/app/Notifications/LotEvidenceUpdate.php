<?php

namespace App\Notifications;

use App\Models\CoffeeListing;
use App\Models\LotEvidence;
use Illuminate\Notifications\Notification;

/**
 * In-app notice to the business owner about lot evidence.
 * Events: approved, needs_changes, rejected, expired, expiring. English and Amharic text.
 */
class LotEvidenceUpdate extends Notification
{
    public function __construct(
        public string $event,
        public CoffeeListing $lot,
        public LotEvidence $evidence,
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
            'lot' => $this->lot->title,
            'evidence' => __('lots.kinds.' . $this->evidence->kind, [], $locale),
            'reason' => $this->params['reason'] ?? '',
            'days' => $this->params['days'] ?? '',
        ];

        return [
            'title' => __("lots.notifications.{$this->event}_title", [], $locale),
            'message' => __("lots.notifications.{$this->event}", $replace, $locale),
        ];
    }

    public function toArray(object $notifiable): array
    {
        $en = $this->texts('en');
        $am = $this->texts('am');

        return [
            'type' => 'lot_evidence',
            'event' => $this->event,
            'title' => $en['title'],
            'message' => $en['message'],
            'title_am' => $am['title'],
            'message_am' => $am['message'],
            'organization_id' => $this->lot->organization_id,
            'listing_id' => $this->lot->id,
            'kind' => $this->evidence->kind,
            'params' => array_intersect_key($this->params, array_flip(['days', 'reason_code'])),
            'url' => null,
        ];
    }
}
