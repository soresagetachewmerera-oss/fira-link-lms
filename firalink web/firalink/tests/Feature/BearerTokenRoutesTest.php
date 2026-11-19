<?php

namespace Tests\Feature;

use App\Models\CoffeeReadinessAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Services\SmsEthiopiaService;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\Fakes\FakeSmsEthiopia;
use Tests\TestCase;

/**
 * Fix slice C (gap analysis 2026-11-19). These tests use real Sanctum bearer tokens, exactly like the app.
 * actingAs() switches the default guard and hid GAP-004/005/006, so it is deliberately not used here.
 */
class BearerTokenRoutesTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures;

    private function token(User $user): string
    {
        return $user->createToken('app')->plainTextToken;
    }

    private function owner(): array
    {
        $user = $this->makeUser();
        $org = $this->makeOrganization($user);
        $user->forceFill(['active_organization_id' => $org->id])->save();

        return [$user, $org, $this->token($user)];
    }

    public function test_export_docs_accepts_the_app_token_and_refuses_guests(): void
    {
        [, , $token] = $this->owner();

        // Signed in: validation runs (422), so the user was recognised. Before the fix this was 401.
        $this->withToken($token)->postJson('/api/export-docs/shipments', [])->assertStatus(422);
        $this->withToken($token)->getJson('/api/export-docs/shipments')->assertOk()->assertJson(['success' => true]);
        $this->withToken($token)->getJson('/api/export-docs/documents')->assertOk();

        // Guests: 401 everywhere a business is needed; templates and the calculator stay public.
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/export-docs/shipments', [])->assertStatus(401);
        $this->getJson('/api/export-docs/documents')->assertStatus(401);
        $this->postJson('/api/export-docs/ai/assist', [])->assertStatus(401);
        $this->getJson('/api/export-docs/templates')->assertOk();
    }

    public function test_readiness_check_works_for_guests_without_saving_and_saves_for_users(): void
    {
        $guest = $this->postJson('/api/coffee-toolkit/assess', ['has_commercial_reg' => true]);
        $guest->assertOk()->assertJson(['success' => true]);
        $this->assertArrayHasKey('overall_score', $guest->json('data'));

        [$user, , $token] = $this->owner();
        $before = CoffeeReadinessAssessment::where('user_id', $user->id)->count();
        $this->withToken($token)->postJson('/api/coffee-toolkit/assess', ['has_commercial_reg' => true])->assertOk();
        $this->assertSame($before + 1, CoffeeReadinessAssessment::where('user_id', $user->id)->count());

        $this->withToken($token)->getJson('/api/coffee-toolkit/hub-summary')->assertOk()
            ->assertJsonPath('data.latest_assessment.user_id', $user->id);
    }

    public function test_business_entitlements_are_for_members_only(): void
    {
        [, $org, $memberToken] = $this->owner();
        [, , $otherToken] = $this->owner();

        $this->getJson("/api/org/{$org->id}/entitlements")->assertStatus(401);
        $this->withToken($otherToken)->getJson("/api/org/{$org->id}/entitlements")->assertStatus(404);
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();
        $this->withToken($memberToken)->getJson("/api/org/{$org->id}/entitlements")->assertOk()->assertJson(['success' => true]);
    }

    public function test_document_analysis_needs_sign_in(): void
    {
        $this->postJson('/api/fira-ai/analyze-document', [])->assertStatus(401);
        $this->postJson('/api/ai-chat/analyze-document', [])->assertStatus(401);
    }

    public function test_guest_phone_codes_only_go_to_unverified_accounts(): void
    {
        $sms = new FakeSmsEthiopia();
        $this->app->instance(SmsEthiopiaService::class, $sms);

        // Any number: same answer, no SMS.
        $unknown = '0911' . random_int(100000, 999999);
        $this->postJson('/api/auth/phone/verify/send', ['phone' => $unknown])
            ->assertOk()
            ->assertJson(['success' => true, 'message' => 'If this number can be verified, a code is on its way.']);
        $this->assertNull($sms->lastCode($unknown));

        // An account whose phone is not verified yet still gets its code.
        $pending = '0911' . random_int(100000, 999999);
        $this->makeUser()->forceFill(['phone' => $sms->normalizePhoneNumber($pending), 'phone_verified_at' => null])->save();
        $this->postJson('/api/auth/phone/verify/send', ['phone' => $pending])->assertOk();
        $this->assertNotNull($sms->lastCode($pending));
    }

    public function test_a_lesson_from_one_course_does_not_unlock_another_courses_video(): void
    {
        $instructor = $this->makeUser('instructor');
        $course = fn (string $title) => DB::table('courses')->insertGetId(array_filter([
            'instructor_id' => $instructor->id, 'title' => $title, 'slug' => Schema::hasColumn('courses', 'slug') ? 'c-' . Str::random(8) : null,
            'status' => 'published', 'created_at' => now(), 'updated_at' => now(),
        ], fn ($v) => $v !== null));
        $lesson = function (int $courseId, string $video, bool $preview = false) {
            $topic = DB::table('course_topics')->insertGetId(['course_id' => $courseId, 'title' => 'M', 'order' => 1, 'created_at' => now(), 'updated_at' => now()]);

            return DB::table('lessons')->insertGetId(['course_topic_id' => $topic, 'title' => 'L', 'order' => 1, 'video_path' => $video, 'is_preview' => $preview, 'created_at' => now(), 'updated_at' => now()]);
        };

        $dir = 'gap007-' . Str::lower(Str::random(8));
        $paidVideo = "{$dir}/paid.mp4";
        $cheapVideo = "{$dir}/cheap.mp4";
        $previewVideo = "{$dir}/preview.mp4";
        File::ensureDirectoryExists(storage_path("app/public/{$dir}"));
        foreach ([$paidVideo, $cheapVideo, $previewVideo] as $v) {
            File::put(storage_path("app/public/{$v}"), str_repeat('x', 2048));
        }

        try {
            $paidCourse = $course('Paid course');
            $cheapCourse = $course('Cheap course');
            $lesson($paidCourse, $paidVideo);
            $cheapLesson = $lesson($cheapCourse, $cheapVideo);
            $lesson($paidCourse, $previewVideo, true);

            $buyer = $this->makeUser();
            DB::table('enrollments')->insert(['user_id' => $buyer->id, 'course_id' => $cheapCourse, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
            $token = $this->token($buyer);

            // Enrolled in the cheap course: its own video plays.
            $this->withToken($token)->get('/api/video/stream?path=' . urlencode($cheapVideo) . "&lesson_id={$cheapLesson}")->assertOk();
            // The old bypass: the cheap lesson's id with the paid course's video.
            $this->withToken($token)->get('/api/video/stream?path=' . urlencode($paidVideo) . "&lesson_id={$cheapLesson}")->assertStatus(404);
            $this->withToken($token)->get('/api/video/stream?path=' . urlencode($paidVideo))->assertStatus(403);
            // Guests: paid needs sign-in, preview is public.
            $this->flushHeaders();
            $this->app['auth']->forgetGuards();
            $this->get('/api/video/stream?path=' . urlencode($paidVideo), ['Accept' => 'application/json'])->assertStatus(401);
            $this->get('/api/video/stream?path=' . urlencode($previewVideo))->assertOk();

            // Signed temporary URL (GAP-008): plays for player without token, while tampered signature is 401.
            $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'api.video.stream',
                now()->addMinutes(10),
                ['path' => $paidVideo]
            );
            $this->get($signedUrl)->assertOk();

            $tamperedUrl = $signedUrl . '&tampered=1';
            $this->get($tamperedUrl, ['Accept' => 'application/json'])->assertStatus(401);
        } finally {
            File::deleteDirectory(storage_path("app/public/{$dir}"));
        }
    }
}
