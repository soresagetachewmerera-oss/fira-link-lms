<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\SmsEthiopiaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\Fakes\FakeSmsEthiopia;
use Tests\TestCase;

/**
 * Slice 1: sensitive actions need a fresh code or the password, even when signed in.
 */
class FreshAuthSensitiveActionsTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures;

    private FakeSmsEthiopia $sms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sms = new FakeSmsEthiopia();
        $this->app->instance(SmsEthiopiaService::class, $this->sms);
        Storage::fake('verification');
    }

    /** A real personal access token that is NOT fresh (issued long ago). */
    private function staleTokenFor(User $user): string
    {
        $token = $user->createToken('old-device')->plainTextToken;
        $this->withToken($token);

        return $token;
    }

    public function test_sensitive_routes_refuse_a_signed_in_but_stale_session(): void
    {
        $owner = $this->makeUser();
        $owner->forceFill(['password' => Hash::make('Owner123A')])->save();
        $this->makeOrganization($owner);
        $this->staleTokenFor($owner);

        $this->post('/api/marketplace/verification/documents', [
            'document_type' => 'trade_license', 'document_number' => 'N-1', 'expires_at' => now()->addYear()->toDateString(), 'pages' => [$this->realPdf()],
        ], ['Accept' => 'application/json'])->assertStatus(403)->assertJsonPath('code', 'FRESH_AUTH_REQUIRED');

        $this->postJson('/api/user/phone', ['phone' => '0911223344', 'code' => '123456'])
            ->assertStatus(403)->assertJsonPath('code', 'FRESH_AUTH_REQUIRED');

        // Password change: a wrong current password is not fresh authentication.
        $this->postJson('/api/user/password', ['current_password' => 'wrong', 'new_password' => 'NewOwner1A', 'new_password_confirmation' => 'NewOwner1A'])
            ->assertStatus(403)->assertJsonPath('code', 'FRESH_AUTH_REQUIRED');
        // The correct current password in the request counts as the password check.
        $this->postJson('/api/user/password', ['current_password' => 'Owner123A', 'new_password' => 'NewOwner1A', 'new_password_confirmation' => 'NewOwner1A'])
            ->assertOk();
    }

    public function test_step_up_by_sms_code_unlocks_sensitive_routes_for_10_minutes(): void
    {
        $owner = $this->makeUser();
        $owner->forceFill(['phone' => '2519' . random_int(10000000, 99999999), 'phone_verified_at' => now()])->save();
        $this->makeOrganization($owner);
        $this->staleTokenFor($owner);

        $this->postJson('/api/auth/step-up/code')->assertOk()->assertJsonPath('channel', 'sms');
        $code = $this->sms->lastCode($owner->phone);

        $this->postJson('/api/auth/step-up/verify', ['code' => '000000' === $code ? '111111' : '000000'])->assertStatus(422);
        $this->postJson('/api/auth/step-up/verify', ['code' => $code])->assertOk()->assertJsonPath('fresh_for_minutes', 10);

        $this->post('/api/marketplace/verification/documents', [
            'document_type' => 'trade_license', 'document_number' => 'N-1', 'expires_at' => now()->addYear()->toDateString(), 'pages' => [$this->realPdf()],
        ], ['Accept' => 'application/json'])->assertStatus(201);

        $this->travel(11)->minutes();
        $this->post('/api/marketplace/verification/documents', [
            'document_type' => 'tin_certificate', 'document_number' => 'N-1', 'expires_at' => now()->addYear()->toDateString(), 'pages' => [$this->realPdf()],
        ], ['Accept' => 'application/json'])->assertStatus(403)->assertJsonPath('code', 'FRESH_AUTH_REQUIRED');
    }

    public function test_step_up_by_password(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['password' => Hash::make('Passw0rdX')])->save();
        $this->staleTokenFor($user);

        $this->postJson('/api/auth/step-up/verify', ['password' => 'nope'])->assertStatus(422);
        $this->postJson('/api/auth/step-up/verify', ['password' => 'Passw0rdX'])->assertOk();
    }

    public function test_a_fresh_sign_in_token_is_fresh_but_an_older_token_of_the_same_user_is_not(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['email' => 'f_' . Str::random(8) . '@example.test', 'password' => Hash::make('Passw0rdX'), 'phone' => null])->save();
        $old = $user->createToken('old')->plainTextToken;

        $fresh = $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'Passw0rdX'])->assertOk()->json('token');

        $unused = '0911' . random_int(100000, 999999);
        $this->withToken($old)->postJson('/api/user/phone', ['phone' => $unused, 'code' => '123456'])
            ->assertStatus(403)->assertJsonPath('code', 'FRESH_AUTH_REQUIRED');

        // Fresh token reaches the controller (which then checks the code).
        $this->app['auth']->forgetGuards();
        $this->withToken($fresh)->postJson('/api/user/phone', ['phone' => $unused, 'code' => '123456'])
            ->assertStatus(422)->assertJsonPath('code', 'INVALID_CODE');
    }

    public function test_payment_confirmation_requires_fresh_auth_even_for_admin(): void
    {
        [$deal] = $this->makeDealRoom();
        $admin = $this->makeUser('admin');
        $this->staleTokenFor($admin);
        $milestone = $this->milestone($deal, 'payment_escrow');

        $this->postJson("/api/deal-rooms/{$deal->id}/milestones/{$milestone->id}/confirm", ['bank_reference' => 'CBE-1'])
            ->assertStatus(403)->assertJsonPath('code', 'FRESH_AUTH_REQUIRED');
    }

    public function test_withdrawal_bank_details_require_fresh_auth(): void
    {
        $instructor = $this->makeUser('instructor');
        $instructor->forceFill(['is_approved' => true, 'balance' => 500])->save();
        Sanctum::actingAs($instructor);

        $this->postJson('/api/instructor/withdrawals', ['method' => 'bank_transfer', 'amount' => 50, 'method_details' => 'CBE 1000123456'])
            ->assertStatus(403)->assertJsonPath('code', 'FRESH_AUTH_REQUIRED');
        $this->assertEquals(500, (float) $instructor->fresh()->balance);
    }

    public function test_phone_change_needs_code_to_new_number_and_rejects_taken_numbers(): void
    {
        $user = $this->makeUser();
        $other = $this->makeUser();
        $other->forceFill(['phone' => '251911000111', 'phone_verified_at' => now()])->save();
        \App\Services\Auth\FreshAuth::markUser($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/user/phone', ['phone' => '0911000111', 'code' => '123456'])->assertStatus(409)->assertJsonPath('code', 'PHONE_TAKEN');

        $new = '0911' . random_int(100000, 999999);
        $this->postJson('/api/auth/phone/verify/send', ['phone' => $new])->assertOk();
        $code = $this->sms->lastCode($new);

        $this->postJson('/api/user/phone', ['phone' => $new, 'code' => $code])->assertOk();
        $fresh = $user->fresh();
        $this->assertSame(app(SmsEthiopiaService::class)->normalizePhoneNumber($new), $fresh->phone);
        $this->assertNotNull($fresh->phone_verified_at);
    }

    public function test_profile_form_cannot_change_a_phone_without_a_code(): void
    {
        // Auth gating 2026-11-20 (S-6): changing a number is a sensitive change (POST /api/user/phone).
        $user = $this->makeUser();
        $user->forceFill(['email' => 'pp_' . Str::random(8) . '@example.test', 'phone' => '251911555666', 'phone_verified_at' => now()])->save();
        Sanctum::actingAs($user);

        $this->postJson('/api/user/profile', ['name' => $user->name, 'email' => $user->email, 'phone' => '0911777888'])
            ->assertStatus(422)->assertJsonPath('code', 'PHONE_CHANGE_NEEDS_CODE');
        $this->assertSame('251911555666', $user->fresh()->phone);
        $this->assertNotNull($user->fresh()->phone_verified_at);
    }
}
