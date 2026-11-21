<?php

namespace Tests\Feature\Messaging;

use App\Models\NotificationDelivery;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Notifications\Catalog\NotificationType;
use App\Services\Notifications\MessagePreview;
use App\Services\Notifications\MessageText;
use App\Services\SmsEthiopiaService;
use App\Support\SmsSegments;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\Fakes\FakeSmsEthiopia;
use Tests\TestCase;

/**
 * Messaging slice 3: code SMS in English and Amharic from the catalogue, inside one segment,
 * no links, no emoji, never logged with the code.
 */
class SmsTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    private const CODE_TYPES = [
        NotificationType::OtpSignIn, NotificationType::OtpSignUp, NotificationType::OtpPasswordReset,
        NotificationType::OtpStepUp, NotificationType::OtpContactChange,
    ];

    private FakeSmsEthiopia $sms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sms = new FakeSmsEthiopia();
        $this->app->instance(SmsEthiopiaService::class, $this->sms);
    }

    public function test_segment_counter_knows_gsm7_and_ucs2(): void
    {
        $this->assertSame(['encoding' => 'GSM-7', 'characters' => 160, 'segments' => 1, 'per_segment' => 160], SmsSegments::analyse(str_repeat('a', 160)));
        $this->assertSame(2, SmsSegments::analyse(str_repeat('a', 161))['segments']);
        $this->assertSame(153, SmsSegments::analyse(str_repeat('a', 161))['per_segment']);
        $this->assertSame(2, SmsSegments::analyse('€')['characters'], 'The euro sign takes two GSM-7 places.');
        $this->assertSame(1, SmsSegments::analyse(str_repeat('ሀ', 70))['segments']);
        $this->assertSame(['encoding' => 'UCS-2', 'characters' => 71, 'segments' => 2, 'per_segment' => 67], SmsSegments::analyse(str_repeat('ሀ', 71)));
    }

    public function test_every_sms_text_fits_one_segment_and_follows_the_rules(): void
    {
        $preview = app(MessagePreview::class);
        $text = app(MessageText::class);
        $checked = 0;

        foreach (NotificationType::cases() as $type) {
            if (!$type->allows('sms') || !$text->has($type, 'sms', 'en')) {
                continue;
            }
            foreach (['en', 'am'] as $locale) {
                $label = "{$type->value} [{$locale}]";
                $this->assertTrue($text->has($type, 'sms', $locale), "{$label} has no SMS text");
                $sms = $preview->sms($type, $locale);
                $body = $sms['text'];

                $limit = in_array($type, self::CODE_TYPES, true) ? 1 : 2;
                $this->assertLessThanOrEqual($limit, $sms['segments'], "{$label}: {$sms['characters']} {$sms['encoding']} characters is {$sms['segments']} segments");
                $this->assertSame($locale === 'am' ? 'UCS-2' : 'GSM-7', $sms['encoding'], "{$label}: unexpected characters (curly quotes or emoji?)");
                $this->assertStringStartsWith($locale === 'am' ? 'ፊራ ሊንክ' : 'Fira Link', $body, "{$label}: sender prefix");
                $this->assertDoesNotMatchRegularExpression('/https?:|www\.|\.com|\.et\b|bit\.ly/i', $body, "{$label}: no links in a code SMS");
                $this->assertDoesNotMatchRegularExpression('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}]/u', $body, "{$label}: emoji");
                $this->assertStringNotContainsString('!', $body, $label);
                $this->assertStringNotContainsString(':', str_replace(['Fira Link:'], '', $body), "{$label}: unreplaced placeholder");
                if (in_array($type, self::CODE_TYPES, true)) {
                    $this->assertSame(1, substr_count($body, '482913'), "{$label}: the code once");
                    $this->assertStringContainsString('10', $body, "{$label}: says when it expires");
                }
                $checked++;
            }
        }

        $this->assertGreaterThanOrEqual(10, $checked, 'All five code messages in both languages.');
    }

    public function test_phone_sign_in_sends_the_catalogue_text_in_the_apps_language(): void
    {
        $phone = '09' . random_int(10000000, 99999999);

        $this->withHeader('Accept-Language', 'am')->postJson('/api/auth/phone/start', ['phone' => $phone])->assertOk();
        [, $am] = end($this->sms->messages);
        $this->assertStringStartsWith('ፊራ ሊንክ፦', $am);
        $this->assertMatchesRegularExpression('/\d{6}/', $am);

        $other = '09' . random_int(10000000, 99999999);
        $this->withHeader('Accept-Language', 'en')->postJson('/api/auth/phone/start', ['phone' => $other])->assertOk();
        [, $en] = end($this->sms->messages);
        $this->assertStringStartsWith('Fira Link: your code is ', $en);

        // The old stored template is not what was sent.
        $old = SmsTemplate::where('key', 'otp')->value('content');
        if ($old) {
            $this->assertStringNotContainsString('verification code is', $en);
        }
    }

    /**
     * Where the person is known (signed in), their saved language wins. Sign-in start does not look
     * up the account (same answer for every number), so there the app's language decides.
     */
    public function test_a_saved_language_wins_over_the_request_for_a_known_person(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['phone' => '2519' . random_int(10000000, 99999999), 'phone_verified_at' => now(), 'locale' => 'am'])->save();

        $this->withToken($user->createToken('app')->plainTextToken)
            ->withHeader('Accept-Language', 'en')
            ->postJson('/api/auth/step-up/code')->assertOk()->assertJsonPath('channel', 'sms');

        [, $text] = end($this->sms->messages);
        $this->assertStringStartsWith('ፊራ ሊንክ፦ የማረጋገጫ ኮድዎ', $text);
    }

    public function test_the_gateway_log_never_keeps_the_code(): void
    {
        foreach (['en', 'am'] as $locale) {
            $text = __('messages.security.otp_sign_in.sms', ['code' => '482913', 'minutes' => 10], $locale);
            $this->assertStringNotContainsString('482913', SmsLog::sanitizeMessage($text), $locale);
        }
    }

    public function test_a_failed_gateway_reports_failure_and_records_it(): void
    {
        $this->app->instance(SmsEthiopiaService::class, new FakeSmsEthiopia(configured: true, delivers: false));
        $phone = '09' . random_int(10000000, 99999999);

        $res = $this->postJson('/api/auth/phone/start', ['phone' => $phone]);

        $this->assertFalse((bool) $res->json('success'), 'Never "sent" when the gateway refused.');
        $row = NotificationDelivery::where('type', 'security.otp_sign_in')->where('channel', 'sms')->latest('id')->first();
        $this->assertNotNull($row);
        $this->assertSame(NotificationDelivery::FAILED, $row->status);
        $this->assertSame('SIMULATED_FAILURE', $row->error_code);
    }

    public function test_the_code_sms_is_sent_immediately_with_the_routes_rate_limit(): void
    {
        $this->assertTrue(NotificationType::OtpSignIn->sendsImmediately());
        $route = collect(app('router')->getRoutes())->first(fn ($r) => $r->uri() === 'api/auth/phone/start');
        $this->assertContains('throttle:10,1', $route->gatherMiddleware(), 'Per-IP limit on code requests.');
    }

    public function test_stored_code_templates_can_no_longer_be_edited(): void
    {
        $template = SmsTemplate::firstOrCreate(['key' => 'otp'], ['name' => 'OTP', 'content' => 'Old {otp}', 'is_enabled' => true, 'allowed_variables' => ['otp']]);
        $before = $template->content;
        $admin = $this->makeUser('admin');

        $this->actingAsStaff($admin, fresh: true)
            ->put(route('admin.settings.sms.template.update', $template->id), ['content' => 'Changed {otp}', 'is_enabled' => 1])
            ->assertRedirect();

        $this->assertSame($before, $template->fresh()->content);
    }
}
