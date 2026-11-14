<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * In-app notice to a business owner about a request or quote.
 * Events: quote_received, quote_accepted, quote_not_selected, quote_declined and sample_* events. English and Amharic.
 */
class TradeUpdate extends Notification
{
    public function __construct(public string $event, public array $params = [])
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $replace = array_map(fn ($v) => (string) $v, array_intersect_key($this->params, array_flip(['rfq', 'seller', 'buyer', 'lot'])) + ['rfq' => '', 'seller' => '', 'buyer' => '', 'lot' => '']);

        return [
            'type' => 'trade',
            'event' => $this->event,
            'title' => __("trade.notifications.{$this->event}_title", [], 'en'),
            'message' => __("trade.notifications.{$this->event}", $replace, 'en'),
            'title_am' => __("trade.notifications.{$this->event}_title", [], 'am'),
            'message_am' => __("trade.notifications.{$this->event}", $replace, 'am'),
            'url' => null,
        ];
    }
}
