<?php

namespace Tests\Feature\Auth;

use App\Mail\FiraCodeMail;
use App\Models\User;
use App\Services\SmsEthiopiaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Fakes\FakeSmsEthiopia;
use Tests\TestCase;

/**
 * Slice 1: forgot password by phone or email, reset code, password rules, sign out everywhere.
 */
class PasswordResetFlowTest extends TestCase
{
    use DatabaseTransactions;

    private FakeSmsEthiopia $sms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sms = new FakeSmsEthiopia();
        $this->app->instance(SmsEthiopiaService::class, $this->sms);
        Mail::fake();
    }

    private function user(array $extra = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'r_' . Str::lower(Str::random(10)) . '@example.test',
            'password' => Hash::make('OldPassw0rd'),
            'phone' => '2519' . random_int(10000000, 99999999),
            'phone_verified_at' => now(),
        ], $extra));
    }

    private function mailedCode(User $user): string
    {
        $code = null;
        Mail::assertSent(FiraCodeMail::class, function (FiraCodeMail $m) use ($user, &$code) {
            if ($m->hasTo($user->email)) {
                $code = $m->code;
                return true;
            }
            return false;
        });

        return $code;
    }

    public function test_forgot_gives_identical_answers_for_existing_and_unknown_accounts(): void
    {
        $user = $this->user();

        $known = $this->postJson('/api/auth/password/forgot', ['channel' => 'email', 'email' => $user->email])->assertOk()->json();
        $unknown = $this->postJson('/api/auth/password/forgot', ['channel' => 'email', 'email' => 'nobody_' . Str::random(8) . '@example.test'])->assertOk()->json();
        $this->assertSame($known, $unknown);

        $knownPhone = $this->postJson('/api/auth/password/forgot', ['channel' => 'phone', 'phone' => '0' . substr($user->phone, 3)])->assertOk()->json();
        $unknownPhone = $this->postJson('/api/auth/password/forgot', ['channel' => 'phone', 'phone' => '0911' . random_int(100000, 999999)])->assertOk()->json();
        $this->assertSame($knownPhone, $unknownPhone);

        // Only the real account got codes.
        Mail::assertSent(FiraCodeMail::class, 1);
        $this->assertSame(1, $this->sms->sent);
    }

    public function test_wrong_code_for_unknown_and_known_account_look_the_same(): void
    {
        $user = $this->user();
        $this->postJson('/api/auth/password/forgot', ['channel' => 'email', 'email' => $user->email]);

        $known = $this->postJson('/api/auth/password/verify-code', ['channel' => 'email', 'email' => $user->email, 'code' => '000001'])->assertStatus(422)->json();
        $unknown = $this->postJson('/api/auth/password/verify-code', ['channel' => 'email', 'email' => 'x_' . Str::random(6) . '@example.test', 'code' => '000001'])->assertStatus(422)->json();
        $this->assertSame($known, $unknown);
    }

    public function test_reset_by_email_code_is_single_use_enforces_rules_and_signs_out_everywhere(): void
    {
        $user = $this->user();
        $user->createToken('phone');
        $user->createToken('tablet');
        DB::table('sessions')->insert([
            'id' => Str::random(40), 'user_id' => $user->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'test', 'payload' => '', 'last_activity' => now()->timestamp,
        ]);

        $this->postJson('/api/auth/password/forgot', ['channel' => 'email', 'email' => $user->email])->assertOk();
        $code = $this->mailedCode($user);

        $ticket = $this->postJson('/api/auth/password/verify-code', ['channel' => 'email', 'email' => $user->email, 'code' => $code])
            ->assertOk()->json('reset_ticket');

        // The code cannot be used twice.
        $this->postJson('/api/auth/password/verify-code', ['channel' => 'email', 'email' => $user->email, 'code' => $code])->assertStatus(422);

        // Rules: 8+ characters, a number, a capital letter.
        foreach (['short1A', 'nocapital1', 'NoNumberHere'] as $weak) {
            $this->postJson('/api/auth/password/reset', ['reset_ticket' => $ticket, 'password' => $weak, 'password_confirmation' => $weak])
                ->assertStatus(422);
        }

        $this->postJson('/api/auth/password/reset', ['reset_ticket' => $ticket, 'password' => 'NewPassw0rd', 'password_confirmation' => 'NewPassw0rd'])
            ->assertOk()->assertJsonPath('signed_out_everywhere', true);

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassw0rd', $user->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());

        // The ticket works once.
        $this->postJson('/api/auth/password/reset', ['reset_ticket' => $ticket, 'password' => 'Another1A', 'password_confirmation' => 'Another1A'])
            ->assertStatus(422)->assertJsonPath('code', 'TICKET_EXPIRED');
    }

    public function test_reset_by_phone_code_and_code_valid_only_10_minutes(): void
    {
        $user = $this->user();
        $local = '0' . substr($user->phone, 3);

        $this->postJson('/api/auth/password/forgot', ['channel' => 'phone', 'phone' => $local])->assertOk();
        $code = $this->sms->lastCode($user->phone);

        $this->travel(11)->minutes();
        $this->postJson('/api/auth/password/verify-code', ['channel' => 'phone', 'phone' => $local, 'code' => $code])->assertStatus(422);

        $this->postJson('/api/auth/password/forgot', ['channel' => 'phone', 'phone' => $local])->assertOk();
        $this->postJson('/api/auth/password/verify-code', ['channel' => 'phone', 'phone' => $local, 'code' => $this->sms->lastCode($user->phone)])
            ->assertOk()->assertJsonStructure(['reset_ticket']);
    }

    public function test_reset_ticket_expires_after_10_minutes(): void
    {
        $user = $this->user();
        $this->postJson('/api/auth/password/forgot', ['channel' => 'email', 'email' => $user->email]);
        $ticket = $this->postJson('/api/auth/password/verify-code', ['channel' => 'email', 'email' => $user->email, 'code' => $this->mailedCode($user)])->json('reset_ticket');

        $this->travel(11)->minutes();
        $this->postJson('/api/auth/password/reset', ['reset_ticket' => $ticket, 'password' => 'NewPassw0rd', 'password_confirmation' => 'NewPassw0rd'])
            ->assertStatus(422);
    }

    public function test_legacy_forgot_endpoint_no_longer_reveals_accounts(): void
    {
        $this->postJson('/api/auth/password/forgot/otp', ['phone' => '0911' . random_int(100000, 999999)])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_web_forgot_password_shows_the_same_message_for_unknown_email(): void
    {
        $user = $this->user();

        $known = $this->post(route('password.email'), ['email' => $user->email]);
        $unknown = $this->post(route('password.email'), ['email' => 'ghost_' . Str::random(6) . '@example.test']);

        $known->assertSessionHasNoErrors();
        $unknown->assertSessionHasNoErrors();
        $this->assertSame(session('status'), 'If an account matches that email, a reset link is on its way.');
    }
}
