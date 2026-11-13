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
            ['key' => 'live_class_reminder_minutes'],
            [
                'group'   => 'live_class',
                'type'    => 'integer',
                'payload' => json_encode(['data' => 30]),
                'is_encrypted' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('platform_settings')->updateOrInsert(
            ['key' => 'live_class_enabled'],
            [
                'group'   => 'live_class',
                'type'    => 'boolean',
                'payload' => json_encode(['data' => true]),
                'is_encrypted' => 0,
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
            ->where('group', 'live_class')
            ->delete();
    }
};
