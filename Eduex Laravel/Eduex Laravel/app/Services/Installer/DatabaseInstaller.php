<?php

namespace App\Services\Installer;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class DatabaseInstaller
{
    public function run(): array
    {
        $output = new BufferedOutput;

        try {
            Artisan::call('migrate', ['--force' => true], $output);
        } catch (Exception $exception) {
            return $this->failure('Database migration failed', $exception, $output);
        }

        try {
            Artisan::call('db:seed', ['--force' => true], $output);
        } catch (Exception $exception) {
            return $this->failure('Database seeding failed', $exception, $output);
        }

        foreach (config('setup.final_commands', []) as $command) {
            $name = $command['command'];
            $options = $command['options'] ?? [];

            try {
                Artisan::call($name, $options, $output);
            } catch (Exception $exception) {
                return $this->failure("Artisan command [{$name}] failed", $exception, $output);
            }
        }

        return [
            'status' => 'success',
            'output' => $output->fetch(),
        ];
    }

    protected function failure(string $message, Exception $exception, BufferedOutput $output): array
    {
        return [
            'status' => 'error',
            'message' => $message.' : '.$exception->getMessage(),
            'output' => $output->fetch(),
        ];
    }
}

