<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Market watch slice 5 (2026-11-28): signed-in watchlist (pinned instruments) and price alerts.
 * An alert compares the latest published price with a threshold in the instrument's stored unit and
 * fires once when the price crosses to the chosen side; it re-arms when the price goes back.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_watchlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('series_id')->constrained('market_series')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'series_id']);
        });

        Schema::create('market_price_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('series_id')->constrained('market_series')->cascadeOnDelete();
            $table->string('direction', 8); // above | below
            $table->decimal('threshold', 20, 8);
            $table->json('channels');
            $table->boolean('active')->default(true);
            $table->string('last_side', 8)->nullable(); // side of the threshold at the last check
            $table->timestamp('triggered_at')->nullable();
            $table->timestamps();
            $table->index(['series_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_price_alerts');
        Schema::dropIfExists('market_watchlist_items');
    }
};
