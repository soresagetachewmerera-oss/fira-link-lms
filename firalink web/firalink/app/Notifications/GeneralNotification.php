<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $type;
    public $image;
    public $url;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $type, $image = null, $url = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->image = $image;
        $this->url = $url;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', FcmChannel::class];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'image' => $this->image,
            'url' => $this->url,
        ];
    }

    /**
     * Get the FCM push representation of the notification.
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body'  => $this->message,
            'data'  => [
                'type'  => (string) ($this->type ?? 'general'),
                'url'   => (string) ($this->url ?? ''),
                'image' => (string) ($this->image ?? ''),
            ],
        ];
    }
}
