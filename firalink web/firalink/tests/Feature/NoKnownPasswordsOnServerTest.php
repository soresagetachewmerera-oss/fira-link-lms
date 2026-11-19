<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Fix slice B (GAP-019): seeding a server never creates accounts with a known password. */
class NoKnownPasswordsOnServerTest extends TestCase
{
    use DatabaseTransactions;

    private const SEEDED = ['admin@fira-link.com', 'admin@eduex.com', 'instructor@eduex.com', 'student@eduex.com'];

    public function test_server_seed_creates_only_the_platform_admin_with_a_random_password(): void
    {
        User::whereIn('email', self::SEEDED)->delete();
        $this->app['env'] = 'production';

        try {
            $this->app->make(AdminSeeder::class)->run();
        } finally {
            $this->app['env'] = 'testing';
        }

        $admin = User::where('email', 'admin@fira-link.com')->first();
        $this->assertNotNull($admin);
        $this->assertFalse(Hash::check('password', $admin->password));
        $this->assertFalse(User::whereIn('email', ['admin@eduex.com', 'instructor@eduex.com', 'student@eduex.com'])->exists());
    }

    public function test_seeding_again_never_changes_an_existing_password(): void
    {
        $admin = User::where('email', 'admin@fira-link.com')->first() ?? User::factory()->create(['email' => 'admin@fira-link.com', 'role' => 'admin']);
        $admin->forceFill(['password' => Hash::make('a-real-owner-password')])->save();
        $this->app['env'] = 'production';

        try {
            $this->app->make(AdminSeeder::class)->run();
        } finally {
            $this->app['env'] = 'testing';
        }

        $this->assertTrue(Hash::check('a-real-owner-password', $admin->fresh()->password));
    }
}
