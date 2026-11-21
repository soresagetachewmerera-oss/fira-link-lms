<?php

namespace Tests\Feature\Auth;

use App\Mail\FiraCodeMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\SmsEthiopiaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Fakes\FakeSmsEthiopia;
use Tests\TestCase;

/**
 * Slice 1: phone sign-in for new and returning users, OTP rules, SMS availability and fallback.
 */
class PhoneSignInTest extends TestCase
{
    use DatabaseTransactions;

    private FakeSmsEthiopia $sms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useSms(new FakeSmsEthiopia());
    }

    private function useSms(FakeSmsEthiopia $sms): void
    {
        $this->sms = $sms;
        $this->app->instance(SmsEthiopiaService::class, $sms);
    }

    /** A random valid Ethiopian mobile number, local format. */
    private function phone(): string
    {
        return '09' . random_int(10000000, 99999999);
    }

    private function userWithPhone(string $phone, bool $verified = true, array $extra = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'p_' . Str::lower(Str::random(10)) . '@example.test',
            'phone' => app(SmsEthiopiaService::class)->normalizePhoneNumber($phone),
            'phone_verified_at' => $verified ? now() : null,
            'role' => 'student',
        ], $extra));
    }

    private function start(string $phone, array $extra = [])
    {
        return $this->postJson('/api/auth/phone/start', array_merge(['phone' => $phone], $extra));
    }

    private function verify(string $phone, ?string $code = null)
    {
        return $this->postJson('/api/auth/phone/verify', ['phone' => $phone, 'code' => $code ?? $this->sms->lastCode($phone)]);
    }

    public function test_new_user_signs_up_with_phone_code_then_full_name(): void
    {
        $phone = $this->phone();

        $this->start($phone)->assertOk()->assertJsonPath('code_length', 6)->assertJsonStructure(['phone_masked', 'cooldown_seconds', 'expiry_minutes']);
        $code = $this->sms->lastCode($phone);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $ticket = $this->verify($phone)->assertOk()->assertJsonPath('status', 'new_user')->json('signup_ticket');

        $this->postJson('/api/auth/phone/complete', ['signup_ticket' => $ticket, 'full_name' => ''])->assertStatus(422);

        $res = $this->postJson('/api/auth/phone/complete', ['signup_ticket' => $ticket, 'full_name' => '  Abebe   Kebede '])
            ->assertOk()
            ->assertJsonPath('status', 'signed_in')
            ->assertJsonPath('is_new_user', true);
        $this->assertNotEmpty($res->json('token'));

        $user = User::where('phone', app(SmsEthiopiaService::class)->normalizePhoneNumber($phone))->firstOrFail();
        $this->assertSame('Abebe Kebede', $user->name);
        $this->assertNull($user->email);
        $this->assertNotNull($user->phone_verified_at);

        // The ticket works once.
        $this->postJson('/api/auth/phone/complete', ['signup_ticket' => $ticket, 'full_name' => 'Again'])->assertStatus(422);
    }

    public function test_returning_user_with_verified_unique_phone_signs_in(): void
    {
        $phone = $this->phone();
        $user = $this->userWithPhone($phone);

        $this->start($phone)->assertOk();
        $this->verify($phone)
            ->assertOk()
            ->assertJsonPath('status', 'signed_in')
            ->assertJsonPath('is_new_user', false)
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_shared_phone_is_blocked_and_logged_never_merged(): void
    {
        $phone = $this->phone();
        $a = $this->userWithPhone($phone);
        $b = $this->userWithPhone($phone);

        $this->start($phone)->assertOk();
        $this->verify($phone)->assertStatus(409)->assertJsonPath('code', 'PHONE_SHARED');

        $log = AuditLog::where('action', 'phone_signin_blocked_shared_phone')->latest('id')->firstOrFail();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $log->after_state['user_ids']);
        $this->assertSame(2, User::whereIn('id', [$a->id, $b->id])->count());
    }

    public function test_account_that_never_verified_the_phone_cannot_use_it_to_sign_in(): void
    {
        $phone = $this->phone();
        $this->userWithPhone($phone, verified: false);

        $this->start($phone)->assertOk();
        $this->verify($phone)->assertStatus(409)->assertJsonPath('code', 'PHONE_NOT_VERIFIED');
    }

    public function test_code_is_single_use_and_expires_after_10_minutes(): void
    {
        $phone = $this->phone();
        $this->userWithPhone($phone);

        $this->start($phone);
        $code = $this->sms->lastCode($phone);
        $this->verify($phone, $code)->assertOk();
        $this->verify($phone, $code)->assertStatus(422)->assertJsonPath('code', 'CODE_EXPIRED');

        $this->travel(2)->minutes();
        $this->start($phone)->assertOk();
        $this->travel(11)->minutes();
        $this->verify($phone)->assertStatus(422)->assertJsonPath('code', 'CODE_EXPIRED');
    }

    public function test_wrong_codes_burn_the_code_and_three_burns_lock_the_phone(): void
    {
        $phone = $this->phone();

        for ($burn = 1; $burn <= 3; $burn++) {
            $this->travel(2)->minutes(); // past the resend cooldown
            $this->start($phone)->assertOk();
            $real = $this->sms->lastCode($phone);
            $wrong = $real === '000000' ? '111111' : '000000';

            $this->verify($phone, $wrong)->assertStatus(422)->assertJsonPath('code', 'INVALID_CODE')->assertJsonPath('attempts_remaining', 4);
            for ($i = 0; $i < 4; $i++) {
                $this->verify($phone, $wrong);
            }
            // Sixth try exceeds the limit: the code is dead even if it is now the right one.
            $this->verify($phone, $real)->assertStatus(422)->assertJsonPath('code', 'CODE_INVALIDATED');
        }

        $this->start($phone)->assertStatus(423)->assertJsonPath('code', 'LOCKED');
        $this->verify($phone, '123456')->assertStatus(423);

        $this->travel(31)->minutes();
        $this->start($phone)->assertOk();
    }

    public function test_per_phone_hourly_limit_and_resend_cooldown(): void
    {
        $phone = $this->phone();

        $this->start($phone)->assertOk();
        $this->start($phone)->assertStatus(429)->assertJsonPath('code', 'COOLDOWN_ACTIVE');

        for ($i = 2; $i <= 5; $i++) {
            $this->travel(61)->seconds();
            $this->start($phone)->assertOk();
        }
        $this->travel(61)->seconds();
        $this->start($phone)->assertStatus(429)->assertJsonPath('code', 'TOO_MANY_REQUESTS');
    }

    public function test_per_ip_hourly_limit(): void
    {
        // Spread out so the route group's own 10-per-minute throttle is not what trips.
        for ($i = 0; $i < 20; $i++) {
            $this->travel(7)->seconds();
            $this->start($this->phone())->assertOk();
        }
        $this->travel(7)->seconds();
        $this->start($this->phone())->assertStatus(429)->assertJsonPath('code', 'TOO_MANY_REQUESTS');
    }

    public function test_production_without_sms_key_refuses_phone_sign_in(): void
    {
        $this->useSms(new FakeSmsEthiopia(configured: false));
        $this->app['env'] = 'production';
        config(['services.sms_ethiopia.test_code' => '246810']); // must be ignored outside local

        $this->start($this->phone())->assertStatus(503)->assertJsonPath('code', 'SMS_UNAVAILABLE');
    }

    public function test_fixed_test_code_works_only_when_app_env_is_local(): void
    {
        $this->useSms(new FakeSmsEthiopia(configured: false));
        config(['services.sms_ethiopia.test_code' => '246810']);

        // testing (not local): no test code, and without SMS the step is refused.
        $phone = $this->phone();
        $this->start($phone)->assertStatus(503);

        $this->app['env'] = 'local';
        $this->start($phone)->assertOk();
        $this->assertSame(0, $this->sms->sent, 'Nothing is sent in local test-code mode.');
        $this->verify($phone, '246810')->assertOk()->assertJsonPath('status', 'new_user');
    }

    public function test_email_fallback_when_sms_delivery_fails(): void
    {
        Mail::fake();
        $this->useSms(new FakeSmsEthiopia(configured: true, delivers: false));
        $phone = $this->phone();
        $user = $this->userWithPhone($phone);

        $this->start($phone)->assertStatus(502)->assertJsonPath('code', 'SMS_FAILED')->assertJsonPath('email_fallback_available', true);

        $expected = $this->postJson('/api/auth/phone/email-fallback', ['phone' => $phone])->assertOk()->json('message');

        $mailed = null;
        Mail::assertSent(FiraCodeMail::class, function (FiraCodeMail $mail) use ($user, &$mailed) {
            $mailed = $mail->code;
            return $mail->hasTo($user->email);
        });

        $this->verify($phone, $mailed)->assertOk()->assertJsonPath('user.id', $user->id);

        // Unknown number: same answer, nothing sent.
        Mail::fake();
        $this->postJson('/api/auth/phone/email-fallback', ['phone' => $this->phone()])
            ->assertOk()
            ->assertJsonPath('message', $expected);
        Mail::assertNothingSent();
    }

    public function test_only_ethiopian_numbers_receive_sms(): void
    {
        $this->start('2025550123', ['country_code' => '+1'])->assertStatus(422)->assertJsonPath('code', 'SMS_COUNTRY_UNSUPPORTED');
        $this->start('12345')->assertStatus(422)->assertJsonPath('code', 'INVALID_PHONE');
    }
}
