<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/** QA tools for launch testing, and the forgot-password answers they found. */
class QaToolsTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures;

    private function seedRun(): array
    {
        $this->assertSame(0, Artisan::call('qa:seed', ['--run' => 'qatt' . random_int(100, 999), '--password' => 'QaTools#2026pass']));
        preg_match('/QA_JSON:(\{.*\})/', Artisan::output(), $m);

        return json_decode($m[1], true);
    }

    public function test_seed_makes_one_account_per_role_and_cleanup_removes_only_qa_data(): void
    {
        $real = $this->makeUser();
        $run = $this->seedRun();

        $this->assertCount(11, $run['accounts']);
        foreach ($run['accounts'] as $a) {
            $this->assertStringEndsWith('@qa.firalink.test', $a['email']);
        }
        $this->assertTrue(User::find($run['accounts']['reviewer']['id'])->hasRole('reviewer'));
        $this->assertTrue((bool) User::find($run['accounts']['instructor']['id'])->is_approved);
        $this->assertFalse((bool) User::find($run['accounts']['instructor_pending']['id'])->is_approved);
        $this->assertSame(1, $run['businesses']['exporter']['tier']);
        $this->assertNotEmpty($run['staff_totp_secrets']['admin']);

        $this->assertSame(0, Artisan::call('qa:cleanup', ['--run' => $run['run']]));
        $this->assertSame(0, User::where('email', 'like', $run['run'] . '.%')->count());
        $this->assertNotNull($real->fresh());
    }

    public function test_qa_code_works_only_for_qa_accounts_and_a_started_flow(): void
    {
        $run = $this->seedRun();
        $student = $run['accounts']['student'];

        // Nothing pending yet.
        $this->assertSame(1, Artisan::call('qa:code', ['identifier' => $student['email'], 'purpose' => 'password_reset']));

        $this->postJson('/api/auth/password/forgot', ['channel' => 'email', 'email' => $student['email']])->assertOk();
        $this->assertSame(0, Artisan::call('qa:code', ['identifier' => $student['email'], 'purpose' => 'password_reset']));
        preg_match('/QA_CODE:(\d+)/', Artisan::output(), $m);
        $this->postJson('/api/auth/password/verify-code', ['channel' => 'email', 'email' => $student['email'], 'code' => $m[1]])
            ->assertOk()->assertJsonStructure(['reset_ticket']);

        // A real account is refused even with a flow started.
        $real = $this->makeUser();
        $this->postJson('/api/auth/password/forgot', ['channel' => 'email', 'email' => $real->email])->assertOk();
        $this->assertSame(1, Artisan::call('qa:code', ['identifier' => $real->email, 'purpose' => 'password_reset']));
        $this->assertSame(1, Artisan::call('qa:code', ['identifier' => (string) $real->id, 'purpose' => 'login']));
    }

    public function test_qa_tools_never_run_in_production_and_need_the_flag_elsewhere(): void
    {
        $this->app['env'] = 'production';
        $this->assertSame(1, Artisan::call('qa:seed'));
        $this->assertSame(1, Artisan::call('qa:cleanup', ['--all' => true]));
        $this->assertSame(1, Artisan::call('qa:code', ['identifier' => 'x@qa.firalink.test', 'purpose' => 'login']));

        $this->app['env'] = 'staging';
        config(['qa.enabled' => false]);
        $this->assertSame(1, Artisan::call('qa:seed'));
        $this->assertStringContainsString('QA_TOOLS_ENABLED', Artisan::output());
    }

    public function test_web_forgot_password_gives_the_same_answer_when_mail_fails(): void
    {
        $user = $this->makeUser();
        Password::shouldReceive('sendResetLink')->andThrow(new \RuntimeException('530 Authentication required'));

        $known = $this->post('/forgot-password', ['email' => $user->email]);
        $unknown = $this->post('/forgot-password', ['email' => 'nobody-' . uniqid() . '@example.test']);
        $known->assertRedirect();
        $this->assertSame($unknown->status(), $known->status());
        $this->assertSame($unknown->headers->get('Location'), $known->headers->get('Location'));
    }

    public function test_instructor_forgot_password_does_not_reveal_accounts(): void
    {
        $instructor = $this->makeUser('instructor');
        Password::shouldReceive('sendResetLink')->andThrow(new \RuntimeException('mail down'));

        $a = $this->postJson('/api/instructor/auth/forgot-password', ['email' => $instructor->email])->assertOk();
        $b = $this->postJson('/api/instructor/auth/forgot-password', ['email' => 'nobody-' . uniqid() . '@example.test'])->assertOk();
        $this->assertSame($a->json('message'), $b->json('message'));
    }
}
