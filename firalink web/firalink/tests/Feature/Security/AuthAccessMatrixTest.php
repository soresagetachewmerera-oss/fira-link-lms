<?php

namespace Tests\Feature\Security;

use App\Models\CoffeeListing;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Auth gating 2026-11-20, Phase 1. Every signed-in API route answers 401 with the same body for a
 * missing, invalid, expired or revoked ("leftover") token; wrong role, wrong user, wrong workspace,
 * removed member and suspended business get 403/404 without data. Real bearer tokens, as the app sends.
 */
class AuthAccessMatrixTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    private const UNAUTHENTICATED = ['success' => false, 'message' => 'Unauthenticated.'];

    /** Route parameters that are constrained to fixed values. */
    private const PARAM_VALUES = ['kind' => 'sealed', 'type' => 'question', 'provider' => 'chapa', 'format' => 'pdf'];

    /** @return array<int, array{method: string, uri: string, signed: bool, middleware: string[]}> */
    private function signedInApiRoutes(): array
    {
        $router = app('router');
        $out = [];
        foreach (Route::getRoutes() as $route) {
            if (!str_starts_with($route->uri(), 'api/')) {
                continue;
            }
            $mw = $router->gatherRouteMiddleware($route);
            if (!collect($mw)->contains(fn ($m) => str_starts_with($m, \Illuminate\Auth\Middleware\Authenticate::class))) {
                continue;
            }
            $uri = preg_replace_callback('/\{(\w+)\??\}/', fn ($m) => self::PARAM_VALUES[$m[1]] ?? '1', $route->uri());
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $out[] = [
                    'method' => $method,
                    'uri' => '/' . $uri,
                    'signed' => in_array(\Illuminate\Routing\Middleware\ValidateSignature::class, $mw, true),
                    'middleware' => $mw,
                ];
            }
        }

        return $out;
    }

    private function send(string $method, string $uri, ?string $token): \Illuminate\Testing\TestResponse
    {
        $this->app['auth']->forgetGuards();
        $headers = ['Accept' => 'application/json'];
        if ($token !== null) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $this->json($method, $uri, [], $headers);
    }

    public function test_every_signed_in_api_route_refuses_missing_invalid_expired_and_leftover_tokens(): void
    {
        $user = $this->makeUser();

        $expired = $user->createToken('old-phone');
        PersonalAccessToken::whereKey($expired->accessToken->id)->update([
            'created_at' => now()->subMinutes((int) config('sanctum.expiration') + 5),
        ]);

        // A token from a device that signed out: the row is gone, the app may still hold the string.
        $leftover = $user->createToken('signed-out')->plainTextToken;
        $this->withToken($leftover)->postJson('/api/logout')->assertOk();

        $tokens = [
            'none' => null,
            'invalid' => '999999|' . Str::random(40),
            'expired' => $expired->plainTextToken,
            'leftover' => $leftover,
        ];

        $routes = $this->signedInApiRoutes();
        $this->assertGreaterThan(200, count($routes), 'route discovery failed');

        $failures = [];
        foreach ($routes as $r) {
            foreach ($tokens as $kind => $token) {
                $res = $this->send($r['method'], $r['uri'], $token);
                // A signed route may refuse the missing signature first; either way nothing is served.
                $ok = $res->status() === 401 ? $res->json() === self::UNAUTHENTICATED : ($r['signed'] && $res->status() === 403);
                if (!$ok) {
                    $failures[] = "{$r['method']} {$r['uri']} [{$kind}] -> {$res->status()} " . Str::limit((string) $res->getContent(), 120);
                }
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_instructor_routes_refuse_a_student_and_admin_routes_refuse_everyone_else(): void
    {
        $student = $this->makeUser('student');
        $token = $student->createToken('app')->plainTextToken;

        $failures = [];
        foreach ($this->signedInApiRoutes() as $r) {
            $instructorOnly = in_array(\App\Http\Middleware\CheckRole::class . ':instructor', $r['middleware'], true);
            $adminOnly = in_array(\App\Http\Middleware\CheckRole::class . ':admin', $r['middleware'], true);
            if (!$instructorOnly && !$adminOnly) {
                continue;
            }
            $res = $this->send($r['method'], $r['uri'], $token);
            if ($res->status() !== 403 || $res->json('data') !== null) {
                $failures[] = "{$r['method']} {$r['uri']} -> {$res->status()}";
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_wrong_user_cannot_open_another_persons_deal_room_lot_team_plan_or_payout(): void
    {
        [$deal, $buyer, $seller] = $this->makeDealRoom();
        $sellerOrg = $deal->organization;
        $lot = $this->draftLot($sellerOrg, $seller);

        $outsider = $this->makeUser();
        $outsiderOrg = $this->makeOrganization($outsider);
        $outsider->forceFill(['active_organization_id' => $outsiderOrg->id])->save();
        $token = $outsider->createToken('app')->plainTextToken;

        foreach ([
            ['GET', "/api/deal-rooms/{$deal->id}"],
            ['POST', "/api/deal-rooms/{$deal->id}/complete"],
            ['POST', "/api/deal-rooms/{$deal->id}/dispute"],
            ['GET', "/api/marketplace/seller/lots/{$lot->id}"],
            ['PUT', "/api/marketplace/seller/lots/{$lot->id}"],
            ['DELETE', "/api/marketplace/seller/lots/{$lot->id}"],
            ['GET', "/api/organizations/{$sellerOrg->id}/members"],
            ['DELETE', "/api/organizations/{$sellerOrg->id}/members/{$seller->id}"],
            ['GET', "/api/org/{$sellerOrg->id}/entitlements"],
        ] as [$method, $uri]) {
            $res = $this->send($method, $uri, $token);
            $this->assertContains($res->status(), [403, 404], "{$method} {$uri} answered {$res->status()}");
            $this->assertNull($res->json('data'), "{$method} {$uri} returned data");
        }

        // Nothing changed.
        $this->assertDatabaseHas('coffee_listings', ['id' => $lot->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('organization_memberships', ['organization_id' => $sellerOrg->id, 'user_id' => $seller->id, 'is_active' => true]);
    }

    public function test_a_plain_member_cannot_see_or_change_the_payout_account(): void
    {
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $org->forceFill(['business_role' => 'exporter'])->save();
        $this->confirmedPayoutAccount($org);

        $member = $this->makeUser();
        OrganizationMembership::create(['organization_id' => $org->id, 'user_id' => $member->id, 'role' => 'member', 'is_active' => true, 'joined_at' => now()]);
        $member->forceFill(['active_organization_id' => $org->id])->save();
        $token = $member->createToken('app')->plainTextToken;

        $res = $this->send('GET', '/api/payout-account', $token);
        $res->assertStatus(403);
        $this->assertNull($res->json('data'));
    }

    public function test_a_removed_team_member_loses_access_to_the_business(): void
    {
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $org->forceFill(['business_role' => 'exporter'])->save();
        $lot = $this->draftLot($org, $owner);

        $member = $this->makeUser();
        OrganizationMembership::create(['organization_id' => $org->id, 'user_id' => $member->id, 'role' => 'manager', 'is_active' => true, 'joined_at' => now()]);
        $member->forceFill(['active_organization_id' => $org->id])->save();
        $memberToken = $member->createToken('app')->plainTextToken;

        $this->send('GET', "/api/marketplace/seller/lots/{$lot->id}", $memberToken)->assertOk();

        $ownerToken = $owner->createToken('app')->plainTextToken;
        $this->send('DELETE', "/api/organizations/{$org->id}/members/{$member->id}", $ownerToken)->assertOk();

        foreach ([
            ['GET', "/api/marketplace/seller/lots/{$lot->id}"],
            ['PUT', "/api/marketplace/seller/lots/{$lot->id}"],
            ['GET', "/api/organizations/{$org->id}/members"],
            ['GET', "/api/org/{$org->id}/entitlements"],
        ] as [$method, $uri]) {
            $res = $this->send($method, $uri, $memberToken);
            $this->assertContains($res->status(), [403, 404], "{$method} {$uri} answered {$res->status()} after removal");
            $this->assertNull($res->json('data'));
        }
    }

    public function test_a_suspended_business_cannot_publish_and_its_lots_disappear(): void
    {
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $owner->forceFill(['active_organization_id' => $org->id])->save();
        $this->approveBusiness($org);
        $lot = $this->draftLot($org, $owner);
        $lot->forceFill(['status' => 'active', 'published_at' => now()])->save();
        $this->send('GET', "/api/marketplace/listings/{$lot->id}", null)->assertOk();

        $org->forceFill(['status' => 'suspended'])->save();

        $this->send('GET', "/api/marketplace/listings/{$lot->id}", null)->assertNotFound();
        $res = $this->send('POST', "/api/marketplace/seller/lots/{$lot->id}/publish", $owner->createToken('app')->plainTextToken);
        $this->assertContains($res->status(), [403, 422], 'a suspended business published a lot');
    }

    public function test_the_401_body_is_the_same_everywhere_and_leaks_nothing(): void
    {
        foreach (['/api/user', '/api/user/organizations', '/api/marketplace/seller/home', '/api/instructor/dashboard', '/api/deal-rooms'] as $uri) {
            $res = $this->send('GET', $uri, null);
            $res->assertStatus(401);
            $this->assertSame(self::UNAUTHENTICATED, $res->json(), $uri);
        }
    }

    private function draftLot($org, User $owner): CoffeeListing
    {
        $lot = new CoffeeListing();
        $lot->forceFill([
            'organization_id' => $org->id,
            'seller_id' => $owner->id,
            'title' => 'Matrix lot ' . Str::random(6),
            'slug' => 'matrix-lot-' . Str::lower(Str::random(10)),
            'origin_region' => 'Sidama',
            'processing_method' => 'washed',
            'grade' => 'grade_1',
            'status' => 'draft',
        ])->save();

        return $lot;
    }
}
