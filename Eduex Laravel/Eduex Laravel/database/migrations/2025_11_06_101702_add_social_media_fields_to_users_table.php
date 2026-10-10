<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('linkedin')->nullable()->after('bio');
            $table->string('twitter')->nullable()->after('linkedin');
            $table->string('facebook')->nullable()->after('twitter');
            $table->string('youtube')->nullable()->after('facebook');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['linkedin', 'twitter', 'facebook', 'youtube']);
        });
    }
};
