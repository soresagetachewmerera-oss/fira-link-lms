<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Slice 1: one-time commands for weak default passwords and alias email pairs.
 */
class AuthCleanupCommandsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_flag_weak_passwords_counts_in_dry_run_and_flags_with_force(): void
    {
        $weakA = User::factory()->create(['email' => 'w1_' . Str::random(8) . '@example.test', 'password' => Hash::make('12345678')]);
        $weakB = User::factory()->create(['email' => 'w2_' . Str::random(8) . '@example.test', 'password' => Hash::make('password')]);
        $strong = User::factory()->create(['email' => 's_' . Str::random(8) . '@example.test', 'password' => Hash::make('Str0ngPass')]);
        $weakA->createToken('t');

        $this->artisan('auth:flag-weak-passwords')->assertSuccessful();
        $this->assertFalse($weakA->fresh()->must_reset_password, 'Dry run must not change anything.');

        $this->artisan('auth:flag-weak-passwords', ['--force' => true])
            ->expectsOutputToContain('Flagged')
            ->doesntExpectOutputToContain($weakA->email)
            ->assertSuccessful();

        $this->assertTrue($weakA->fresh()->must_reset_password);
        $this->assertTrue($weakB->fresh()->must_reset_password);
        $this->assertFalse($strong->fresh()->must_reset_password);
        $this->assertSame(0, $weakA->tokens()->count());
        $this->assertTrue(AuditLog::where('action', 'account_flagged_must_reset_password')->where('auditable_id', $weakA->id)->exists());
    }

    public function test_alias_report_lists_pairs_and_changes_nothing(): void
    {
        $local = 'pair_' . Str::lower(Str::random(8));
        $f = User::factory()->create(['email' => $local . '@firalink.com']);
        $e = User::factory()->create(['email' => $local . '@eduex.com']);
        User::factory()->create(['email' => 'solo_' . Str::lower(Str::random(8)) . '@firalink.com']);

        $this->artisan('auth:report-email-aliases')
            ->expectsOutputToContain('Nothing was changed')
            ->doesntExpectOutputToContain($local)
            ->assertSuccessful();

        $this->assertSame($local . '@firalink.com', $f->fresh()->email);
        $this->assertSame($local . '@eduex.com', $e->fresh()->email);
    }
}
