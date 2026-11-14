<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slice 5: requests (RFQs) and quotes belong to business workspaces. buyer_id / seller_id stay as
 * "created by". Volume keeps the buyer's unit and a kg value so quotes can be compared.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_rfqs', function (Blueprint $table) {
            if (!Schema::hasColumn('marketplace_rfqs', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->after('buyer_id')->constrained('organizations')->nullOnDelete();
            }
            if (!Schema::hasColumn('marketplace_rfqs', 'volume_value')) {
                $table->decimal('volume_value', 12, 2)->nullable()->after('volume_bags');
                $table->string('volume_unit', 10)->default('bags')->after('volume_value'); // bags (60 kg), kg, mt
                $table->decimal('volume_kg', 14, 2)->nullable()->after('volume_unit');
            }
            if (!Schema::hasColumn('marketplace_rfqs', 'sample_first')) {
                $table->boolean('sample_first')->default(false)->after('specifications');
            }
            if (!Schema::hasColumn('marketplace_rfqs', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('status');
            }
        });

        Schema::table('marketplace_quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('marketplace_quotations', 'organization_id')) {
                $table->foreignId('organization_id')->nullable()->after('seller_id')->constrained('organizations')->nullOnDelete();
            }
            if (!Schema::hasColumn('marketplace_quotations', 'decided_at')) {
                $table->timestamp('decided_at')->nullable()->after('accepted_at');
                $table->foreignId('decided_by')->nullable()->after('decided_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_quotations', function (Blueprint $table) {
            foreach (['organization_id', 'decided_by'] as $fk) {
                if (Schema::hasColumn('marketplace_quotations', $fk)) {
                    $table->dropConstrainedForeignId($fk);
                }
            }
            if (Schema::hasColumn('marketplace_quotations', 'decided_at')) {
                $table->dropColumn('decided_at');
            }
        });
        Schema::table('marketplace_rfqs', function (Blueprint $table) {
            if (Schema::hasColumn('marketplace_rfqs', 'organization_id')) {
                $table->dropConstrainedForeignId('organization_id');
            }
            foreach (['volume_value', 'volume_unit', 'volume_kg', 'sample_first', 'closed_at'] as $col) {
                if (Schema::hasColumn('marketplace_rfqs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
