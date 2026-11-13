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
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('destination');
            $table->string('meeting_point')->nullable();
            $table->integer('duration_days')->default(1);
            $table->integer('duration_hours')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('departure_time')->nullable();
            $table->integer('total_seats')->default(20);
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('ETB');
            $table->string('category')->nullable();
            $table->string('difficulty_level')->default('Moderate');
            $table->string('featured_image')->nullable();
            $table->json('gallery')->nullable();
            $table->json('itinerary')->nullable();
            $table->json('included_services')->nullable();
            $table->json('exclusions')->nullable();
            $table->json('requirements')->nullable();
            $table->text('cancellation_policy')->nullable();
            $table->json('available_schedules')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('tour_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained('tours')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->integer('participants')->default(1);
            $table->date('schedule_date')->nullable();
            $table->string('departure_time')->nullable();
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('ETB');
            $table->string('status')->default('pending'); // pending, confirmed, cancelled, completed
            $table->string('payment_status')->default('pending'); // pending, paid, refunded, failed
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->text('special_requests')->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('booked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'tour_booking_id')) {
                    $table->foreignId('tour_booking_id')->nullable()->after('enrollment_id')->constrained('tour_bookings')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'tour_booking_id')) {
                    $table->dropForeign(['tour_booking_id']);
                    $table->dropColumn('tour_booking_id');
                }
            });
        }

        Schema::dropIfExists('tour_bookings');
        Schema::dropIfExists('tours');
    }
};
