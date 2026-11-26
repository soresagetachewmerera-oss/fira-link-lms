<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Release 1 (audit D1): stock held for an accepted quote. Accepting takes bags off the lot's available
 * count; the hold expires if the contract is not signed in time, and is released when the deal is
 * cancelled. Additive only: no existing column changes. Existing accepted quotes get a hold when their
 * deal room is opened (or fail there if the bags are gone).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('coffee_listings')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('quotation_id')->unique()->constrained('marketplace_quotations')->cascadeOnDelete();
            $table->foreignId('deal_room_id')->nullable()->constrained('deal_rooms')->nullOnDelete();
            $table->unsignedInteger('bags');
            $table->string('status', 20)->index(); // held, committed, released
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('committed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->string('release_reason', 40)->nullable();
            $table->timestamps();
            $table->index(['listing_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
