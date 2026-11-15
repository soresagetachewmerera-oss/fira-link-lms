<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slice 8: events (category, online, agenda, member ticket, service fee, refund policy, organizer
 * business), tours (host business, deposit and balance, safety notes) and the purpose of a payment
 * (event ticket, tour deposit, tour balance) so a confirmed bank slip updates the right booking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('category', 30)->nullable()->after('summary'); // trade_fair, training, cupping, networking
            $table->boolean('is_online')->default(false)->after('location');
            $table->json('agenda')->nullable()->after('description');
            $table->decimal('member_price', 12, 2)->nullable()->after('price');
            $table->decimal('service_fee', 12, 2)->default(0)->after('member_price');
            $table->text('refund_policy')->nullable()->after('service_fee');
            $table->foreignId('organizer_organization_id')->nullable()->after('refund_policy')->constrained('organizations')->nullOnDelete();
            $table->boolean('organized_by_fira_link')->default(false)->after('organizer_organization_id');
        });

        Schema::table('event_bookings', function (Blueprint $table) {
            $table->string('ticket_type', 20)->default('general')->after('seats');
            $table->decimal('service_fee', 12, 2)->default(0)->after('unit_price');
            $table->string('company', 200)->nullable()->after('phone');
            $table->string('payment_method', 30)->nullable()->after('payment_status');
            // Phone-only accounts (slice 1) have no email.
            $table->string('email')->nullable()->change();
        });

        Schema::table('tours', function (Blueprint $table) {
            $table->foreignId('host_organization_id')->nullable()->after('metadata')->constrained('organizations')->nullOnDelete();
            $table->unsignedTinyInteger('deposit_percent')->default(30)->after('price');
            $table->unsignedSmallInteger('balance_due_days')->default(14)->after('deposit_percent');
            $table->text('safety_notes')->nullable()->after('cancellation_policy');
        });

        Schema::table('tour_bookings', function (Blueprint $table) {
            $table->decimal('deposit_amount', 12, 2)->nullable()->after('total_amount');
            $table->decimal('balance_amount', 12, 2)->nullable()->after('deposit_amount');
            $table->date('balance_due_date')->nullable()->after('balance_amount');
            $table->timestamp('deposit_paid_at')->nullable()->after('balance_due_date');
            $table->timestamp('balance_paid_at')->nullable()->after('deposit_paid_at');
            $table->string('payment_method', 30)->nullable()->after('payment_status');
            $table->string('email')->nullable()->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            // event_ticket, tour_deposit, tour_balance (null for older and course payments)
            $table->string('purpose', 30)->nullable()->after('payment_method');
            $table->foreignId('confirmed_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn(['purpose', 'confirmed_at']);
        });
        Schema::table('tour_bookings', fn (Blueprint $table) => $table->dropColumn(['deposit_amount', 'balance_amount', 'balance_due_date', 'deposit_paid_at', 'balance_paid_at', 'payment_method']));
        Schema::table('tours', function (Blueprint $table) {
            $table->dropConstrainedForeignId('host_organization_id');
            $table->dropColumn(['deposit_percent', 'balance_due_days', 'safety_notes']);
        });
        Schema::table('event_bookings', fn (Blueprint $table) => $table->dropColumn(['ticket_type', 'service_fee', 'company', 'payment_method']));
        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('organizer_organization_id');
            $table->dropColumn(['category', 'is_online', 'agenda', 'member_price', 'service_fee', 'refund_policy', 'organized_by_fira_link']);
        });
    }
};
