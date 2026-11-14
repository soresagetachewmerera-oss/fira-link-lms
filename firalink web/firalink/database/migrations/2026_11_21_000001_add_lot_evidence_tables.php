<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Slice 4: lot evidence. The lot level is computed from evidence a reviewer approved
 * (App\Services\Lots\LotLevelService); clients never set it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coffee_listings', function (Blueprint $table) {
            if (!Schema::hasColumn('coffee_listings', 'lot_code')) {
                $table->string('lot_code', 16)->nullable()->unique()->after('slug');
            }
            if (!Schema::hasColumn('coffee_listings', 'evidence_valid_until')) {
                $table->timestamp('evidence_valid_until')->nullable()->after('evidence_level');
            }
            if (!Schema::hasColumn('coffee_listings', 'published_at')) {
                $table->timestamp('published_at')->nullable()->after('status');
            }
        });

        // Every existing lot gets a lot code (sellers show it on their bags in evidence photos).
        \Illuminate\Support\Facades\DB::table('coffee_listings')->whereNull('lot_code')->orderBy('id')->select('id')
            ->chunkById(200, function ($lots) {
                foreach ($lots as $lot) {
                    \Illuminate\Support\Facades\DB::table('coffee_listings')->where('id', $lot->id)
                        ->update(['lot_code' => \App\Models\CoffeeListing::generateLotCode()]);
                }
            });

        Schema::create('lot_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('coffee_listings')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            // photo_bags, photo_beans, photo_warehouse, lab_report
            $table->string('kind', 40)->index();
            // in_review, approved, needs_changes, rejected, expired, superseded
            $table->string('status', 20)->default('in_review')->index();
            $table->timestamp('captured_at')->nullable();      // from the phone (and EXIF)
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->unsignedInteger('location_accuracy_m')->nullable();
            $table->json('details')->nullable();               // lab report: lab_name, report_number, issued_at
            $table->json('checks')->nullable();                // reviewer confirmations
            $table->string('public_path')->nullable();          // approved photo, EXIF removed, public disk
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_reason_code', 40)->nullable();
            $table->text('review_reason')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->json('reminders_sent')->nullable();
            $table->timestamps();
        });

        Schema::create('lot_evidence_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_evidence_id')->constrained('lot_evidence')->cascadeOnDelete();
            $table->unsignedSmallInteger('page_number');
            $table->string('file_path');
            $table->string('file_disk', 40)->default('verification');
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size_kb');
            $table->string('sha256', 64);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lot_evidence_pages');
        Schema::dropIfExists('lot_evidence');
        Schema::table('coffee_listings', function (Blueprint $table) {
            foreach (['lot_code', 'evidence_valid_until', 'published_at'] as $col) {
                if (Schema::hasColumn('coffee_listings', $col)) {
                    if ($col === 'lot_code') {
                        $table->dropUnique(['lot_code']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
