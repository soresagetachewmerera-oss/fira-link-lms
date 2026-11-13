<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\SmsEthiopiaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Fakes\FakeSmsEthiopia;
use Tests\TestCase;

/**
 * Slice 1: the password backdoor and the @firalink.com / @eduex.com alias are gone;
 * must-reset accounts cannot sign in with a password; phone verification is never skipped
 * in production.
 */
class LoginHardeningTest extends TestCase
{
    use DatabaseTransactions;

    private function user(string $email, string $password, array $extra = []): User
    {
        return User::factory()->create(array_merge(['email' => $email, 'password' => Hash::make($password), 'phone' => null], $extra));
    }

    private function login(string $email, string $password)
    {
        return $this->postJson('/api/auth/login', ['email' => $email, 'password' => $password]);
    }

    public function test_weak_default_passwords_are_no_longer_interchangeable(): void
    {
        $a = $this->user('a_' . Str::random(8) . '@example.test', '12345678');
        $b = $this->user('b_' . Str::random(8) . '@example.test', 'password');

        $this->login($a->email, 'password')->assertStatus(401);
        $this->login($b->email, '12345678')->assertStatus(401);
        $this->login($a->email, '12345678')->assertOk();
    }

    public function test_alias_domains_do_not_sign_in_to_each_others_accounts(): void
    {
        $local = 'alias_' . Str::lower(Str::random(8));
        $this->user($local . '@eduex.com', 'Secret123A');

        $this->login($local . '@firalink.com', 'Secret123A')->assertStatus(401);

        // Web login too.
        $this->post('/login', ['email' => $local . '@firalink.com', 'password' => 'Secret123A'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_must_reset_account_gets_no_token_by_password(): void
    {
        $user = $this->user('m_' . Str::random(8) . '@example.test', '12345678');
        $user->forceFill(['must_reset_password' => true])->save();

        $this->login($user->email, '12345678')->assertStatus(403)->assertJsonPath('code', 'PASSWORD_RESET_REQUIRED');
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_must_reset_account_signing_in_by_phone_goes_to_new_password(): void
    {
        $sms = new FakeSmsEthiopia();
        $this->app->instance(SmsEthiopiaService::class, $sms);
        $phone = '2519' . random_int(10000000, 99999999);
        $user = $this->user('mp_' . Str::random(8) . '@example.test', 'password', ['phone' => $phone, 'phone_verified_at' => now()]);
        $user->forceFill(['must_reset_password' => true])->save();

        $this->postJson('/api/auth/phone/start', ['phone' => $phone])->assertOk();
        $ticket = $this->postJson('/api/auth/phone/verify', ['phone' => $phone, 'code' => $sms->lastCode($phone)])
            ->assertOk()->assertJsonPath('status', 'password_reset_required')->json('reset_ticket');

        $this->postJson('/api/auth/password/reset', ['reset_ticket' => $ticket, 'password' => 'Fresh123A', 'password_confirmation' => 'Fresh123A'])->assertOk();
        $this->assertFalse($user->fresh()->must_reset_password);
        $this->login($user->email, 'Fresh123A')->assertOk();
    }

    public function test_unverified_phone_is_not_skipped_in_production_without_sms(): void
    {
        $this->app->instance(SmsEthiopiaService::class, new FakeSmsEthiopia(configured: false));
        $this->app['env'] = 'production';
        $user = $this->user('u_' . Str::random(8) . '@example.test', 'Secret123A', ['phone' => '2519' . random_int(10000000, 99999999), 'phone_verified_at' => null]);

        $this->login($user->email, 'Secret123A')->assertStatus(503)->assertJsonPath('code', 'SMS_UNAVAILABLE');
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_otp_verify_does_not_reveal_unknown_accounts(): void
    {
        $user = $this->user('o_' . Str::random(8) . '@example.test', 'Secret123A');

        $known = $this->postJson('/api/auth/login/otp/verify', ['email' => $user->email, 'code' => '000000'])->assertStatus(422)->json();
        $unknown = $this->postJson('/api/auth/login/otp/verify', ['email' => 'none_' . Str::random(6) . '@example.test', 'code' => '000000'])->assertStatus(422)->json();
        $this->assertSame($known, $unknown);
    }
}
