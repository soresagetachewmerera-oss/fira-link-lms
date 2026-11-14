<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slice 6: samples between businesses. The buyer's delivery contact and courier account are
 * stored encrypted and shown only on that sample's shipping view. The seal ID is unique; photos of
 * the sealed sample and the retained copy are private files.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_samples', function (Blueprint $table) {
            if (!Schema::hasColumn('marketplace_samples', 'buyer_organization_id')) {
                $table->foreignId('buyer_organization_id')->nullable()->after('buyer_id')->constrained('organizations')->nullOnDelete();
            }
            if (!Schema::hasColumn('marketplace_samples', 'delivery_company')) {
                // Encrypted text (contact details): only the seller of this one sample sees them.
                $table->text('delivery_address')->nullable()->after('shipping_address');
                $table->text('delivery_company')->nullable()->after('delivery_address');
                $table->string('delivery_country', 100)->nullable()->after('delivery_company');
                $table->text('delivery_phone')->nullable()->after('delivery_country');
                $table->string('courier_carrier', 20)->nullable()->after('delivery_phone'); // dhl, fedex
                $table->text('courier_account')->nullable()->after('courier_carrier');
            }
            if (!Schema::hasColumn('marketplace_samples', 'seal_id')) {
                $table->string('seal_id', 32)->nullable()->unique()->after('retained_sample_seal_hash');
                $table->string('sealed_photo_path')->nullable()->after('seal_id');
                $table->string('retained_photo_path')->nullable()->after('sealed_photo_path');
                $table->timestamp('sealed_at')->nullable()->after('retained_photo_path');
            }
            if (!Schema::hasColumn('marketplace_samples', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_status');
                $table->string('payment_reference', 100)->nullable()->after('paid_at');
                $table->date('ship_by')->nullable()->after('payment_reference');
            }
            if (!Schema::hasColumn('marketplace_samples', 'seal_intact')) {
                $table->boolean('seal_intact')->nullable()->after('delivery_date');
                $table->timestamp('evaluated_at')->nullable()->after('cupping_notes');
                $table->timestamp('cancelled_at')->nullable()->after('evaluated_at');
            }
        });

        Schema::table('deal_rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('deal_rooms', 'sample_seal_id')) {
                // The approved sample's seal ID, written into the contract so the shipment can be compared.
                $table->string('sample_seal_id', 32)->nullable()->after('listing_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deal_rooms', function (Blueprint $table) {
            if (Schema::hasColumn('deal_rooms', 'sample_seal_id')) {
                $table->dropColumn('sample_seal_id');
            }
        });
        Schema::table('marketplace_samples', function (Blueprint $table) {
            if (Schema::hasColumn('marketplace_samples', 'buyer_organization_id')) {
                $table->dropConstrainedForeignId('buyer_organization_id');
            }
            if (Schema::hasColumn('marketplace_samples', 'seal_id')) {
                $table->dropUnique(['seal_id']);
            }
            foreach (['delivery_address', 'delivery_company', 'delivery_country', 'delivery_phone', 'courier_carrier', 'courier_account', 'seal_id',
                'sealed_photo_path', 'retained_photo_path', 'sealed_at', 'paid_at', 'payment_reference', 'ship_by',
                'seal_intact', 'evaluated_at', 'cancelled_at'] as $col) {
                if (Schema::hasColumn('marketplace_samples', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
