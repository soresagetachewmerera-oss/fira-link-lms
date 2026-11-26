<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Release 2a: business categories (admin-managed labels) and the activities (capabilities) a business
 * declares. Additive: existing businesses keep business_category_id = null and their rights are derived
 * from business_role exactly as before (App\Services\Trade\CapabilityService::legacy()).
 * The ten starting categories come from the industry notes in the brief and
 * docs/product/07_coffee_b2b_decisions.md; admins can rename, add or switch them off. None is marked
 * legally confirmed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();      // never renamed: stored on businesses
            $table->string('label_en', 120);
            $table->string('label_am', 120);
            $table->string('description_en', 500)->nullable();
            $table->string('description_am', 500)->nullable();
            $table->string('business_role', 20);       // exporter, supplier, broker, buyer (documents and older apps)
            $table->json('capabilities');              // allowed activities
            $table->boolean('legally_confirmed')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('business_category_id')->nullable()->after('business_role')->constrained('business_categories')->nullOnDelete();
        });

        Schema::create('organization_capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('capability', 40);
            $table->foreignId('declared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['organization_id', 'capability']);
        });

        $now = now();
        $rows = [
            ['farmer', 'Farmer (Alami)', 'አልሚ (አርሶ አደር)', 'Grows coffee and sells it to local buyers.', 'ቡና አምርቶ ለሀገር ውስጥ ገዢዎች ይሸጣል።', 'supplier', ['produce', 'sell_domestic']],
            ['farmer_exporter', 'Farmer-exporter (Alami Laki)', 'አልሚ ላኪ', 'Grows coffee and exports it with an ECTA grower-exporter certificate.', 'ቡና አምርቶ በECTA የአምራች ላኪ የብቃት ማረጋገጫ ወደ ውጭ ይልካል።', 'exporter', ['produce', 'process', 'sell_domestic', 'sell_export']],
            ['supplier', 'Supplier (Akrabi)', 'አቅራቢ', 'Buys coffee from farmers, prepares it and supplies exporters.', 'ከአርሶ አደሮች ቡና ገዝቶ አዘጋጅቶ ለላኪዎች ያቀርባል።', 'supplier', ['aggregate', 'buy_domestic', 'sell_domestic']],
            ['supplier_exporter', 'Supplier-exporter (Akrabi Laki)', 'አቅራቢ ላኪ', 'Supplies coffee and exports it with an ECTA supplier-exporter certificate.', 'ቡና አቅርቦ በECTA የአቅራቢ ላኪ የብቃት ማረጋገጫ ወደ ውጭ ይልካል።', 'exporter', ['aggregate', 'buy_domestic', 'sell_domestic', 'sell_export']],
            ['cooperative_union', 'Cooperative union exporter', 'የኅብረት ሥራ ዩኒየን ላኪ', 'Collects members\' coffee and exports it.', 'የአባላትን ቡና ሰብስቦ ወደ ውጭ ይልካል።', 'exporter', ['aggregate', 'process', 'buy_domestic', 'sell_domestic', 'sell_export']],
            ['processing_exporter', 'Processed coffee exporter', 'የተዘጋጀ ቡና ላኪ', 'Processes raw coffee and exports it.', 'ጥሬ ቡናን አዘጋጅቶ ወደ ውጭ ይልካል።', 'exporter', ['process', 'buy_domestic', 'sell_export']],
            ['specialty_exporter', 'Specialty coffee exporter', 'የልዩ ቡና ላኪ', 'Exports specialty lots with cupping scores and samples.', 'የቅምሻ ውጤትና ናሙና ያላቸውን ልዩ ቡናዎች ይልካል።', 'exporter', ['process', 'buy_domestic', 'sell_export']],
            ['general_exporter', 'Coffee exporter', 'ቡና ላኪ', 'Buys eligible coffee and exports it.', 'ብቁ ቡና ገዝቶ ወደ ውጭ ይልካል።', 'exporter', ['buy_domestic', 'sell_export']],
            ['international_buyer', 'International buyer or importer', 'ዓለም አቀፍ ገዢ ወይም አስመጪ', 'Buys Ethiopian coffee for import.', 'የኢትዮጵያን ቡና ገዝቶ ያስመጣል።', 'buyer', ['buy_export']],
            ['roaster', 'Roaster or distributor', 'ቡና የሚቆላ ወይም የሚያከፋፍል ድርጅት', 'Buys green coffee to roast or distribute.', 'ለመቁላት ወይም ለማከፋፈል አረንጓዴ ቡና ይገዛል።', 'buyer', ['buy_domestic', 'buy_export']],
            ['broker', 'Coffee broker', 'የቡና ደላላ', 'Arranges trades between buyers and sellers.', 'በገዢዎችና በሻጮች መካከል ግብይት ያገናኛል።', 'broker', ['sell_export', 'buy_export']],
        ];
        foreach ($rows as $i => [$code, $en, $am, $den, $dam, $role, $caps]) {
            DB::table('business_categories')->insertOrIgnore([
                'code' => $code, 'label_en' => $en, 'label_am' => $am, 'description_en' => $den, 'description_am' => $dam,
                'business_role' => $role, 'capabilities' => json_encode($caps), 'legally_confirmed' => false,
                'is_active' => true, 'sort_order' => ($i + 1) * 10, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_capabilities');
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('business_category_id');
        });
        Schema::dropIfExists('business_categories');
    }
};
