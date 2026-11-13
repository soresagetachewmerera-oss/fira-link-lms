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
        // 1. Seller Profiles
        Schema::create('marketplace_seller_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('company_name');
            $table->string('company_slug')->unique();
            $table->string('business_type')->default('exporter'); // exporter, cooperative, commercial_farm, union, broker, roaster
            $table->string('export_license_no')->nullable()->index();
            $table->string('tin_number')->nullable();
            $table->json('origin_regions')->nullable();
            $table->json('washing_stations')->nullable();
            $table->integer('washing_station_count')->default(0);
            $table->decimal('annual_export_volume_mt', 10, 2)->nullable();
            $table->json('certifications')->nullable();
            $table->text('company_bio')->nullable();
            $table->string('website_url')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Ethiopia');
            $table->string('logo_path')->nullable();
            $table->string('banner_path')->nullable();
            $table->string('license_document_path')->nullable();
            $table->string('verification_status')->default('pending')->index(); // pending, under_review, verified, rejected
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamps();
        });

        // 2. Coffee Listings
        Schema::create('coffee_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('origin_region')->index(); // Sidama, Yirgacheffe, Guji, Limu, Harrar, Jimma, etc.
            $table->string('sub_region')->nullable(); // Bensa, Kochere, Uraga, etc.
            $table->string('washing_station')->nullable();
            $table->string('coffee_type')->default('arabica');
            $table->string('variety')->nullable(); // 74110, 74112, Heirloom, Kurume, Dega, etc.
            $table->string('processing_method')->default('washed')->index(); // washed, natural, anaerobic, honey, experimental
            $table->string('grade')->default('grade_1')->index(); // grade_1, grade_2, grade_3, grade_4, grade_5, undergrade
            $table->string('screen_size')->nullable(); // Screen 14-16, Screen 17+
            $table->decimal('cupping_score', 4, 2)->nullable()->index();
            $table->decimal('moisture_content', 4, 2)->nullable();
            $table->decimal('water_activity', 4, 2)->nullable();
            $table->string('crop_year')->default('2025/2026');
            $table->integer('altitude_min')->nullable();
            $table->integer('altitude_max')->nullable();
            $table->integer('total_quantity_bags')->default(0);
            $table->integer('available_quantity_bags')->default(0);
            $table->integer('minimum_order_bags')->default(10);
            $table->string('bag_type')->default('jute_with_grainpro'); // jute_with_grainpro, standard_jute, vacuum_pack
            $table->decimal('bag_weight_kg', 5, 2)->default(60.00);
            $table->decimal('indicative_price_usd_per_lb', 6, 2)->nullable();
            $table->decimal('indicative_price_usd_per_mt', 10, 2)->nullable();
            $table->string('incoterm')->default('FOB Djibouti');
            $table->string('port_of_loading')->default('Djibouti');
            $table->boolean('sample_available')->default(true);
            $table->decimal('sample_cost_usd', 6, 2)->default(0.00);
            $table->json('tasting_notes')->nullable();
            $table->json('sensory_attributes')->nullable(); // aroma, acidity, body, flavor, aftertaste, balance (1-10)
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index(); // draft, pending_approval, active, sold_out, archived
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamp('featured_until')->nullable();
            $table->integer('views_count')->default(0);
            $table->integer('inquiries_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // 3. Coffee Listing Media
        Schema::create('coffee_listing_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('coffee_listings')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('media_type')->default('image'); // image, cupping_report, certificate, document
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 4. Requests For Quotation (RFQ)
        Schema::create('marketplace_rfqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->string('rfq_number')->unique();
            $table->string('title');
            $table->string('target_origin_region')->nullable();
            $table->string('target_grade')->nullable();
            $table->string('target_processing_method')->nullable();
            $table->decimal('target_cupping_score_min', 4, 2)->nullable();
            $table->integer('volume_bags');
            $table->string('bag_type_preference')->default('jute_with_grainpro');
            $table->decimal('target_price_usd_per_lb', 6, 2)->nullable();
            $table->string('destination_country');
            $table->string('destination_port');
            $table->string('preferred_incoterm')->default('FOB');
            $table->string('preferred_payment_terms')->nullable(); // LC at sight, CAD, TT
            $table->date('target_delivery_date')->nullable();
            $table->date('closing_date')->nullable()->index();
            $table->text('specifications')->nullable();
            $table->string('status')->default('open')->index(); // open, reviewing, fulfilled, closed, cancelled
            $table->integer('quotes_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Quotations (Offers)
        Schema::create('marketplace_quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->nullable()->constrained('marketplace_rfqs')->onDelete('cascade');
            $table->foreignId('listing_id')->nullable()->constrained('coffee_listings')->onDelete('set null');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->string('quote_number')->unique();
            $table->decimal('offered_price_usd_per_lb', 6, 2);
            $table->decimal('total_price_usd', 12, 2);
            $table->integer('quantity_bags');
            $table->string('incoterm')->default('FOB Djibouti');
            $table->string('port_of_loading')->default('Djibouti');
            $table->string('port_of_destination')->nullable();
            $table->string('payment_terms')->default('LC at sight');
            $table->date('estimated_shipping_date')->nullable();
            $table->date('validity_date');
            $table->text('terms_and_conditions')->nullable();
            $table->boolean('sample_available')->default(true);
            $table->string('status')->default('submitted')->index(); // submitted, under_review, accepted, rejected, expired
            $table->timestamp('accepted_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });

        // 6. Direct Inquiries & Sample Requests
        Schema::create('marketplace_inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->nullable()->constrained('coffee_listings')->onDelete('cascade');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('buyer_id')->constrained('users')->onDelete('cascade');
            $table->string('inquiry_type')->default('general'); // general, sample_request, price_quote, visit_request
            $table->string('subject');
            $table->text('message');
            $table->integer('quantity_interested_bags')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('courier_account')->nullable(); // DHL/FedEx account
            $table->string('sample_status')->default('none'); // none, requested, approved, dispatched, delivered, rejected
            $table->string('tracking_number')->nullable();
            $table->string('status')->default('pending'); // pending, replied, closed
            $table->timestamps();
        });

        // 7. Subscription Plans for Marketplace
        Schema::create('marketplace_subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('role_target')->default('seller'); // seller, buyer
            $table->text('description')->nullable();
            $table->decimal('price_etb', 10, 2)->default(0.00);
            $table->decimal('price_usd', 10, 2)->default(0.00);
            $table->string('billing_cycle')->default('monthly'); // monthly, yearly
            $table->integer('active_listings_limit')->default(0); // 0 = unlimited or tier specific
            $table->integer('rfq_response_limit')->default(0);
            $table->integer('featured_slots')->default(0);
            $table->boolean('verified_badge_included')->default(false);
            $table->boolean('analytics_access')->default(false);
            $table->boolean('priority_support')->default(false);
            $table->json('features')->nullable();
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 8. User Marketplace Subscriptions
        Schema::create('user_marketplace_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('plan_id')->constrained('marketplace_subscription_plans')->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->string('status')->default('active')->index(); // active, pending, expired, cancelled
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 9. Saved / Favorited Listings
        Schema::create('marketplace_saved_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('listing_id')->constrained('coffee_listings')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'listing_id']);
        });

        // 10. Add marketplace_subscription_id to payments table if not present
        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'marketplace_subscription_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('marketplace_subscription_id')
                      ->nullable()
                      ->after('event_booking_id')
                      ->constrained('user_marketplace_subscriptions')
                      ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'marketplace_subscription_id')) {
            Schema::table('payments', function (Blueprint $table) {
                try {
                    $table->dropForeign(['marketplace_subscription_id']);
                } catch (\Throwable $e) {}
                $table->dropColumn('marketplace_subscription_id');
            });
        }

        Schema::dropIfExists('marketplace_saved_listings');
        Schema::dropIfExists('user_marketplace_subscriptions');
        Schema::dropIfExists('marketplace_subscription_plans');
        Schema::dropIfExists('marketplace_inquiries');
        Schema::dropIfExists('marketplace_quotations');
        Schema::dropIfExists('marketplace_rfqs');
        Schema::dropIfExists('coffee_listing_media');
        Schema::dropIfExists('coffee_listings');
        Schema::dropIfExists('marketplace_seller_profiles');
    }
};

