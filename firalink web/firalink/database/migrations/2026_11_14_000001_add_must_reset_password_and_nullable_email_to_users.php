<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - must_reset_password: set by `auth:flag-weak-passwords --force`; the account must
     *   choose a new password before it can sign in again.
     * - email nullable: people who sign up with a phone code add an email later in Profile.
     *   The unique index stays (NULLs do not collide in MySQL or SQLite).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'must_reset_password')) {
                $table->boolean('must_reset_password')->default(false);
            }
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'must_reset_password')) {
                $table->dropColumn('must_reset_password');
            }
            // Email is left nullable: phone-only accounts may exist by then.
        });
    }
};
