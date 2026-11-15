<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventBooking;
use App\Models\Payment;
use App\Models\Tour;
use App\Models\TourBooking;
use App\Models\User;
use App\Services\Booking\ChapaClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/** Slice 8: events, farm tours and Academy from the app flows. */
class EventsToursAcademyTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
    }

    private function event(array $extra = []): Event
    {
        $e = new Event();
        $e->forceFill(array_merge([
            'title' => 'Addis Cupping ' . Str::random(4), 'slug' => 'ev-' . Str::lower(Str::random(10)),
            'start_date' => now()->addDays(10)->toDateString(), 'end_date' => now()->addDays(10)->toDateString(),
            'start_time' => '09:00', 'location' => 'Skylight Hotel', 'total_seats' => 3, 'price' => 500,
            'is_published' => true, 'service_fee' => 25,
        ], $extra))->save();

        return $e;
    }

    private function tour(array $extra = []): Tour
    {
        $t = new Tour();
        $t->forceFill(array_merge([
            'title' => 'Guji harvest ' . Str::random(4), 'slug' => 'tr-' . Str::lower(Str::random(10)), 'destination' => 'Guji',
            'duration_days' => 3, 'total_seats' => 4, 'price' => 10000, 'currency' => 'ETB', 'is_published' => true,
            'deposit_percent' => 30, 'balance_due_days' => 14,
            'available_schedules' => [now()->addDays(40)->toDateString(), now()->addDays(5)->toDateString()],
        ], $extra))->save();

        return $t;
    }

    private function signedIn(): User
    {
        $u = $this->makeUser();
        $u->forceFill(['phone' => '+2519' . random_int(10000000, 99999999)])->save();
        Sanctum::actingAs($u);

        return $u;
    }

    private function bookEvent(Event $e, array $extra = [])
    {
        return $this->post("/api/events/{$e->id}/book", array_merge([
            'first_name' => 'Abebe', 'last_name' => 'Kebede', 'phone' => '+251911000000', 'seats' => 1, 'payment_method' => 'bank_transfer',
        ], $extra), ['Accept' => 'application/json']);
    }

    private function admin(): User
    {
        $admin = $this->makeUser('admin');

        return $admin;
    }

    // ---- Events -----------------------------------------------------------------

    public function test_event_shows_organizer_verified_only_with_an_active_tier_1_and_hides_unpublished_events(): void
    {
        $owner = $this->makeUser();
        $org = $this->approveBusiness($this->makeOrganization($owner), 'exporter');
        $e = $this->event(['organizer_organization_id' => $org->id, 'category' => 'cupping', 'agenda' => [['time' => '09:00', 'title' => 'Welcome']], 'member_price' => 300]);

        $this->getJson("/api/events/{$e->id}")->assertOk()
            ->assertJsonPath('flow.organizer.name', $org->name)
            ->assertJsonPath('flow.organizer.verified', true)
            ->assertJsonPath('flow.category', 'cupping')
            ->assertJsonPath('flow.seats_left', 3)
            ->assertJsonPath('flow.agenda.0.title', 'Welcome')
            ->assertJsonPath('flow.tickets.1.type', 'member')
            ->assertJsonPath('flow.tickets.1.eligible', false);
        $this->getJson('/api/events?category=cupping')->assertOk()->assertJsonFragment(['id' => $e->id]);

        $org->forceFill(['status' => 'suspended'])->save();
        $this->getJson("/api/events/{$e->id}")->assertJsonPath('flow.organizer.verified', false);

        $this->getJson('/api/events/' . $this->event(['is_published' => false])->id)->assertStatus(404);
        $this->getJson('/api/events/' . $this->event(['organized_by_fira_link' => true])->id)->assertJsonPath('flow.organizer.type', 'fira_link');
    }

    public function test_bank_transfer_ticket_needs_a_real_slip_is_in_review_without_a_qr_until_finance_confirms(): void
    {
        $e = $this->event();
        $user = $this->signedIn();

        $this->bookEvent($e, ['phone' => null])->assertStatus(422);
        $this->bookEvent($e)->assertStatus(422)->assertJsonPath('code', 'SLIP_REQUIRED');
        $this->bookEvent($e, ['slip' => $this->dummyBytes('slip.png')])->assertStatus(422)->assertJsonPath('code', 'FILE_REJECTED');
        $this->bookEvent($e, ['ticket_type' => 'member', 'slip' => $this->realPng()])->assertStatus(422)->assertJsonPath('code', 'TICKET_NOT_ALLOWED');

        $res = $this->bookEvent($e, ['seats' => 2, 'company' => 'Abyssinia Green', 'slip' => $this->realPng(), 'bank_reference' => 'CBE-' . Str::random(6)])
            ->assertOk()->assertJsonPath('booking.in_review', true)->assertJsonPath('payment_required', false);
        $booking = EventBooking::findOrFail($res->json('booking_id'));
        $this->assertSame(1050.0, (float) $booking->total_amount); // (500 + 25 fee) x 2
        $this->assertSame('Abyssinia Green', $booking->company);
        $payment = Payment::where('event_booking_id', $booking->id)->firstOrFail();
        $this->assertSame('event_ticket', $payment->purpose);
        Storage::disk('local')->assertExists($payment->receipt_file);
        Storage::disk('public')->assertMissing($payment->receipt_file);

        // A second slip for the same ticket waits on the first.
        $this->getJson("/api/user/bookings/{$booking->id}")->assertOk()->assertJsonPath('ticket.in_review', true)->assertJsonPath('qr_code', null);
        $this->getJson('/api/user/bookings')->assertJsonPath('data.0.in_review', true);

        // Seats are held while in review: one left.
        $this->bookEvent($e, ['seats' => 2, 'slip' => $this->realPng()])->assertStatus(422)->assertJsonPath('code', 'SEATS_LEFT');

        // Another person cannot see this ticket.
        Sanctum::actingAs($this->makeUser());
        $this->getJson("/api/user/bookings/{$booking->id}")->assertStatus(403);

        $admin = $this->admin();
        $this->actingAsStaff($admin)->post(route('admin.payments.approve', $payment->id))->assertRedirect();
        $this->assertSame(EventBooking::STATUS_CONFIRMED, $booking->fresh()->status);
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame($admin->id, (int) $payment->fresh()->confirmed_by);

        Sanctum::actingAs($user);
        $this->assertNotNull($this->getJson("/api/user/bookings/{$booking->id}")->json('qr_code'));
    }

    public function test_a_rejected_slip_cancels_the_ticket_and_frees_the_seat(): void
    {
        $e = $this->event(['total_seats' => 1]);
        $this->signedIn();
        $id = $this->bookEvent($e, ['slip' => $this->realPng()])->assertOk()->json('booking_id');
        $payment = Payment::where('event_booking_id', $id)->firstOrFail();

        $admin = $this->admin();
        $this->actingAsStaff($admin)->post(route('admin.payments.reject', $payment->id), ['reason' => 'Amount does not match'])->assertRedirect();
        $this->assertSame('cancelled', EventBooking::find($id)->status);
        $this->assertSame(1, $e->fresh()->remainingSeats());
    }

    public function test_the_admin_offline_page_opens_slips_only_through_signed_private_links(): void
    {
        $e = $this->event();
        $this->signedIn();
        $id = $this->bookEvent($e, ['slip' => $this->realPng()])->json('booking_id');
        $payment = Payment::where('event_booking_id', $id)->firstOrFail();

        $page = $this->actingAsStaff($this->admin())->get(route('admin.payments.offline-pending'))->assertOk();
        $page->assertSee(__('bookings.purposes.event_ticket'));
        $page->assertDontSee('storage/' . $payment->receipt_file);
        $this->get(app(\App\Services\PrivateDocumentStore::class)->adminUrl('payment-receipt', $payment->id))->assertOk();
        $this->get(route('admin.private-files.show', ['kind' => 'payment-receipt', 'id' => $payment->id]))->assertStatus(403);
    }

    // ---- Tours ------------------------------------------------------------------

    public function test_tour_booking_needs_sign_in_an_offered_date_and_places(): void
    {
        $t = $this->tour();
        $date = now()->addDays(40)->toDateString();
        $payload = ['first_name' => 'Sara', 'last_name' => 'Ali', 'phone' => '+251911000001', 'participants' => 2, 'schedule_date' => $date, 'payment_method' => 'bank_transfer', 'slip' => $this->realPng()];

        $this->post("/api/tours/{$t->id}/book", $payload, ['Accept' => 'application/json'])->assertStatus(401);

        $this->signedIn();
        $this->post("/api/tours/{$t->id}/book", ['schedule_date' => now()->addDays(41)->toDateString()] + $payload, ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonPath('code', 'DATE_NOT_OFFERED');
        $this->post("/api/tours/{$t->id}/book", ['participants' => 5] + $payload, ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonPath('code', 'SEATS_LEFT');

        $this->getJson("/api/tours/{$t->id}")->assertOk()
            ->assertJsonPath('deposit_percent', 30)
            ->assertJsonPath('next_dates.0.date', now()->addDays(5)->toDateString())
            ->assertJsonPath('next_dates.1.places_left', 4);
    }

    public function test_deposit_then_balance_through_fira_link_with_finance_confirming_each_slip(): void
    {
        $t = $this->tour();
        $user = $this->signedIn();
        $date = now()->addDays(40)->toDateString();
        $res = $this->post("/api/tours/{$t->id}/book", [
            'first_name' => 'Sara', 'last_name' => 'Ali', 'phone' => '+251911000001', 'participants' => 2, 'schedule_date' => $date,
            'special_requests' => 'Vegetarian', 'payment_method' => 'bank_transfer', 'slip' => $this->realPng(),
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('booking.deposit_amount', 6000)
            ->assertJsonPath('booking.balance_amount', 14000)
            ->assertJsonPath('booking.balance_due_date', now()->addDays(26)->toDateString());
        $booking = TourBooking::findOrFail($res->json('booking_id'));
        $this->getJson("/api/tours/{$t->id}")->assertJsonPath('next_dates.1.places_left', 2);

        // The balance cannot be paid before the deposit is confirmed.
        $this->post("/api/user/tour-bookings/{$booking->id}/pay-balance", ['payment_method' => 'bank_transfer', 'slip' => $this->realPng()], ['Accept' => 'application/json'])->assertStatus(403);

        $admin = $this->admin();
        $deposit = Payment::where('tour_booking_id', $booking->id)->where('purpose', 'tour_deposit')->firstOrFail();
        $this->actingAsStaff($admin)->post(route('admin.payments.approve', $deposit->id))->assertRedirect();
        $booking->refresh();
        $this->assertSame(TourBooking::STATUS_CONFIRMED, $booking->status);
        $this->assertSame('deposit_paid', $booking->payment_status);

        Sanctum::actingAs($user);
        $this->post("/api/user/tour-bookings/{$booking->id}/pay-balance", ['payment_method' => 'bank_transfer', 'slip' => $this->realPng()], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame('balance_in_review', $booking->fresh()->payment_status);
        $this->post("/api/user/tour-bookings/{$booking->id}/pay-balance", ['payment_method' => 'bank_transfer', 'slip' => $this->realPng()], ['Accept' => 'application/json'])->assertStatus(403);

        $balance = Payment::where('tour_booking_id', $booking->id)->where('purpose', 'tour_balance')->firstOrFail();
        $this->assertSame(14000.0, (float) $balance->amount);
        $this->actingAsStaff($admin)->post(route('admin.payments.approve', $balance->id))->assertRedirect();
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertNotNull($booking->fresh()->balance_paid_at);

        Sanctum::actingAs($user);
        $this->getJson("/api/user/tour-bookings/{$booking->id}")->assertOk()->assertJsonPath('ticket.balance_paid', true)->assertJsonPath('ticket.special_requests', 'Vegetarian');
    }

    public function test_a_tour_soon_is_paid_in_full_and_unpaid_balances_and_abandoned_checkouts_release_places(): void
    {
        $t = $this->tour();
        $this->signedIn();
        $soon = $this->post("/api/tours/{$t->id}/book", [
            'first_name' => 'A', 'last_name' => 'B', 'phone' => '+251911000002', 'participants' => 1, 'schedule_date' => now()->addDays(5)->toDateString(),
            'payment_method' => 'bank_transfer', 'slip' => $this->realPng(),
        ], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame(10000.0, (float) $soon->json('booking.deposit_amount'));
        $this->assertSame(0.0, (float) $soon->json('booking.balance_amount'));

        // Balance due yesterday, still unpaid: places are released.
        $late = new TourBooking();
        $late->forceFill([
            'tour_id' => $t->id, 'user_id' => $this->makeUser()->id, 'reference' => TourBooking::generateReference(), 'first_name' => 'L', 'last_name' => 'T',
            'participants' => 2, 'schedule_date' => now()->addDays(10)->toDateString(), 'unit_price' => 10000, 'total_amount' => 20000,
            'deposit_amount' => 6000, 'balance_amount' => 14000, 'balance_due_date' => now()->subDay()->toDateString(), 'deposit_paid_at' => now()->subDays(20),
            'currency' => 'ETB', 'status' => 'confirmed', 'payment_status' => 'deposit_paid', 'booked_at' => now()->subDays(20),
        ])->save();
        // A Chapa checkout opened two hours ago and never paid.
        $abandoned = $late->replicate();
        $abandoned->forceFill(['reference' => TourBooking::generateReference(), 'status' => 'pending', 'payment_status' => 'pending', 'balance_due_date' => now()->addDays(20)->toDateString(), 'deposit_paid_at' => null])->save();
        DB::table('tour_bookings')->where('id', $abandoned->id)->update(['created_at' => now()->subHours(2)]);

        $this->artisan('bookings:release-unpaid')->assertSuccessful();
        $this->assertSame('cancelled', $late->fresh()->status);
        $this->assertSame('cancelled', $abandoned->fresh()->status);
        $this->assertSame('pending', TourBooking::find($soon->json('booking_id'))->status); // slip with finance: kept
    }

    public function test_chapa_verify_is_for_the_owner_and_checks_the_amount(): void
    {
        $t = $this->tour();
        $owner = $this->signedIn();
        $this->app->instance(ChapaClient::class, new class extends ChapaClient {
            public static float $paid = 0;

            public function __construct()
            {
            }

            public function initialize(float $amount, string $currency, array $payer, string $txRef, string $callbackPath, string $description): string
            {
                return 'https://checkout.chapa.test/' . $txRef;
            }

            public function verify(string $txRef): array
            {
                return ['status' => 'success', 'reference' => 'CH-' . $txRef, 'amount' => self::$paid, 'currency' => 'ETB'];
            }
        });
        $res = $this->postJson("/api/tours/{$t->id}/book", [
            'first_name' => 'A', 'last_name' => 'B', 'phone' => '+251911000003', 'participants' => 1, 'schedule_date' => now()->addDays(40)->toDateString(), 'payment_method' => 'chapa',
        ])->assertOk()->assertJsonPath('payment_required', true);
        $txRef = $res->json('tx_ref');
        $id = $res->json('booking_id');

        Sanctum::actingAs($this->makeUser());
        $this->postJson('/api/tours/payments/chapa/verify', ['tx_ref' => $txRef, 'booking_id' => $id])->assertStatus(404);

        Sanctum::actingAs($owner);
        $client = app(ChapaClient::class);
        $client::$paid = 1.0; // paid less than the deposit
        $this->postJson('/api/tours/payments/chapa/verify', ['tx_ref' => $txRef, 'booking_id' => $id])->assertStatus(400);
        $this->assertSame('pending', TourBooking::find($id)->status);
    }

    public function test_admin_links_a_host_by_fira_id_and_sets_dates_and_deposit(): void
    {
        $t = $this->tour();
        $org = $this->approveBusiness($this->makeOrganization($this->makeUser()), 'exporter');
        $admin = $this->admin();
        $this->actingAsStaff($admin)->patch(route('admin.tours.flow', $t), ['host_fira_id' => 'FL-NOPE-0000', 'deposit_percent' => 40, 'balance_due_days' => 10])->assertSessionHasErrors('host_fira_id');
        $this->actingAsStaff($admin)->patch(route('admin.tours.flow', $t), [
            'host_fira_id' => strtolower($org->fira_id), 'deposit_percent' => 40, 'balance_due_days' => 10, 'dates_text' => now()->addDays(60)->toDateString(),
        ])->assertSessionHas('success');
        $this->getJson("/api/tours/{$t->id}")->assertJsonPath('host.name', $org->name)->assertJsonPath('host.verified', true)->assertJsonPath('deposit_percent', 40)
            ->assertJsonPath('next_dates.0.date', now()->addDays(60)->toDateString());
    }

    // ---- Receipts and Academy -----------------------------------------------------

    public function test_a_bank_slip_receipt_opens_only_for_the_payer_through_a_signed_link(): void
    {
        $e = $this->event();
        $user = $this->signedIn();
        $id = $this->bookEvent($e, ['slip' => $this->realPng()])->assertOk()->json('booking_id');
        $payment = Payment::where('event_booking_id', $id)->firstOrFail();

        $this->assertStringContainsString('signature=', (string) $payment->receipt_url);
        $this->actingAs($user)->get($payment->receipt_url)->assertOk();
        $this->actingAs($this->makeUser())->get($payment->receipt_url)->assertStatus(404);
        $this->actingAs($user)->get(route('payments.receipt.show', $payment->id))->assertStatus(403); // unsigned
    }

    public function test_course_progress_up_next_and_lesson_position(): void
    {
        $instructor = $this->makeUser('instructor');
        $courseId = DB::table('courses')->insertGetId(array_filter([
            'instructor_id' => $instructor->id, 'title' => 'Quality and cupping', 'slug' => Schema::hasColumn('courses', 'slug') ? 'c-' . Str::random(8) : null,
            'status' => 'published', 'created_at' => now(), 'updated_at' => now(),
        ], fn ($v) => $v !== null));
        $topicId = DB::table('course_topics')->insertGetId(['course_id' => $courseId, 'title' => 'Module 1', 'order' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $lessons = [];
        foreach ([1, 2, 3] as $n) {
            $lessons[] = DB::table('lessons')->insertGetId(['course_topic_id' => $topicId, 'title' => "Lesson {$n}", 'order' => $n, 'created_at' => now(), 'updated_at' => now()]);
        }
        $user = $this->signedIn();
        $enrollment = new Enrollment();
        $enrollment->forceFill(['user_id' => $user->id, 'course_id' => $courseId, 'status' => Enrollment::STATUS_APPROVED, 'enrolled_at' => now()])->save();
        $this->postJson("/api/user/courses/{$courseId}/lessons/{$lessons[0]}/complete");

        $this->getJson("/api/user/courses/{$courseId}/learn")->assertOk()
            ->assertJsonPath('data.progress.items_total', 3)
            ->assertJsonPath('data.progress.items_done', 1)
            ->assertJsonPath('data.progress.percent', 33)
            ->assertJsonPath('data.progress.up_next.title', 'Lesson 2')
            ->assertJsonPath('data.progress.up_next.number', 2);
        $this->getJson("/api/user/courses/{$courseId}/lessons/{$lessons[1]}")->assertOk()
            ->assertJsonPath('data.position.number', 2)
            ->assertJsonPath('data.position.total', 3)
            ->assertJsonPath('data.position.previous_lesson_id', $lessons[0])
            ->assertJsonPath('data.position.next_lesson_id', $lessons[2]);

        // Academy members get the member ticket.
        $e = $this->event(['member_price' => 300]);
        $this->getJson("/api/events/{$e->id}")->assertJsonPath('flow.tickets.1.eligible', true);
    }
}
