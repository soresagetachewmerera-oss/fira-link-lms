<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('platform_settings')->updateOrInsert(
            [
                'group' => 'general',
                'key' => 'system_version'
            ],
            [
                'type' => 'string',
                'payload' => json_encode('1.0.0'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('platform_settings')
            ->where('group', 'general')
            ->where('key', 'system_version')
            ->delete();
    }
};
