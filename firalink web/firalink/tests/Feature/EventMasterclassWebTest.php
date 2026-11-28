<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventBooking;
use App\Models\User;
use Database\Seeders\MasterclassEventSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\ApprovesBusinesses;
use Tests\TestCase;

class EventMasterclassWebTest extends TestCase
{
    use DatabaseTransactions, ApprovesBusinesses;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(MasterclassEventSeeder::class);
    }

    public function test_masterclass_event_is_published_and_in_person_at_vamdas_cinema(): void
    {
        $slug = 'coffee-pulses-export-masterclass-a8-a9-a10-cert';
        $event = Event::where('slug', $slug)->first();

        $this->assertNotNull($event);
        $this->assertTrue($event->is_published);
        $this->assertFalse($event->is_online, 'Event must be in-person, not online');
        $this->assertTrue($event->is_free);
        $this->assertEquals(0.00, (float) $event->price);
        $this->assertEquals('2026-12-20', $event->start_date->format('Y-m-d'));
        $this->assertEquals('2026-12-20', $event->end_date->format('Y-m-d'));
        $this->assertStringContainsString('Vamdas Cinema', $event->location);
        $this->assertStringContainsString('በተግባር የተፈተነ የቡና እና የጥራጥሬ ኤክስፖርት master-class', $event->title);
        $this->assertStringContainsString('A8,A9,A10', $event->title);
    }

    public function test_events_index_page_displays_masterclass_in_person_and_venue(): void
    {
        $response = $this->get(route('events.index'));

        $response->assertStatus(200);
        $response->assertSee('በተግባር የተፈተነ የቡና እና የጥራጥሬ ኤክስፖርት master-class');
        $response->assertSee('Vamdas Cinema');
        $response->assertSee('In-Person');
        $response->assertSee('Free');
    }

    public function test_event_show_page_renders_vamdas_cinema_map_and_attendees(): void
    {
        $slug = 'coffee-pulses-export-masterclass-a8-a9-a10-cert';
        $response = $this->get(route('events.show', $slug));

        $response->assertStatus(200);
        $response->assertSee('በተግባር የተፈተነ የቡና እና የጥራጥሬ ኤክስፖርት master-class');
        $response->assertSee('Vamdas Cinema');
        $response->assertSee('https://maps.app.goo.gl/ro2Q5YnH8B7Ybu9j6');
        $response->assertSee('Confirmed Attendees');
    }

    public function test_authenticated_user_can_view_free_booking_page(): void
    {
        $user = User::factory()->create();
        $slug = 'coffee-pulses-export-masterclass-a8-a9-a10-cert';

        $response = $this->actingAs($user)->get(route('events.book', $slug));

        $response->assertStatus(200);
        $response->assertSee('Free Admission');
        $response->assertSee('Confirm Free Registration');
        $response->assertSee('Vamdas Cinema');
    }

    public function test_authenticated_user_can_book_free_ticket_successfully(): void
    {
        $user = User::factory()->create();
        $slug = 'coffee-pulses-export-masterclass-a8-a9-a10-cert';

        $response = $this->actingAs($user)->post(route('events.book.store', $slug), [
            'first_name' => 'Tadesse',
            'last_name' => 'Girma',
            'email' => 'tadesse@example.com',
            'phone' => '+251911998877',
            'company' => 'Cohort A10 Exporter',
            'category' => 'student',
            'student_section' => 'A10',
            'seats' => 1,
            'payment_method' => 'free',
            'ticket_type' => 'general',
            'notes' => 'Attending A10 Certificate & Practical Masterclass session at Vamdas Cinema',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $booking = EventBooking::where('email', 'tadesse@example.com')->first();
        $this->assertNotNull($booking);
        $this->assertEquals(EventBooking::STATUS_CONFIRMED, $booking->status);
        $this->assertEquals(EventBooking::PAYMENT_PAID, $booking->payment_status);
        $this->assertEquals(EventBooking::CATEGORY_STUDENT, $booking->category);
        $this->assertEquals('A10', $booking->student_section);
        $this->assertNotNull($booking->verification_token);
        $this->assertEquals(0.00, (float) $booking->total_amount);
        $this->assertEquals('free', $booking->payment_method);
    }

    public function test_registration_as_student_requires_valid_section_a1_to_a10(): void
    {
        $user = User::factory()->create();
        $slug = 'coffee-pulses-export-masterclass-a8-a9-a10-cert';

        // Missing student section when category is student
        $response = $this->actingAs($user)->post(route('events.book.store', $slug), [
            'first_name' => 'Abebe',
            'last_name' => 'Bikila',
            'email' => 'abebe@example.com',
            'category' => 'student',
            'student_section' => '',
            'seats' => 1,
        ]);
        $response->assertSessionHasErrors(['student_section']);

        // Invalid student section
        $response = $this->actingAs($user)->post(route('events.book.store', $slug), [
            'first_name' => 'Abebe',
            'last_name' => 'Bikila',
            'email' => 'abebe@example.com',
            'category' => 'student',
            'student_section' => 'Z99',
            'seats' => 1,
        ]);
        $response->assertSessionHasErrors(['student_section']);
    }

    public function test_registration_as_general_attendee_succeeds_without_section(): void
    {
        $user = User::factory()->create();
        $slug = 'coffee-pulses-export-masterclass-a8-a9-a10-cert';

        $response = $this->actingAs($user)->post(route('events.book.store', $slug), [
            'first_name' => 'Almaz',
            'last_name' => 'Ayana',
            'email' => 'almaz@example.com',
            'category' => 'general',
            'seats' => 1,
            'payment_method' => 'free',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $booking = EventBooking::where('email', 'almaz@example.com')->first();
        $this->assertNotNull($booking);
        $this->assertEquals(EventBooking::CATEGORY_GENERAL, $booking->category);
        $this->assertNull($booking->student_section);
        $this->assertFalse($booking->isStudent());
        $this->assertEquals('General Attendee', $booking->category_label);
    }

    public function test_gate_verification_and_check_in_flow_with_double_entry_protection(): void
    {
        $user = User::factory()->create([
            'name' => 'Bethlehem Tilahun',
            'profile_photo' => null,
        ]);
        $staffUser = User::factory()->create([
            'name' => 'Gate Staff Official',
            'role' => 'admin',
        ]);

        $event = Event::where('slug', 'coffee-pulses-export-masterclass-a8-a9-a10-cert')->firstOrFail();

        $booking = EventBooking::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'reference' => 'EVT-TEST-' . strtoupper(Str::random(6)),
            'verification_token' => Str::random(40),
            'status' => EventBooking::STATUS_CONFIRMED,
            'payment_status' => EventBooking::PAYMENT_PAID,
            'category' => EventBooking::CATEGORY_STUDENT,
            'student_section' => 'A9',
            'first_name' => 'Bethlehem',
            'last_name' => 'Tilahun',
            'email' => 'bethlehem@example.com',
            'seats' => 1,
            'unit_price' => 0.00,
            'total_amount' => 0.00,
            'currency' => 'ETB',
            'booked_at' => now(),
        ]);

        // 1. Initial State: Valid Pass
        $this->assertFalse($booking->isCheckedIn());
        $verifyUrl = route('events.bookings.verify', $booking->verification_token);

        $response = $this->actingAs($staffUser)->get($verifyUrl);
        $response->assertStatus(200);
        $response->assertSee('VALID TICKET PASS');
        $response->assertSee('Bethlehem Tilahun');
        $response->assertSee('Section A9');
        $response->assertSee('Confirm Entry');

        // 2. Gate Staff Performs Check-In
        $checkInResponse = $this->actingAs($staffUser)->post($verifyUrl);
        $checkInResponse->assertSessionHasNoErrors();
        $checkInResponse->assertSessionHas('success');

        $booking->refresh();
        $this->assertTrue($booking->isCheckedIn());
        $this->assertNotNull($booking->checked_in_at);
        $this->assertEquals($staffUser->id, $booking->checked_in_by);

        // 3. Second Check-In Attempt is Blocked (Duplicate Entry Protection)
        $secondAttempt = $this->actingAs($staffUser)->post($verifyUrl);
        $secondAttempt->assertSessionHas('error');

        $viewAfterCheckIn = $this->actingAs($staffUser)->get($verifyUrl);
        $viewAfterCheckIn->assertSee('ALREADY CHECKED IN');
        $viewAfterCheckIn->assertSee('Pass Already Used');
        $viewAfterCheckIn->assertSee($staffUser->name);
    }

    public function test_admin_gate_scanner_api_and_attendees_export(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Officer',
            'role' => 'admin',
        ]);
        $event = Event::where('slug', 'coffee-pulses-export-masterclass-a8-a9-a10-cert')->firstOrFail();

        $booking = EventBooking::create([
            'event_id' => $event->id,
            'reference' => 'EVT-API-' . strtoupper(Str::random(6)),
            'verification_token' => Str::random(40),
            'status' => EventBooking::STATUS_CONFIRMED,
            'payment_status' => EventBooking::PAYMENT_PAID,
            'category' => EventBooking::CATEGORY_STUDENT,
            'student_section' => 'A8',
            'first_name' => 'Dawit',
            'last_name' => 'Yohannes',
            'email' => 'dawit@example.com',
            'seats' => 2,
            'unit_price' => 0.00,
            'total_amount' => 0.00,
            'currency' => 'ETB',
            'booked_at' => now(),
        ]);

        // 1. Scanner API Lookup
        $lookupResp = $this->actingAsStaff($admin)->postJson(route('admin.events.scanner.lookup', $event), [
            'code' => $booking->reference,
        ]);
        $lookupResp->assertStatus(200);
        $lookupResp->assertJsonPath('success', true);
        $lookupResp->assertJsonPath('booking.reference', $booking->reference);
        $lookupResp->assertJsonPath('booking.student_section', 'A8');
        $lookupResp->assertJsonPath('booking.status_state', 'valid');

        // 2. Scanner API Check-In
        $checkInResp = $this->actingAsStaff($admin)->postJson(route('admin.events.scanner.checkin', ['event' => $event->id, 'booking' => $booking->id]));
        $checkInResp->assertStatus(200);
        $checkInResp->assertJsonPath('success', true);
        $checkInResp->assertJsonPath('status', 'checked_in');

        // 3. Scanner API Double Check-In Rejection
        $doubleCheckInResp = $this->actingAsStaff($admin)->postJson(route('admin.events.scanner.checkin', ['event' => $event->id, 'booking' => $booking->id]));
        $doubleCheckInResp->assertStatus(422);
        $doubleCheckInResp->assertJsonPath('status', 'already_checked_in');

        // 4. Admin Attendees Dashboard View & Filter
        $attendeesView = $this->actingAsStaff($admin)->get(route('admin.events.attendees', [
            'event' => $event->id,
            'category' => 'student',
            'section' => 'A8',
        ]));
        $attendeesView->assertStatus(200);
        $attendeesView->assertSee('Dawit Yohannes');
        $attendeesView->assertSee('Section A8');
        $attendeesView->assertSee('CHECKED IN');

        // 5. Admin Attendees CSV Export
        $exportResp = $this->actingAsStaff($admin)->get(route('admin.events.attendees.export', $event));
        $exportResp->assertStatus(200);
        $this->assertEquals('text/csv; charset=UTF-8', $exportResp->headers->get('content-type'));
    }

    public function test_user_can_upload_profile_photo_and_initials_fallback(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => 'Kassahun Melaku',
            'role' => 'student',
            'profile_photo' => null,
        ]);

        // Default state: no photo, URL is null, initials is KM
        $this->assertFalse($user->hasProfilePhoto());
        $this->assertNull($user->profile_photo_url);
        $this->assertEquals('KM', $user->initials);

        // Upload photo
        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        $response = $this->actingAs($user)->post(route('student.settings.update'), [
            'name' => 'Kassahun Melaku',
            'email' => $user->email,
            'phone' => '+251911223344',
            'profile_photo' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('student.settings'));

        $user->refresh();
        $this->assertTrue($user->hasProfilePhoto());
        $this->assertNotNull($user->profile_photo);
        $this->assertNotNull($user->profile_photo_url);
        Storage::disk('public')->assertExists($user->profile_photo);
    }
}
