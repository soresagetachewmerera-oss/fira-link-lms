<?php

namespace App\Services;

use App\Models\User;
use App\Services\SettingsRepository;

/**
 * Notification Preference Service
 *
 * This service is responsible for managing notification preferences for users.
 *
 * @package App\Services
 */
class NotificationPreferenceService
{
    public function __construct(private SettingsRepository $settingsRepository)
    {
    }

    public function studentDefaults(): array
    {
        return [
            'email_notifications' => true,
            'course_updates' => true,
            'review_notifications' => true,
            'course_reminders' => true,
            'progress_reports' => true,
            'marketing_emails' => $this->marketingOptInDefault(),
        ];
    }

    public function instructorDefaults(): array
    {
        return [
            'email_notifications' => true,
            'course_updates' => true,
            'review_notifications' => true,
            'marketing_emails' => $this->marketingOptInDefault(),
        ];
    }

    public function defaultsForUser(User $user): array
    {
        return $user->isInstructor()
            ? $this->instructorDefaults()
            : $this->studentDefaults();
    }

    public function mergeWithDefaults(array $existing, string $role): array
    {
        $defaults = $role === 'instructor'
            ? $this->instructorDefaults()
            : $this->studentDefaults();

        return array_merge($defaults, $existing);
    }

    public function mergeForUser(User $user, array $existing = [], array $updates = []): array
    {
        $defaults = $this->defaultsForUser($user);

        return array_merge($defaults, $existing, $updates);
    }

    public function prefers(User $user, string $key, bool $fallback = true): bool
    {
        $preferences = $user->notificationSettings?->preferences ?? [];
        $merged = $this->mergeForUser($user, $preferences);

        return (bool) ($merged[$key] ?? $fallback);
    }

    protected function marketingOptInDefault(): bool
    {
        $preferences = $this->settingsRepository->get('email.preferences', []);

        return (bool) ($preferences['marketing_opt_in_default'] ?? false);
    }
}
