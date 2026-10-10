<?php
/**
 * @file create_community_votes_table.php
 * @description Migration for community_votes table
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('community_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('votable'); // This adds votable_id and votable_type
            $table->tinyInteger('vote'); // 1 or -1
            $table->timestamps();

            $table->unique(['user_id', 'votable_id', 'votable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_votes');
    }
};
