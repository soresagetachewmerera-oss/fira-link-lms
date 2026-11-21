<?php

namespace App\Channels;

use App\Services\FcmService;
use Illuminate\Notifications\Notification;

class FcmChannel
{
    protected FcmService $fcmService;

    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Send the given notification via Firebase Cloud Messaging.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return array|null the FCM result (success/failure/skipped counts), so the delivery log can
     *                    record what really happened; null when there was nothing to send
     */
    public function send($notifiable, Notification $notification): ?array
    {
        if (!method_exists($notifiable, 'deviceTokens')) {
            return null;
        }

        $payload = [];
        if (method_exists($notification, 'toFcm')) {
            $payload = $notification->toFcm($notifiable);
        } elseif (method_exists($notification, 'toArray')) {
            $array = $notification->toArray($notifiable);
            $payload = [
                'title' => $array['title'] ?? config('app.name', 'Fira Link LMS'),
                'body'  => $array['message'] ?? $array['body'] ?? 'You have a new update.',
                'data'  => $array,
            ];
        }

        if (empty($payload)) {
            return null;
        }

        $title = $payload['title'] ?? config('app.name', 'Fira Link LMS');
        $body  = $payload['body'] ?? $payload['message'] ?? '';
        $data  = $payload['data'] ?? [];

        return $this->fcmService->sendToUser($notifiable, $title, $body, $data);
    }
}

