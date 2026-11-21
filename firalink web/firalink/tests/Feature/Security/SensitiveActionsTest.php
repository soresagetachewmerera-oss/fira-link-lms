<?php

namespace Tests\Feature\Security;

use App\Mail\FiraCodeMail;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\User;
use App\Notifications\SecurityNotice;
use App\Services\Auth\FreshAuth;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Auth gating 2026-11-20, Phase 1: class D actions (fresh authentication, notices, audit), denied
 * attempts in the audit log, the staff "confirm it is you" page, and the closed public leaks.
 */
class SensitiveActionsTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    private function userWithPassword(string $password = 'Owner123A'): User
    {
        $user = $this->makeUser();
        $user->forceFill(['password' => Hash::make($password)])->save();

        return $user;
    }

    public function test_delete_account_request_needs_fresh_auth_and_tells_the_user(): void
    {
        Notification::fake();
        $user = $this->userWithPassword();
        $this->withToken($user->createToken('old-device')->plainTextToken);

        $this->postJson('/api/user/delete-request')->assertStatus(403)->assertJsonPath('code', 'FRESH_AUTH_REQUIRED');
        $this->assertDatabaseMissing('account_deletion_requests', ['user_id' => $user->id]);

        $this->postJson('/api/user/delete-request', ['current_password' => 'Owner123A'])->assertOk();
        $this->assertDatabaseHas('account_deletion_requests', ['user_id' => $user->id, 'status' => 'pending']);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'security.deletion_requested']);
        Notification::assertSentTo($user, SecurityNotice::class, fn (SecurityNotice $n) => $n->event === 'deletion_requested');
    }

    public function test_password_change_signs_out_other_devices_tells_the_user_and_is_audited(): void
    {
        Notification::fake();
        $user = $this->userWithPassword();
        $other = $user->createToken('other-phone')->plainTextToken;
        $this->withToken($user->createToken('this-phone')->plainTextToken);

        $this->postJson('/api/user/password', [
            'current_password' => 'Owner123A', 'new_password' => 'NewOwner1A', 'new_password_confirmation' => 'NewOwner1A',
        ])->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($other)->getJson('/api/user')->assertStatus(401);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'security.password_changed']);
        Notification::assertSentTo($user, SecurityNotice::class, fn (SecurityNotice $n) => $n->event === 'password_changed');
        $this->assertNotContains('NewOwner1A', [json_encode(AuditLog::where('user_id', $user->id)->get()->toArray())]);
    }

    public function test_email_change_tells_the_old_address_too(): void
    {
        // Mail is faked; notifications really run (the code email is a catalogue notification).
        Mail::fake();
        $sent = [];
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Notifications\Events\NotificationSent::class, function ($e) use (&$sent) {
            $sent[] = $e;
        });
        $user = User::factory()->create(['email' => 'old_' . Str::lower(Str::random(8)) . '@example.test', 'phone' => null]);
        $old = $user->email;
        $new = 'new_' . Str::lower(Str::random(8)) . '@example.test';
        Sanctum::actingAs($user);
        FreshAuth::markUser($user);

        $this->postJson('/api/user/email/code', ['email' => $new])->assertOk();
        $code = null;
        Mail::assertSent(FiraCodeMail::class, function (FiraCodeMail $m) use ($new, &$code) {
            return $m->hasTo($new) && ($code = $m->code);
        });
        $this->postJson('/api/user/email', ['email' => $new, 'code' => $code])->assertOk();

        $notices = collect($sent)->filter(fn ($e) => $e->notification instanceof SecurityNotice && $e->notification->event === 'email_changed');
        $this->assertTrue($notices->contains(fn ($e) => $e->notifiable instanceof User && $e->notifiable->id === $user->id), 'The person is told.');
        $this->assertTrue($notices->contains(fn ($e) => $e->notifiable instanceof AnonymousNotifiable && ($e->notifiable->routes['mail'] ?? null) === $old), 'The old address is told.');
    }

    public function test_the_profile_form_cannot_change_or_remove_a_phone_number(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['phone' => '251911' . random_int(100000, 999999), 'phone_verified_at' => now()])->save();
        Sanctum::actingAs($user);

        $this->postJson('/api/user/profile', ['name' => 'New Name', 'phone' => '0922' . random_int(100000, 999999)])
            ->assertStatus(422)->assertJsonPath('code', 'PHONE_CHANGE_NEEDS_CODE');
        $this->postJson('/api/user/profile', ['name' => 'New Name', 'phone' => ''])
            ->assertStatus(422)->assertJsonPath('code', 'PHONE_CHANGE_NEEDS_CODE');
        $this->assertNotNull($user->fresh()->phone_verified_at);

        // Sending the same number (the app always sends it) still saves the rest.
        $this->postJson('/api/user/profile', ['name' => 'New Name', 'phone' => $user->phone])->assertOk();
        $this->assertSame('New Name', $user->fresh()->name);
    }

    public function test_a_first_phone_number_can_be_added_and_a_taken_one_gives_a_neutral_answer(): void
    {
        $taken = $this->makeUser();
        $taken->forceFill(['phone' => '251933' . random_int(100000, 999999)])->save();

        $user = $this->makeUser();
        $user->forceFill(['phone' => null])->save();
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/user/profile', ['name' => 'A', 'phone' => $taken->phone])->assertStatus(422);
        $res->assertJsonPath('code', 'PHONE_UNAVAILABLE');
        $this->assertStringNotContainsString('another account', (string) $res->getContent());

        $this->postJson('/api/user/profile', ['name' => 'A', 'phone' => '0944' . random_int(100000, 999999)])->assertOk();
        $this->assertNull($user->fresh()->phone_verified_at, 'A number added on the profile is not verified.');
    }

    public function test_denied_attempts_are_audited_once_without_secrets(): void
    {
        $user = $this->makeUser();
        $leftover = $user->createToken('signed-out')->plainTextToken;
        $user->tokens()->delete();

        for ($i = 0; $i < 3; $i++) {
            $this->app['auth']->forgetGuards();
            $this->withToken($leftover)->getJson('/api/user/organizations')->assertStatus(401);
        }
        $rows = AuditLog::where('action', 'security.token_rejected')->where('after_state->route', 'api/user/organizations')->get();
        $this->assertCount(1, $rows, 'Retries inside the window are not written again.');
        $this->assertStringNotContainsString(explode('|', $leftover)[1], json_encode($rows->toArray()));

        // A real 403: a student calling an instructor route.
        $this->app['auth']->forgetGuards();
        $this->withToken($user->createToken('app')->plainTextToken)->getJson('/api/instructor/dashboard')->assertStatus(403);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'security.denied']);

        // A guest without any token is not logged.
        $before = AuditLog::count();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->getJson('/api/user')->assertStatus(401);
        $this->assertSame($before, AuditLog::count());
    }

    public function test_staff_money_and_settings_actions_need_a_fresh_confirmation(): void
    {
        $admin = $this->makeUser('admin');
        $admin->forceFill(['password' => Hash::make('Admin123A')])->save();
        $this->actingAsStaff($admin);

        // Not fresh: sent to the confirm page, nothing happens.
        $this->from('/admin/payments')->post('/admin/payments/999999/approve')->assertRedirect(route('staff.confirm.show'));
        $this->get(route('staff.confirm.show'))->assertOk()->assertSee(__('security.confirm.title'));

        $this->post(route('staff.confirm.store'), ['password' => 'wrong'])->assertSessionHasErrors('code');
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'security.staff_confirm_failed']);

        $this->post(route('staff.confirm.store'), ['password' => 'Admin123A'])->assertRedirect('/admin/payments');
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'action' => 'security.staff_confirmed']);

        // Fresh now: the action runs (unknown payment, so 404), not the confirm page.
        $res = $this->post('/admin/payments/999999/approve');
        $this->assertNotSame(route('staff.confirm.show'), $res->headers->get('Location'));
    }

    public function test_a_person_who_is_not_staff_cannot_use_the_confirm_page(): void
    {
        $this->actingAs($this->makeUser());
        $this->get('/staff/confirm')->assertStatus(403);
    }

    public function test_seller_plan_payment_check_is_owner_only_and_never_returns_the_payment_row(): void
    {
        $payer = $this->makeUser();
        $payment = Payment::create([
            'user_id' => $payer->id, 'payment_method' => 'chapa', 'amount' => 1500, 'status' => 'completed',
            'chapa_tx_ref' => 'MKT-SUB-' . Str::upper(Str::random(24)),
        ]);

        $this->postJson('/api/marketplace/subscribe/chapa/verify', ['tx_ref' => $payment->chapa_tx_ref])->assertStatus(401);

        Sanctum::actingAs($this->makeUser());
        $this->postJson('/api/marketplace/subscribe/chapa/verify', ['tx_ref' => $payment->chapa_tx_ref])->assertNotFound();

        // Chapa's callback (no user): the status only.
        $this->app['auth']->forgetGuards();
        $res = $this->getJson('/api/marketplace/subscribe/chapa/callback?tx_ref=' . $payment->chapa_tx_ref)->assertOk();
        $this->assertSame(['status' => 'completed'], $res->json('data'));
        $this->assertStringNotContainsString((string) $payer->id . ',', json_encode($res->json('data')));

        Sanctum::actingAs($payer);
        $own = $this->postJson('/api/marketplace/subscribe/chapa/verify', ['tx_ref' => $payment->chapa_tx_ref])->assertOk();
        $this->assertArrayNotHasKey('amount', $own->json('data'));
        $this->assertArrayNotHasKey('user_id', $own->json('data'));
    }

    public function test_new_plan_payment_references_are_random(): void
    {
        $source = file_get_contents(app_path('Services/MarketplaceSubscriptionService.php'));
        $this->assertStringNotContainsString("'MKT-SUB-' . \$user->id", $source);
        $this->assertStringContainsString('Str::random(24)', $source);
    }

    public function test_payment_json_hides_gateway_secrets_and_the_receipt_path(): void
    {
        $p = new Payment(['stripe_client_secret' => 'pi_secret', 'receipt_file' => 'receipts/x.pdf', 'amount' => 1]);
        $json = $p->toArray();
        $this->assertArrayNotHasKey('stripe_client_secret', $json);
        $this->assertArrayNotHasKey('receipt_file', $json);
    }

    public function test_web_tour_booking_sends_visitors_to_the_app_and_creates_nothing(): void
    {
        $tour = new Tour();
        $tour->forceFill([
            'title' => 'Guji harvest', 'slug' => 'tr-' . Str::lower(Str::random(10)), 'destination' => 'Guji',
            'duration_days' => 3, 'total_seats' => 4, 'price' => 0, 'currency' => 'ETB', 'is_published' => true,
        ])->save();

        $this->get("/tours/{$tour->slug}/book")->assertRedirect(route('tours.show', $tour->slug));
        $this->post("/tours/{$tour->slug}/book", [
            'first_name' => 'A', 'last_name' => 'B', 'email' => 'a@example.test', 'phone' => '0911000000', 'participants' => 1, 'payment_method' => 'free',
        ])->assertRedirect(route('tours.show', $tour->slug));
        $this->assertDatabaseMissing('tour_bookings', ['tour_id' => $tour->id]);
    }

    public function test_booking_confirmation_pages_are_for_the_person_who_booked(): void
    {
        $tour = new Tour();
        $tour->forceFill(['title' => 'Sidama', 'slug' => 'tr-' . Str::lower(Str::random(10)), 'destination' => 'Sidama', 'duration_days' => 2, 'total_seats' => 4, 'price' => 0, 'currency' => 'ETB', 'is_published' => true])->save();
        $owner = $this->makeUser();
        $booking = TourBooking::create([
            'tour_id' => $tour->id, 'user_id' => $owner->id, 'reference' => TourBooking::generateReference(),
            'first_name' => 'Abebe', 'last_name' => 'Kebede', 'email' => 'abebe@example.test', 'phone' => '0911223344',
            'participants' => 1, 'unit_price' => 0, 'total_amount' => 0, 'currency' => 'ETB', 'status' => 'confirmed', 'payment_status' => 'paid', 'booked_at' => now(),
        ]);
        $url = route('tours.confirmation', $booking->reference);

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->makeUser())->get($url)->assertNotFound();
        $this->actingAs($owner)->get($url)->assertOk()->assertSee('Abebe');
    }

    public function test_a_token_in_the_video_url_is_not_accepted(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Api/FileController.php'));
        $this->assertStringNotContainsString("query('token')", $source);
        $this->assertStringNotContainsString('PersonalAccessToken::findToken', $source);
    }

    public function test_guest_fira_ai_questions_have_a_daily_cap(): void
    {
        config(['security.ai_guest_per_day' => 2]);
        $ip = '10.9.' . random_int(1, 250) . '.' . random_int(1, 250);
        for ($i = 0; $i < 2; $i++) {
            $this->assertNotSame(429, $this->withServerVariables(['REMOTE_ADDR' => $ip])->postJson('/api/fira-ai/send', ['message' => 'Price of Guji grade 1?'])->status());
        }
        $this->withServerVariables(['REMOTE_ADDR' => $ip])->postJson('/api/fira-ai/send', ['message' => 'Again?'])->assertStatus(429);
    }

    public function test_sign_up_ignores_a_role_or_approval_sent_by_the_client(): void
    {
        Mail::fake();
        $email = 'su_' . Str::lower(Str::random(10)) . '@example.test';
        $this->postJson('/api/auth/email/register/start', [
            'full_name' => 'Anna', 'email' => $email, 'password' => 'Hamburg2026', 'password_confirmation' => 'Hamburg2026', 'terms' => true,
            'role' => 'admin', 'is_approved' => true, 'phone_verified_at' => now()->toIso8601String(),
        ])->assertOk();
        $code = null;
        Mail::assertSent(FiraCodeMail::class, function (FiraCodeMail $m) use ($email, &$code) {
            return $m->hasTo($email) && ($code = $m->code);
        });
        $this->postJson('/api/auth/email/register/verify', ['email' => $email, 'code' => $code, 'role' => 'admin'])->assertSuccessful();

        $user = User::where('email', $email)->firstOrFail();
        $this->assertSame('student', $user->role);
        $this->assertNull($user->phone_verified_at);
    }

    public function test_no_controller_passes_raw_request_data_into_a_user(): void
    {
        $offenders = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path('Http/Controllers'))) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $src = file_get_contents($file->getPathname());
            if (preg_match('/\$(user|student|instructor|admin)->(update|fill|forceFill)\(\s*\$request->(all|input|validated|except|post)\(/', $src)
                || preg_match('/User::(create|forceCreate)\(\s*\$request->(all|input|validated|except|post)\(/', $src)) {
                $offenders[] = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        $this->assertSame([], $offenders, 'Request data straight into a User lets a client set role or approval.');
    }
}
