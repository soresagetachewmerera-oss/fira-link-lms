<?php

namespace App\Services;

/**
 * Smtp Configurator
 *
 * This service is responsible for configuring the SMTP settings.
 *
 * @package App\Services
 */
class SmtpConfigurator
{
    public function __construct(private SettingsRepository $settingsRepository)
    {
    }

    public function apply(): void
    {
        $credentials = $this->settingsRepository->get('smtp.credentials', []);

        if (!is_array($credentials) || empty($credentials['host'])) {
            return;
        }

        config()->set('mail.default', 'smtp');

        $this->setConfig('mail.mailers.smtp.host', $credentials['host'] ?? null);
        $this->setConfig('mail.mailers.smtp.port', $credentials['port'] ?? null);
        $this->setConfig('mail.mailers.smtp.username', $credentials['username'] ?? null);
        $this->setConfig('mail.mailers.smtp.password', $credentials['password'] ?? null);
        $this->setConfig('mail.mailers.smtp.encryption', $credentials['encryption'] ?? null);

        if (!empty($credentials['from_address'])) {
            config()->set('mail.from.address', $credentials['from_address']);
        }

        if (!empty($credentials['from_name'])) {
            config()->set('mail.from.name', $credentials['from_name']);
        }
    }

    protected function setConfig(string $key, mixed $value): void
    {
        if ($value !== null && $value !== '') {
            config()->set($key, $value);
        }
    }
}
