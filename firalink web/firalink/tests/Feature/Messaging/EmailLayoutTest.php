<?php

namespace Tests\Feature\Messaging;

use App\Mail\FiraCodeMail;
use App\Mail\FiraMail;
use App\Models\User;
use App\Notifications\Catalog\NotificationType;
use App\Services\Notifications\MessagePreview;
use App\Services\Notifications\MessageText;
use App\Services\Notifications\WelcomeMessage;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Messaging slice 2: one email layout and component set for every catalogue email.
 */
class EmailLayoutTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    private const CODE_TYPES = [
        NotificationType::OtpSignIn, NotificationType::OtpSignUp, NotificationType::OtpPasswordReset,
        NotificationType::OtpStepUp, NotificationType::OtpContactChange,
    ];

    /** @return NotificationType[] every type whose email texts exist */
    private function writtenEmailTypes(): array
    {
        $text = app(MessageText::class);

        return array_values(array_filter(NotificationType::cases(), fn ($t) => $t->allows('mail') && $text->has($t, 'subject', 'en')));
    }

    public function test_the_three_first_messages_have_email_texts_in_both_languages(): void
    {
        $text = app(MessageText::class);
        $required = array_merge(self::CODE_TYPES, [NotificationType::PasswordResetLink, NotificationType::Welcome]);
        foreach ($required as $type) {
            foreach (['en', 'am'] as $locale) {
                foreach (['subject', 'preheader', 'heading', 'body'] as $part) {
                    $this->assertTrue($text->has($type, $part, $locale), "{$type->value}.{$part} missing in {$locale}");
                }
            }
        }
        $this->assertCount(count($required), $this->writtenEmailTypes(), 'Only the slice 2 messages are converted so far.');
    }

    public function test_every_written_email_follows_the_layout_rules_in_english_and_amharic(): void
    {
        $preview = app(MessagePreview::class);
        $base = rtrim(config('app.url'), '/');
        $allowedColors = array_map('strtoupper', array_values(config('mail_brand.colors')));

        foreach ($this->writtenEmailTypes() as $type) {
            foreach (['en', 'am'] as $locale) {
                $label = "{$type->value} [{$locale}]";
                $html = $preview->mailHtml($type, $locale);
                $text = $preview->mailText($type, $locale);
                $subject = $preview->mailSubject($type, $locale);
                $preheader = $preview->notification($type, $locale)->toMail($preview->notifiable($locale))->content['preheader'];

                // Subject: short, plain, no code, no exclamation, no all caps.
                $this->assertLessThanOrEqual(50, mb_strlen($subject), "{$label} subject too long");
                $this->assertStringNotContainsString('!', $subject, $label);
                $this->assertDoesNotMatchRegularExpression('/\d{4,}/', $subject, "{$label} subject carries a number or code");
                if ($locale === 'en') { // Ethiopic has no capitals
                    $this->assertNotSame(mb_strtoupper($subject), $subject, "{$label} subject in capitals");
                }
                $this->assertDoesNotMatchRegularExpression('/\d{4,}/', $preheader, "{$label} preheader carries a code");
                $this->assertNotEmpty($preheader, "{$label} preheader");

                // Language, layout and plain text part.
                $this->assertStringContainsString('<html lang="' . $locale . '"', $html, $label);
                $this->assertStringContainsString('width="600"', $html, $label);
                $this->assertStringNotContainsString('display:flex', $html, $label);
                $this->assertStringNotContainsString('display: grid', $html, $label);
                $this->assertNotEmpty(trim($text), "{$label} plain text");
                $this->assertDoesNotMatchRegularExpression('/<[a-z!\/]/i', $text, "{$label} plain text has HTML");
                $this->assertStringNotContainsString(':minutes', $html . $text, "{$label} unreplaced placeholder");
                $this->assertStringNotContainsString(':name', $html . $text, "{$label} unreplaced placeholder");
                $this->assertStringNotContainsString('messages.', $html . $text, "{$label} missing translation key printed");

                // A code appears only in the code box (and once in the plain text).
                if (in_array($type, self::CODE_TYPES, true)) {
                    $this->assertSame(1, substr_count($html, '482913'), "{$label} code outside the code box");
                    $this->assertStringContainsString('user-select:all', $html, $label);
                    $this->assertSame(1, substr_count($text, '482913'), "{$label} plain text code");
                } else {
                    $this->assertStringNotContainsString('482913', $html, $label);
                }

                // Links and images: official domain only, alt text, no SVG or inline images.
                preg_match_all('/href="([^"]+)"/', $html, $hrefs);
                foreach ($hrefs[1] as $href) {
                    $this->assertTrue($href === $base || str_starts_with($href, $base . '/') || str_starts_with($href, $base . '?'), "{$label} link off the official domain: {$href}");
                }
                preg_match_all('/<img\b[^>]*>/', $html, $imgs);
                $this->assertNotEmpty($imgs[0], "{$label} has the logo");
                foreach ($imgs[0] as $img) {
                    $this->assertMatchesRegularExpression('/alt="[^"]+"/', $img, "{$label} image without alt text");
                    $this->assertMatchesRegularExpression('/src="' . preg_quote($base, '/') . '\/[^"]+\.png"/', $img, "{$label} image not a hosted PNG");
                }
                $this->assertStringNotContainsString('data:image', $html, $label);
                $this->assertStringNotContainsString('.svg', $html, $label);

                // Brand colours only; body text at least 15 px; button at least 44 px tall (14 + 20 + 14).
                preg_match_all('/#[0-9A-Fa-f]{6}\b/', $html, $colors);
                foreach (array_unique(array_map('strtoupper', $colors[0])) as $color) {
                    $this->assertContains($color, $allowedColors, "{$label} colour {$color} is not a brand token");
                }
                $this->assertStringContainsString('font-size:16px;line-height:26px', $html, "{$label} body text size");
                if (str_contains($html, 'padding:14px 28px')) {
                    $this->assertStringContainsString('line-height:20px', $html, $label);
                }

                // Copy: no exclamation marks anywhere in the card; no badge words from a template.
                $card = strip_tags(substr($html, strpos($html, '<h1')));
                $this->assertStringNotContainsString('!', $card, $label);
                $this->assertDoesNotMatchRegularExpression('/\bVerified\b/', $card, "{$label} badge word in a template");
            }
        }
    }

    public function test_senders_and_reply_to_follow_the_message_class(): void
    {
        $preview = app(MessagePreview::class);

        $security = $preview->notification(NotificationType::OtpSignIn, 'en')->toMail($preview->notifiable('en'));
        $security->assertFrom(config('mail_brand.from.security'), 'Fira Link');
        $this->assertSame([], $security->replyTo, 'Nobody replies to a security email with a code.');
        $this->assertInstanceOf(FiraCodeMail::class, $security);

        $welcome = $preview->notification(NotificationType::Welcome, 'en')->toMail($preview->notifiable('en'));
        $welcome->assertFrom(config('mail_brand.from.transactional'), 'Fira Link');
        $welcome->assertHasReplyTo(config('mail_brand.support_email'));
        $this->assertNotInstanceOf(FiraCodeMail::class, $welcome);
    }

    public function test_each_code_purpose_gets_its_own_message(): void
    {
        $this->assertSame(NotificationType::OtpSignIn, OtpService::codeType('login'));
        $this->assertSame(NotificationType::OtpSignIn, OtpService::codeType('phone_signin'));
        $this->assertSame(NotificationType::OtpSignUp, OtpService::codeType('email_signup'));
        $this->assertSame(NotificationType::OtpPasswordReset, OtpService::codeType('password_reset'));
        $this->assertSame(NotificationType::OtpStepUp, OtpService::codeType('step_up'));
        $this->assertSame(NotificationType::OtpContactChange, OtpService::codeType('email_change'));
    }

    public function test_a_password_reset_code_email_says_reset_and_uses_the_persons_language(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'r_' . Str::lower(Str::random(8)) . '@example.test', 'phone' => null, 'locale' => 'am']);

        $this->postJson('/api/auth/password/forgot', ['channel' => 'email', 'email' => $user->email])->assertOk();

        Mail::assertSent(FiraCodeMail::class, function (FiraCodeMail $m) use ($user) {
            return $m->hasTo($user->email)
                && $m->type === 'security.otp_password_reset'
                && $m->content['locale'] === 'am'
                && $m->content['subject'] === __('messages.security.otp_password_reset.subject', [], 'am');
        });
    }

    public function test_the_website_reset_link_email_uses_the_layout_and_keeps_the_email_out_of_the_link(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'w_' . Str::lower(Str::random(8)) . '@example.test']);

        $this->post('/forgot-password', ['email' => $user->email]);

        Mail::assertSent(FiraMail::class, function (FiraMail $m) use ($user) {
            if (!$m->hasTo($user->email) || $m->type !== 'security.password_reset_link') {
                return false;
            }
            $button = collect($m->content['blocks'])->firstWhere('type', 'button');

            return $button
                && str_starts_with($button['url'], rtrim(config('app.url'), '/') . '/reset-password/')
                && !str_contains($button['url'], urlencode($user->email))
                && !str_contains($button['url'], $user->email);
        });
    }

    public function test_welcome_is_sent_once_with_an_inbox_row(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'h_' . Str::lower(Str::random(8)) . '@example.test']);

        app(WelcomeMessage::class)->send($user);
        app(WelcomeMessage::class)->send($user);

        Mail::assertSent(FiraMail::class, fn (FiraMail $m) => $m->type === 'account.welcome' && $m->hasTo($user->email));
        Mail::assertSent(FiraMail::class, 1);
        $this->assertSame(1, $user->notifications()->where('data->type', 'account.welcome')->count());
    }

    public function test_a_phone_only_account_gets_the_inbox_welcome_without_an_email(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => null, 'phone' => '2519' . random_int(10000000, 99999999)]);

        app(WelcomeMessage::class)->send($user);

        Mail::assertNothingSent();
        $this->assertSame(1, $user->notifications()->where('data->type', 'account.welcome')->count());
    }

    public function test_the_preview_page_is_for_admins_with_two_factor_only(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAsStaff($admin)->get(route('admin.messages.index'))->assertOk()->assertSee('security.otp_sign_in');
        $this->actingAsStaff($admin)->get(route('admin.messages.show', ['type' => 'security.otp_sign_in', 'locale' => 'am']))
            ->assertOk()->assertSee('<html lang="am"', false);
        $sms = $this->actingAsStaff($admin)->getJson(route('admin.messages.show', ['type' => 'account.welcome', 'view' => 'push']))->assertOk();
        $this->assertNotEmpty($sms->json('title'));

        $this->actingAs($this->makeUser())->get(route('admin.messages.index'))->assertStatus(403);
        $this->app['auth']->forgetGuards();
        $this->get(route('admin.messages.index'))->assertRedirect();
    }

    public function test_the_preview_command_renders_without_sending(): void
    {
        Mail::fake();
        $this->artisan('messages:preview', ['type' => 'security.otp_step_up', '--locale' => 'am', '--channel' => 'text'])
            ->expectsOutputToContain('482913')
            ->assertExitCode(0);
        Mail::assertNothingSent();
    }

    public function test_the_old_hand_built_templates_are_gone(): void
    {
        $this->assertFileDoesNotExist(app_path('Mail/LoginOtpMail.php'));
        $this->assertFileDoesNotExist(app_path('Mail/WelcomeMail.php'));
        $this->assertFileDoesNotExist(resource_path('views/emails/auth/login-otp.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/emails/auth/welcome.blade.php'));
        $this->assertFileExists(public_path(config('mail_brand.logo.path')));
    }
}
