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
        if (!Schema::hasTable('coffee_market_snapshots')) {
            Schema::create('coffee_market_snapshots', function (Blueprint $table) {
                $table->id();
                $table->date('observed_at')->index();
                $table->string('source')->default('ICO');
                $table->decimal('ico_composite_usc_lb', 8, 2)->default(0.00);
                $table->decimal('ico_composite_usd_lb', 8, 4)->default(0.0000);
                $table->longText('payload_json')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coffee_market_snapshots');
    }
};
