<?php

namespace Tests\Feature\Messaging;

use App\Models\NotificationDelivery;
use App\Models\User;
use App\Notifications\Catalog\Channel;
use App\Notifications\Catalog\MessageClass;
use App\Notifications\Catalog\NotificationType;
use App\Notifications\FiraNotification;
use App\Services\Notifications\NotificationPreferences;
use App\Services\Notifications\Notifier;
use App\Services\SmsEthiopiaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/** An SMS gateway that records texts, or refuses them. */
class RecordingSms extends SmsEthiopiaService
{
    public array $texts = [];

    public function __construct(public bool $delivers = true)
    {
        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function send(string $to, string $message, ?string $templateKey = null, ?User $user = null): array
    {
        if (!$this->delivers) {
            return ['success' => false, 'code' => 'PROVIDER_DOWN', 'message' => 'down'];
        }
        $this->texts[] = [$to, $message];

        return ['success' => true, 'message_id' => 'sms-' . count($this->texts)];
    }
}

/**
 * Messaging slice 1: the catalogue, preferences, the delivery log and idempotency.
 */
class CatalogueTest extends TestCase
{
    use DatabaseTransactions;

    private function user(array $extra = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'm_' . Str::lower(Str::random(10)) . '@example.test',
            'phone' => '2519' . random_int(10000000, 99999999),
        ], $extra));
    }

    public function test_every_type_has_a_complete_and_consistent_definition(): void
    {
        $this->assertGreaterThanOrEqual(100, count(NotificationType::cases()));
        $this->assertSame(range('A', 'K'), array_keys(NotificationType::byGroup()), 'Every group A to K has messages.');

        foreach (NotificationType::cases() as $type) {
            $d = $type->definition();
            $label = $type->value;
            $this->assertNotEmpty($d->channels, $label);
            $this->assertSame([], array_diff($d->channels, Channel::ALL), "{$label}: unknown channel");
            $this->assertSame([], array_diff($d->defaults, $d->channels), "{$label}: default outside allowed channels");
            $this->assertContains($d->androidChannel, NotificationType::ANDROID_CHANNELS, $label);
            $this->assertContains($d->priority, ['high', 'normal', 'low'], $label);
            $this->assertGreaterThan(0, $d->ttlSeconds, $label);
            $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z0-9_]+$/', $label);

            if ($d->class === MessageClass::Security) {
                $this->assertFalse($d->canDisable, "{$label}: security can never be turned off");
                $this->assertSame('high', $d->priority, $label);
            }
            if ($d->class === MessageClass::Marketing) {
                $this->assertSame([], $d->defaults, "{$label}: marketing is off until opt-in");
                $this->assertTrue($d->canDisable, $label);
            }
            if ($d->immediate) {
                $this->assertSame([], array_intersect($d->channels, [Channel::Push, Channel::Database]), "{$label}: codes never go to push or the inbox");
                $this->assertNull($d->appRoute, $label);
            }
            if ($d->auth === NotificationType::AUTH_SIGNED_IN) {
                $this->assertNotNull($d->appRoute, "{$label}: every app message opens a screen");
                $this->assertStringStartsWith('/', $d->appRoute, $label);
            }
        }
    }

    public function test_every_app_route_in_the_catalogue_exists_in_the_app_router(): void
    {
        $router = base_path('../../firalink flutter/lib/router/app_router.dart');
        if (!is_file($router)) {
            $this->markTestSkipped('App sources not next to the server.');
        }
        preg_match_all("/static const String \\w+ = '([^']+)';/", file_get_contents($router), $m);
        $routes = $m[1];
        foreach (NotificationType::cases() as $type) {
            if ($route = $type->definition()->appRoute) {
                $this->assertContains($route, $routes, "{$type->value} opens {$route}, which the app does not have");
            }
        }
    }

    public function test_security_ignores_opt_outs_and_cannot_be_switched_off(): void
    {
        $user = $this->user();
        $prefs = app(NotificationPreferences::class);

        $this->assertTrue($prefs->wants($user, NotificationType::PasswordChanged, Channel::Mail));
        $this->assertTrue($prefs->wants($user, NotificationType::PasswordChanged, Channel::Push));
        $this->assertFalse($prefs->wants($user, NotificationType::PasswordChanged, Channel::Sms), 'Not a default channel.');

        $this->expectException(\InvalidArgumentException::class);
        $prefs->set($user, MessageClass::Security, Channel::Mail, false, 'test');
    }

    public function test_product_defaults_and_switches(): void
    {
        $user = $this->user();
        $prefs = app(NotificationPreferences::class);
        $t = NotificationType::NewCourseInCategory;

        $this->assertTrue($prefs->wants($user, $t, Channel::Database));
        $this->assertTrue($prefs->wants($user, $t, Channel::Push));
        $this->assertFalse($prefs->wants($user, $t, Channel::Mail), 'Product email is off by default.');

        $prefs->set($user, MessageClass::Product, Channel::Push, false, 'app_settings');
        $this->assertFalse($prefs->wants($user, $t, Channel::Push));
        $this->assertTrue($prefs->wants($user, $t, Channel::Database), 'The inbox keeps the record.');

        // A transactional type that cannot be turned off ignores the switch.
        $prefs->set($user, MessageClass::Transactional, Channel::Push, false, 'app_settings');
        $this->assertTrue($prefs->wants($user, NotificationType::SampleShipped, Channel::Push));
        $this->assertFalse($prefs->wants($user, NotificationType::QuoteDeclined, Channel::Push), 'This one can be turned off.');
    }

    public function test_marketing_needs_an_opt_in_with_a_consent_time(): void
    {
        $user = $this->user();
        $prefs = app(NotificationPreferences::class);

        $this->assertFalse($prefs->wants($user, NotificationType::Newsletter, Channel::Mail));
        $this->assertFalse($prefs->wants($user, NotificationType::Newsletter, Channel::Database));

        $row = $prefs->set($user, MessageClass::Marketing, Channel::Mail, true, 'app_settings');
        $this->assertNotNull($row->consented_at);
        $this->assertTrue($prefs->wants($user, NotificationType::Newsletter, Channel::Mail));
        $this->assertTrue($prefs->wants($user, NotificationType::Newsletter, Channel::Database));
    }

    public function test_the_old_marketing_email_setting_still_counts(): void
    {
        $user = $this->user();
        \App\Models\UserNotificationSetting::create(['user_id' => $user->id, 'preferences' => ['marketing_emails' => true, 'email_notifications' => false]]);
        $user->refresh();
        $prefs = app(NotificationPreferences::class);

        $this->assertTrue($prefs->wants($user, NotificationType::Newsletter, Channel::Mail));
        $this->assertFalse($prefs->wants($user, NotificationType::NewCourseInCategory, Channel::Mail));
    }

    public function test_the_inbox_row_stores_the_key_and_safe_params_and_never_a_trust_flag(): void
    {
        $user = $this->user();
        app(Notifier::class)->send($user, NotificationType::SampleShipped, ['sample_id' => 42, 'lot' => 'Guji G1', 'code' => '123456'], 'sample.shipped:42');

        $row = $user->notifications()->latest()->first();
        $this->assertSame('sample.shipped', $row->data['type']);
        $this->assertSame('/marketplace/samples/tracking', $row->data['route']);
        $this->assertSame('42', $row->data['route_id']);
        $this->assertArrayNotHasKey('code', $row->data['params']);
        $this->assertNotEmpty($row->data['title_am']);

        $this->expectException(\InvalidArgumentException::class);
        new FiraNotification(NotificationType::VerificationApproved, ['verified' => true]);
    }

    public function test_the_same_idempotency_key_never_sends_twice(): void
    {
        $user = $this->user();
        $notifier = app(Notifier::class);

        $notifier->send($user, NotificationType::DealOpened, ['deal_room_id' => 7], 'deal.opened:7');
        $notifier->send($user, NotificationType::DealOpened, ['deal_room_id' => 7], 'deal.opened:7');

        $this->assertSame(1, $user->notifications()->where('data->type', 'deal.opened')->count());
        $this->assertSame(1, NotificationDelivery::where('idempotency_key', 'deal.opened:7')->where('channel', Channel::Database)->count());
        $this->assertSame(NotificationDelivery::SENT, NotificationDelivery::where('idempotency_key', 'deal.opened:7')->where('channel', Channel::Database)->value('status'));
    }

    public function test_an_opt_out_is_logged_as_skipped(): void
    {
        $user = $this->user();
        app(NotificationPreferences::class)->set($user, MessageClass::Product, Channel::Push, false, 'app_settings');

        app(Notifier::class)->send($user, NotificationType::NewCourseInCategory, [], 'new_course:1:' . $user->id);

        $this->assertSame(NotificationDelivery::SKIPPED_PREFERENCE, NotificationDelivery::where('idempotency_key', 'new_course:1:' . $user->id)->where('channel', Channel::Push)->value('status'));
        $this->assertSame(NotificationDelivery::SENT, NotificationDelivery::where('idempotency_key', 'new_course:1:' . $user->id)->where('channel', Channel::Database)->value('status'));
    }

    public function test_quiet_hours_hold_push_but_never_security(): void
    {
        $user = $this->user(['quiet_hours_from' => '22:00', 'quiet_hours_until' => '07:00']);
        $prefs = app(NotificationPreferences::class);
        $this->assertTrue($prefs->inQuietHours($user, Carbon::parse('2026-11-21 23:30', 'Africa/Addis_Ababa')));
        $this->assertFalse($prefs->inQuietHours($user, Carbon::parse('2026-11-21 12:00', 'Africa/Addis_Ababa')));

        Carbon::setTestNow(Carbon::parse('2026-11-21 23:30', 'Africa/Addis_Ababa'));
        try {
            $n = new FiraNotification(NotificationType::LessonReminder, [], 'lesson:1:' . $user->id);
            $this->assertFalse($n->shouldSend($user, Channel::driver(Channel::Push)));
            $this->assertSame(NotificationDelivery::SKIPPED_QUIET, NotificationDelivery::where('idempotency_key', 'lesson:1:' . $user->id)->value('status'));

            $s = new FiraNotification(NotificationType::PasswordChanged, [], 'pw:' . $user->id);
            $this->assertTrue($s->shouldSend($user, Channel::driver(Channel::Push)));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_a_code_is_sent_immediately_and_never_stored(): void
    {
        $sms = new RecordingSms();
        $this->app->instance(SmsEthiopiaService::class, $sms);
        Lang::addLines(['messages.security.otp_sign_in.sms' => 'Fira Link: your code is :code. It expires in :minutes minutes. Never share it.'], 'en');
        $user = $this->user();

        $ok = app(Notifier::class)->send($user, NotificationType::OtpSignIn, ['code' => '482913', 'minutes' => 10], null, [Channel::Sms]);

        $this->assertTrue($ok);
        $this->assertCount(1, $sms->texts);
        $this->assertStringContainsString('482913', $sms->texts[0][1]);
        $row = NotificationDelivery::where('type', 'security.otp_sign_in')->latest('id')->first();
        $this->assertSame(NotificationDelivery::SENT, $row->status);
        $this->assertSame('sms-1', $row->provider_message_id);
        $this->assertStringNotContainsString('482913', json_encode(NotificationDelivery::all()->toArray()));
        $this->assertSame(0, $user->notifications()->count(), 'Codes never reach the inbox.');
    }

    public function test_a_failed_gateway_is_recorded_as_failed_never_as_sent(): void
    {
        $this->app->instance(SmsEthiopiaService::class, new RecordingSms(delivers: false));
        Lang::addLines(['messages.security.otp_sign_in.sms' => 'Fira Link: your code is :code.'], 'en');
        $user = $this->user();

        $ok = app(Notifier::class)->send($user, NotificationType::OtpSignIn, ['code' => '111222'], null, [Channel::Sms]);

        $this->assertFalse($ok);
        $row = NotificationDelivery::where('type', 'security.otp_sign_in')->latest('id')->first();
        $this->assertSame(NotificationDelivery::FAILED, $row->status);
        $this->assertSame('PROVIDER_DOWN', $row->error_code);
        $this->assertNull($row->sent_at);
    }

    public function test_an_sms_without_text_is_not_reported_as_sent(): void
    {
        $this->app->instance(SmsEthiopiaService::class, new RecordingSms());
        $user = $this->user();

        app(Notifier::class)->send($user, NotificationType::PhoneChanged, ['phone_masked' => '+251 9** *** 123'], 'phone:' . $user->id);

        $this->assertSame(NotificationDelivery::SKIPPED_UNREACHABLE, NotificationDelivery::where('idempotency_key', 'phone:' . $user->id)->where('channel', Channel::Sms)->value('status'));
    }

    public function test_the_delivery_log_has_no_place_for_bodies_or_contact_details(): void
    {
        $columns = Schema::getColumnListing('notification_deliveries');
        foreach (['body', 'message', 'content', 'text', 'subject', 'email', 'phone', 'recipient', 'payload', 'code'] as $forbidden) {
            $this->assertNotContains($forbidden, $columns);
        }
    }

    public function test_marketing_never_goes_to_a_bare_address(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(Notifier::class)->sendToAddress('mail', 'someone@example.test', NotificationType::Newsletter);
    }

    /**
     * Only the channels (and, until each group is converted, these legacy senders) hand text to the
     * mailer or the SMS gateway. A new file on this list fails the build: send through the Notifier.
     */
    public function test_no_new_code_sends_mail_or_sms_directly(): void
    {
        $allowed = [
            // channels and the gateway itself
            'app/Channels/SmsChannel.php', 'app/Services/SmsEthiopiaService.php',
            // legacy senders, converted group by group in messaging slices 2 to 5 (then removed here)
            'app/Http/Controllers/Admin/NewsletterController.php', 'app/Http/Controllers/Admin/ReviewController.php',
            'app/Http/Controllers/Admin/SettingsController.php', 'app/Http/Controllers/Api/AuthController.php',
            'app/Http/Controllers/Api/CourseEnrollmentController.php', 'app/Http/Controllers/Api/Instructor/AuthController.php',
            'app/Http/Controllers/Api/Instructor/EventController.php', 'app/Http/Controllers/Auth/LoginController.php',
            'app/Http/Controllers/Auth/LoginOtpController.php', 'app/Http/Controllers/Auth/PhoneVerificationController.php',
            'app/Http/Controllers/Front/ContactController.php', 'app/Http/Controllers/Front/CourseEnrollmentController.php',
            'app/Http/Controllers/Front/ReviewController.php', 'app/Http/Controllers/Instructor/CourseBuilderController.php',
            'app/Http/Controllers/Instructor/EventBookingEmailController.php', 'app/Models/User.php', 'app/Services/OtpService.php',
        ];
        $pattern = '/Mail::(to|send|raw|html|queue)\(|->sendFromTemplate\(|->sendOtp\(|->sendWelcomeNotification\(|->sendEnrollmentNotification\(|->sendPaymentConfirmation\(|Service->send\(|sms->send\(|smsService->send\(/';

        $offenders = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path())) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $rel = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
            if (!in_array($rel, $allowed, true) && preg_match($pattern, file_get_contents($file->getPathname()))) {
                $offenders[] = $rel;
            }
        }

        $this->assertSame([], $offenders, "Send through App\\Services\\Notifications\\Notifier instead:\n" . implode("\n", $offenders));
    }

    public function test_the_app_language_is_remembered_for_messages(): void
    {
        $user = $this->user();
        $this->assertSame('en', $user->preferredLocale());

        $this->withToken($user->createToken('app')->plainTextToken)
            ->withHeader('Accept-Language', 'am')
            ->getJson('/api/user')->assertOk();

        $this->assertSame('am', $user->fresh()->locale);
        $this->assertSame('am', $user->fresh()->preferredLocale());
    }
}
