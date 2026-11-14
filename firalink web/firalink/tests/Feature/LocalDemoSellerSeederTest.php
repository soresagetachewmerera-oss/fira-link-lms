<?php

namespace Tests\Feature;

use App\Models\CoffeeListing;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\LocalDemoSellerSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LocalDemoSellerSeederTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_refuses_to_run_outside_local(): void
    {
        // A developer database may already hold demo data from a local run: nothing may change.
        $users = User::count();
        $orgs = Organization::withTrashed()->count();
        $lots = CoffeeListing::withTrashed()->count();
        foreach (['production', 'testing', 'staging'] as $env) {
            $this->app['env'] = $env;
            app(LocalDemoSellerSeeder::class)->run();

            $this->assertSame($users, User::count(), $env);
            $this->assertSame($orgs, Organization::withTrashed()->count(), $env);
            $this->assertSame($lots, CoffeeListing::withTrashed()->count(), $env);
        }
    }

    public function test_in_local_it_creates_one_approved_seller_with_visible_lots_and_is_repeatable(): void
    {
        $this->app['env'] = 'local';
        app(LocalDemoSellerSeeder::class)->run();
        app(LocalDemoSellerSeeder::class)->run();

        $org = Organization::where('slug', LocalDemoSellerSeeder::ORG_SLUG)->firstOrFail();
        $this->assertSame('verified', $org->workspaceStatus());
        $this->assertSame(1, User::where('email', LocalDemoSellerSeeder::SELLER_EMAIL)->count());
        $this->assertSame(3, CoffeeListing::where('organization_id', $org->id)->visibleToBuyers()->count());
    }
}
