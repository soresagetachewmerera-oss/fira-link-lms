<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Organizations
        if (!Schema::hasTable('organizations')) {
            Schema::create('organizations', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('business_type')->default('exporter'); // exporter, cooperative, commercial_farm, union, broker, roaster, buyer
                $table->string('status')->default('active')->index(); // active, suspended
                $table->string('logo_path')->nullable();
                $table->string('billing_email')->nullable();
                $table->string('country')->default('Ethiopia');
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // 2. Organization Memberships (Multi-seat role-based access)
        if (!Schema::hasTable('organization_memberships')) {
            Schema::create('organization_memberships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('role')->default('staff')->index(); // owner, manager, staff
                $table->boolean('is_active')->default(true)->index();
                $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('joined_at')->nullable();
                $table->timestamps();

                $table->unique(['organization_id', 'user_id']);
            });
        }

        // 3. Add organization_id to marketplace_seller_profiles (Backward-compatible bridge)
        if (Schema::hasTable('marketplace_seller_profiles') && !Schema::hasColumn('marketplace_seller_profiles', 'organization_id')) {
            Schema::table('marketplace_seller_profiles', function (Blueprint $table) {
                $table->foreignId('organization_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('organizations')
                    ->nullOnDelete();
            });

            // Backfill existing seller profiles into organizations seamlessly
            $sellers = DB::table('marketplace_seller_profiles')->get();
            foreach ($sellers as $seller) {
                $orgSlug = $seller->company_slug ?? Str::slug($seller->company_name) . '-' . $seller->id;
                // Ensure slug uniqueness
                $existingOrg = DB::table('organizations')->where('slug', $orgSlug)->first();
                if ($existingOrg) {
                    $orgId = $existingOrg->id;
                } else {
                    $orgId = DB::table('organizations')->insertGetId([
                        'name' => $seller->company_name ?? 'Exporter ' . $seller->id,
                        'slug' => $orgSlug,
                        'business_type' => $seller->business_type ?? 'exporter',
                        'status' => 'active',
                        'logo_path' => $seller->logo_path ?? null,
                        'billing_email' => $seller->contact_email ?? null,
                        'country' => $seller->country ?? 'Ethiopia',
                        'created_at' => $seller->created_at ?? now(),
                        'updated_at' => $seller->updated_at ?? now(),
                    ]);
                }

                DB::table('marketplace_seller_profiles')
                    ->where('id', $seller->id)
                    ->update(['organization_id' => $orgId]);

                // Ensure user is registered as owner in organization_memberships
                $existingMembership = DB::table('organization_memberships')
                    ->where('organization_id', $orgId)
                    ->where('user_id', $seller->user_id)
                    ->first();

                if (!$existingMembership) {
                    DB::table('organization_memberships')->insert([
                        'organization_id' => $orgId,
                        'user_id' => $seller->user_id,
                        'role' => 'owner',
                        'is_active' => true,
                        'joined_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('marketplace_seller_profiles') && Schema::hasColumn('marketplace_seller_profiles', 'organization_id')) {
            Schema::table('marketplace_seller_profiles', function (Blueprint $table) {
                try {
                    $table->dropForeign(['organization_id']);
                } catch (\Throwable $e) {}
                $table->dropColumn('organization_id');
            });
        }

        Schema::dropIfExists('organization_memberships');
        Schema::dropIfExists('organizations');
    }
};
