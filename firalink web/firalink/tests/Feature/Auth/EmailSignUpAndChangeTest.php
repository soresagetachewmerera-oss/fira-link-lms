<?php

namespace Tests\Feature\Auth;

use App\Mail\LoginOtpMail;
use App\Models\User;
use App\Services\Auth\FreshAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\TestCase;

/**
 * Pre-slice-2 fixes: email-only sign-up (no phone needed), email change as a sensitive
 * action, admin password change needs the current password.
 */
class EmailSignUpAndChangeTest extends TestCase
{
    use DatabaseTransactions, ApprovesBusinesses;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function mailedCodeTo(string $email): ?string
    {
        $code = null;
        Mail::assertSent(LoginOtpMail::class, function (LoginOtpMail $m) use ($email, &$code) {
            if ($m->hasTo($email)) {
                $code = $m->code;
                return true;
            }
            return false;
        });

        return $code;
    }

    private function start(string $email, array $extra = [])
    {
        return $this->postJson('/api/auth/email/register/start', array_merge([
            'full_name' => 'Anna Schmidt',
            'email' => $email,
            'password' => 'Hamburg2026',
            'password_confirmation' => 'Hamburg2026',
            'terms' => true,
        ], $extra));
    }

    public function test_person_without_phone_signs_up_with_email_and_emailed_code(): void
    {
        $email = 'buyer_' . Str::lower(Str::random(8)) . '@roastery.example';

        $this->start($email)->assertOk()->assertJsonPath('code_length', 6);
        $this->assertNull(User::where('email', $email)->first(), 'No account before the code is confirmed.');

        $code = $this->mailedCodeTo($email);
        $res = $this->postJson('/api/auth/email/register/verify', ['email' => $email, 'code' => $code])
            ->assertOk()->assertJsonPath('is_new_user', true);
        $this->assertNotEmpty($res->json('token'));

        $user = User::where('email', $email)->firstOrFail();
        $this->assertNull($user->phone);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('Hamburg2026', $user->password));

        // Single use.
        $this->postJson('/api/auth/email/register/verify', ['email' => $email, 'code' => $code])->assertStatus(422);

        // And the new account signs in with email and password.
        $this->postJson('/api/auth/login', ['email' => $email, 'password' => 'Hamburg2026'])->assertOk();
    }

    public function test_sign_up_enforces_password_rules(): void
    {
        $this->start('weak_' . Str::random(6) . '@example.test', ['password' => 'password', 'password_confirmation' => 'password'])
            ->assertStatus(422);
        Mail::assertNothingSent();
    }

    public function test_sign_up_answer_is_identical_for_existing_email_and_sends_no_code(): void
    {
        $existing = User::factory()->create(['email' => 'taken_' . Str::lower(Str::random(8)) . '@example.test']);

        $fresh = $this->start('free_' . Str::lower(Str::random(8)) . '@example.test')->json();
        $taken = $this->start($existing->email)->json();

        $this->assertSame($fresh, $taken);
        Mail::assertNotSent(LoginOtpMail::class, fn (LoginOtpMail $m) => $m->hasTo($existing->email));
    }

    public function test_sign_up_code_expires_and_locks_like_phone_codes(): void
    {
        $email = 'lock_' . Str::lower(Str::random(8)) . '@example.test';
        $this->start($email);
        $code = $this->mailedCodeTo($email);

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/auth/email/register/verify', ['email' => $email, 'code' => $code === '000000' ? '111111' : '000000'])->assertStatus(422);
        }
        // The real code is dead after too many wrong tries.
        $this->postJson('/api/auth/email/register/verify', ['email' => $email, 'code' => $code])->assertStatus(422);

        Mail::fake();
        $this->travel(2)->minutes();
        $this->start($email);
        $second = $this->mailedCodeTo($email);
        $this->travel(11)->minutes();
        $this->postJson('/api/auth/email/register/verify', ['email' => $email, 'code' => $second])->assertStatus(422);
        $this->assertNull(User::where('email', $email)->first());
    }

    public function test_email_change_needs_fresh_auth_and_a_code_sent_to_the_new_address(): void
    {
        $user = User::factory()->create(['email' => 'old_' . Str::lower(Str::random(8)) . '@example.test', 'phone' => null]);
        $new = 'new_' . Str::lower(Str::random(8)) . '@example.test';

        // Stale session: refused.
        $stale = $user->createToken('old')->plainTextToken;
        $this->withToken($stale)->postJson('/api/user/email/code', ['email' => $new])
            ->assertStatus(403)->assertJsonPath('code', 'FRESH_AUTH_REQUIRED');

        // Fresh: code goes to the NEW address only.
        $this->app['auth']->forgetGuards();
        $this->withToken('')->flushHeaders();
        FreshAuth::markUser($user);
        Sanctum::actingAs($user);

        $this->postJson('/api/user/email/code', ['email' => $new])->assertOk();
        $code = $this->mailedCodeTo($new);
        Mail::assertNotSent(LoginOtpMail::class, fn (LoginOtpMail $m) => $m->hasTo($user->email));

        $this->postJson('/api/user/email', ['email' => $new, 'code' => $code === '000000' ? '111111' : '000000'])->assertStatus(422);
        $this->postJson('/api/user/email', ['email' => $new, 'code' => $code])->assertOk();

        $this->assertSame($new, $user->fresh()->email);
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_email_change_to_a_taken_address_never_succeeds_and_looks_the_same(): void
    {
        $user = User::factory()->create(['email' => 'me_' . Str::lower(Str::random(8)) . '@example.test']);
        $other = User::factory()->create(['email' => 'other_' . Str::lower(Str::random(8)) . '@example.test']);
        FreshAuth::markUser($user);
        Sanctum::actingAs($user);

        $taken = $this->postJson('/api/user/email/code', ['email' => $other->email])->assertOk()->json();
        $free = $this->postJson('/api/user/email/code', ['email' => 'free_' . Str::lower(Str::random(8)) . '@example.test'])->json();
        $this->assertSame($taken['message'], $free['message']);
        Mail::assertNotSent(LoginOtpMail::class, fn (LoginOtpMail $m) => $m->hasTo($other->email));

        $this->postJson('/api/user/email', ['email' => $other->email, 'code' => '123456'])->assertStatus(422);
        $this->assertNotSame($other->email, $user->fresh()->email);
    }

    public function test_profile_form_cannot_change_email_directly(): void
    {
        $user = User::factory()->create(['email' => 'p_' . Str::lower(Str::random(8)) . '@example.test']);
        Sanctum::actingAs($user);

        $this->postJson('/api/user/profile', ['name' => 'Same Person', 'email' => 'hijack_' . Str::random(6) . '@example.test'])
            ->assertStatus(422)->assertJsonPath('code', 'EMAIL_CHANGE_NEEDS_CODE');

        // Unchanged email (or none) still saves the rest of the profile.
        $this->postJson('/api/user/profile', ['name' => 'Same Person', 'email' => $user->email])->assertOk();
        $this->assertSame('Same Person', $user->fresh()->name);
    }

    public function test_admin_profile_password_change_requires_current_password(): void
    {
        $admin = User::factory()->create([
            'email' => 'adm_' . Str::lower(Str::random(8)) . '@example.test',
            'role' => 'admin',
            'password' => Hash::make('AdminOld1'),
        ]);
        $base = ['name' => $admin->name, 'email' => $admin->email, 'password' => 'AdminNew1', 'password_confirmation' => 'AdminNew1'];

        $this->actingAsStaff($admin)->put(route('admin.profile.update'), $base)->assertSessionHasErrors('current_password');
        $this->actingAsStaff($admin)->put(route('admin.profile.update'), $base + ['current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('AdminOld1', $admin->fresh()->password));

        $this->actingAsStaff($admin)->put(route('admin.profile.update'), $base + ['current_password' => 'AdminOld1'])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('AdminNew1', $admin->fresh()->password));
    }

    public function test_admin_profile_email_change_requires_current_password(): void
    {
        $admin = User::factory()->create([
            'email' => 'adm2_' . Str::lower(Str::random(8)) . '@example.test',
            'role' => 'admin',
            'password' => Hash::make('AdminOld1'),
        ]);
        $new = 'adm2new_' . Str::lower(Str::random(8)) . '@example.test';

        $this->actingAsStaff($admin)->put(route('admin.profile.update'), ['name' => $admin->name, 'email' => $new])
            ->assertSessionHasErrors('current_password');
        $this->assertNotSame($new, $admin->fresh()->email);
    }
}
