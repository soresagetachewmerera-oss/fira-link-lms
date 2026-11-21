<?php

namespace App\Console\Commands;

use App\Notifications\Catalog\NotificationType;
use App\Services\Notifications\MessagePreview;
use Illuminate\Console\Command;

/**
 * php artisan messages:preview security.otp_sign_in --locale=am --channel=mail --out=storage/app/preview.html
 * Renders a catalogue message with sample data (messaging slice 2). Sends nothing.
 */
class PreviewMessage extends Command
{
    protected $signature = 'messages:preview {type? : catalogue key, e.g. security.otp_sign_in (empty: list all)}
        {--locale=en : en or am}
        {--channel=mail : mail, text, sms, push or inapp}
        {--out= : write the result to this file instead of the screen}';

    protected $description = 'Render a message template with sample data (email HTML or text, SMS with segment count, push, in-app). Sends nothing.';

    public function handle(MessagePreview $preview): int
    {
        if (!$this->argument('type')) {
            $rows = [];
            foreach (NotificationType::cases() as $t) {
                $ready = array_keys(array_filter($preview->templates($t)));
                $rows[] = [$t->definition()->group, $t->value, $t->messageClass()->value, implode(', ', $ready) ?: '-'];
            }
            $this->table(['Group', 'Type', 'Class', 'Written'], $rows);

            return self::SUCCESS;
        }

        $type = NotificationType::tryFrom((string) $this->argument('type'));
        if (!$type) {
            $this->error('Unknown type. Run without arguments to list them.');

            return self::FAILURE;
        }
        $locale = in_array($this->option('locale'), ['en', 'am'], true) ? $this->option('locale') : 'en';

        $output = match ($this->option('channel')) {
            'mail' => $preview->mailHtml($type, $locale),
            'text' => $preview->mailText($type, $locale),
            'sms' => json_encode($preview->sms($type, $locale), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'push' => json_encode($preview->push($type, $locale), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'inapp' => json_encode($preview->inApp($type), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => null,
        };
        if ($output === null) {
            $this->error('Channel must be mail, text, sms, push or inapp.');

            return self::FAILURE;
        }

        if ($out = $this->option('out')) {
            file_put_contents($out, $output);
            $this->info("Written to {$out}");
        } else {
            $this->line($output);
        }

        return self::SUCCESS;
    }
}
