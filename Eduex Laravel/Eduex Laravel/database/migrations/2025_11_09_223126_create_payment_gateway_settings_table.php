<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_settings', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->unique();
            $table->string('name');
            $table->enum('type', ['online', 'offline'])->default('online');
            $table->json('credentials')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->text('offline_instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_settings');
    }
};
