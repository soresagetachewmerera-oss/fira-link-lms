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
        // 1. Plans (SaaS Plans)
        if (!Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique(); // starter, growth, business
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_public')->default(true)->index();
                $table->boolean('is_active')->default(true)->index();
                $table->integer('sort_order')->default(0);
                $table->integer('trial_days')->default(0);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // 2. Plan Prices (USD & ETB, Monthly & Annual)
        if (!Schema::hasTable('plan_prices')) {
            Schema::create('plan_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
                $table->string('currency', 3)->default('ETB')->index(); // ETB, USD
                $table->string('billing_period', 10)->default('monthly')->index(); // monthly, annual
                $table->decimal('amount', 10, 2);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamp('effective_from')->nullable();
                $table->timestamps();

                $table->unique(['plan_id', 'currency', 'billing_period']);
            });
        }

        // 3. Features Registry
        if (!Schema::hasTable('features')) {
            Schema::create('features', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique(); // seats, active_lots, rfq_responses_per_month, active_deal_rooms, storage_gb, toolkit_level, etc.
                $table->string('label');
                $table->string('type', 20)->default('limit'); // limit, boolean, tier
                $table->string('unit')->nullable(); // seats, lots, per_month, GB, etc.
                $table->text('description')->nullable();
                $table->boolean('is_available')->default(true)->index(); // false = "Coming soon"
                $table->timestamps();
            });
        }

        // 4. Plan Entitlements (Matrix)
        if (!Schema::hasTable('plan_entitlements')) {
            Schema::create('plan_entitlements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
                $table->string('feature_key')->index();
                $table->string('value'); // integer (e.g. "5", "-1" for unlimited), boolean ("1"/"0"), or tier slug ("basic")
                $table->timestamps();

                $table->unique(['plan_id', 'feature_key']);
            });
        }

        // 5. Organization Entitlement Overrides (Founding member deals, support exceptions)
        if (!Schema::hasTable('organization_entitlement_overrides')) {
            Schema::create('organization_entitlement_overrides', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->string('feature_key')->index();
                $table->string('value');
                $table->string('reason');
                $table->timestamp('expires_at')->nullable()->index();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['organization_id', 'feature_key']);
            });
        }

        // 6. Subscriptions (Organization-Scoped)
        if (!Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
                $table->string('status')->default('trialing')->index(); // trialing, active, past_due, grace, canceled, expired
                $table->string('billing_period')->default('monthly'); // monthly, annual
                $table->timestamp('current_period_start')->nullable();
                $table->timestamp('current_period_end')->nullable()->index();
                $table->timestamp('grace_until')->nullable()->index();
                $table->boolean('cancel_at_period_end')->default(false);
                $table->string('provider')->default('chapa');
                $table->string('provider_ref')->nullable()->index();
                $table->timestamps();

                $table->index(['organization_id', 'status']);
            });
        }

        // 7. Subscription Events (Immutable Lifecycle Log)
        if (!Schema::hasTable('subscription_events')) {
            Schema::create('subscription_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->string('event_type')->index(); // created, upgraded, downgraded, renewed, failed, grace_started, expired, override_applied, admin_changed
                $table->foreignId('from_plan_id')->nullable()->constrained('plans')->nullOnDelete();
                $table->foreignId('to_plan_id')->nullable()->constrained('plans')->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }

        // 8. Atomic Usage Counters
        if (!Schema::hasTable('usage_counters')) {
            Schema::create('usage_counters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->string('feature_key')->index();
                $table->string('period_key')->index(); // e.g. "2026-11" for monthly quotas, or "forever"
                $table->integer('count')->default(0);
                $table->timestamps();

                $table->unique(['organization_id', 'feature_key', 'period_key']);
            });
        }

        // 9. Invoices (Subscription Billing Only)
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
                $table->string('invoice_number')->unique();
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('ETB');
                $table->decimal('exchange_rate', 12, 4)->nullable(); // Recorded exchange rate against ETB/USD
                $table->string('status')->default('pending')->index(); // pending, paid, failed, void, refunded
                $table->string('provider')->nullable();
                $table->string('provider_ref')->nullable()->index();
                $table->decimal('tax_amount', 10, 2)->default(0.00);
                $table->decimal('tax_rate', 5, 2)->nullable();
                $table->string('tax_notes')->nullable(); // Configurable tax fields (UNVERIFIED)
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->timestamps();
            });
        }

        // 10. Subscription Payments (Idempotent Transaction History)
        if (!Schema::hasTable('subscription_payments')) {
            Schema::create('subscription_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->string('currency', 3)->default('ETB');
                $table->string('status')->default('pending')->index(); // pending, successful, failed
                $table->string('provider')->default('chapa');
                $table->string('provider_ref')->unique()->index();
                $table->string('idempotency_key')->unique()->index();
                $table->json('raw_payload')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
            });
        }

        // 11. Webhook Calls (Raw Ingestion & Idempotency Storage)
        if (!Schema::hasTable('webhook_calls')) {
            Schema::create('webhook_calls', function (Blueprint $table) {
                $table->id();
                $table->string('provider')->index(); // chapa, telebirr, cbe_birr, etc.
                $table->string('provider_ref')->nullable()->index();
                $table->string('idempotency_key')->nullable()->index();
                $table->json('payload');
                $table->json('headers')->nullable();
                $table->string('status')->default('received')->index(); // received, processed, ignored, failed
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_calls');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('usage_counters');
        Schema::dropIfExists('subscription_events');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('organization_entitlement_overrides');
        Schema::dropIfExists('plan_entitlements');
        Schema::dropIfExists('features');
        Schema::dropIfExists('plan_prices');
        Schema::dropIfExists('plans');
    }
};
