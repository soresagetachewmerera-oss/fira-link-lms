<?php

namespace App\Console\Commands\Qa;

trait GuardsQa
{
    /** False (and an error printed) when QA tools must not run here. */
    protected function qaAllowed(): bool
    {
        if (app()->environment('production')) {
            $this->error('QA tools never run in production.');

            return false;
        }
        if (!app()->environment(['local', 'testing']) && !config('qa.enabled')) {
            $this->error('QA tools are off on this server. Set QA_TOOLS_ENABLED=true (never in production).');

            return false;
        }

        return true;
    }

    protected function qaDomain(): string
    {
        return (string) config('qa.email_domain', 'qa.firalink.test');
    }

    protected function isQaEmail(?string $email): bool
    {
        return $email !== null && str_ends_with(strtolower($email), '@' . $this->qaDomain());
    }
}
