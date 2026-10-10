<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VersionUpdateSeeder extends Seeder
{
    // -----------------------------
    // SET NEW VERSION HERE
    // -----------------------------
    public const NEW_VERSION = '2.0.0';
    // -----------------------------

    /**
     * Run the database seeds.
     * Update the $version variable before running this seeder.
     */
    public function run(): void
    {
        $newVersion = self::NEW_VERSION;

        // Update the system_version in platform_settings
        DB::table('platform_settings')->updateOrInsert(
            [
                'group' => 'general',
                'key' => 'system_version'
            ],
            [
                'type' => 'string',
                'payload' => json_encode($newVersion),
                'updated_at' => now(),
            ]
        );

        $this->command->info("System version updated to: {$newVersion}");
    }
}
