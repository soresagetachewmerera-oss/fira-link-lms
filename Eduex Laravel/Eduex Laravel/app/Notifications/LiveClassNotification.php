<?php

namespace App\Notifications;

use App\Models\LiveClass;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * LiveClassNotification
 *
 * Delivers live class updates (scheduled / reminder / starting_now / cancelled)
 * to enrolled students via database and email channels.
 *
 * @package App\Notifications
 */
class LiveClassNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly LiveClass $liveClass,
        public readonly string $notificationType, // scheduled|reminder|starting_now|cancelled
    ) {}

    /**
     * Delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Mail representation.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $lc   = $this->liveClass;
        $time = $lc->scheduled_at->format('D, M j Y \a\t g:i A');

        return match ($this->notificationType) {
            'scheduled' => (new MailMessage())
                ->subject("New Live Class Scheduled: {$lc->title}")
                ->greeting("Hello {$notifiable->name}!")
                ->line("A new live class has been scheduled for your course **{$lc->course->title}**.")
                ->line("**{$lc->title}**")
                ->line("{$time} · ⏱ {$lc->duration_minutes} minutes")
                ->action('Join Class', $lc->join_url)
                ->line('See you there!'),

            'reminder' => (new MailMessage())
                ->subject(" Live Class Starting Soon: {$lc->title}")
                ->greeting("Hello {$notifiable->name}!")
                ->line("Your live class is starting soon!")
                ->line("**{$lc->title}** · {$lc->course->title}")
                ->line("{$time}")
                ->action('Join Now', $lc->join_url),

            'starting_now' => (new MailMessage())
                ->subject(" Live Now: {$lc->title}")
                ->greeting("Hello {$notifiable->name}!")
                ->line("Your live class **{$lc->title}** has started!")
                ->action('Join Now', $lc->join_url),

            'cancelled' => (new MailMessage())
                ->subject("Live Class Cancelled: {$lc->title}")
                ->greeting("Hello {$notifiable->name}!")
                ->line("Unfortunately, the live class **{$lc->title}** scheduled for {$time} has been cancelled.")
                ->line('Please check back later for rescheduled sessions.'),

            default => (new MailMessage())
                ->subject("Live Class Update: {$lc->title}")
                ->line("There is an update to your live class **{$lc->title}**."),
        };
    }

    /**
     * Database representation.
     */
    public function toArray(object $notifiable): array
    {
        $lc = $this->liveClass;

        $messages = [
            'scheduled'    => "New live class scheduled: {$lc->title} on {$lc->scheduled_at->format('M j, Y \a\t g:i A')}",
            'reminder'     => "Live class starting soon: {$lc->title}",
            'starting_now' => "Live class is starting now: {$lc->title}",
            'cancelled'    => "Live class cancelled: {$lc->title}",
        ];

        return [
            'title'          => $lc->title,
            'message'        => $messages[$this->notificationType] ?? "Update for live class: {$lc->title}",
            'type'           => 'live_class',
            'notification_type' => $this->notificationType,
            'live_class_id'  => $lc->id,
            'course_id'      => $lc->course_id,
            'join_url'       => $lc->join_url,
            'scheduled_at'   => $lc->scheduled_at->toIso8601String(),
            'image'          => null,
            'url'            => $lc->join_url,
        ];
    }
}
