<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Slice 2: business setup (role, entity type, optional TIN) and multi-select intent.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'business_role')) {
                $table->string('business_role')->nullable()->index(); // exporter, supplier, broker, buyer
            }
            if (!Schema::hasColumn('organizations', 'entity_type')) {
                $table->string('entity_type')->nullable(); // App\Support\BusinessEntityType
            }
            if (!Schema::hasColumn('organizations', 'tin_number')) {
                $table->string('tin_number', 40)->nullable()->index();
            }
            if (!Schema::hasColumn('organizations', 'needs_manual_review')) {
                // Set by the server (entity type "Other"); never by a request.
                $table->boolean('needs_manual_review')->default(false)->index();
            }
            if (!Schema::hasColumn('organizations', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'onboarding_intents')) {
                $table->json('onboarding_intents')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (Schema::hasColumn('organizations', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            foreach (['business_role', 'entity_type', 'tin_number', 'needs_manual_review'] as $column) {
                if (Schema::hasColumn('organizations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'onboarding_intents')) {
                $table->dropColumn('onboarding_intents');
            }
        });
    }
};
