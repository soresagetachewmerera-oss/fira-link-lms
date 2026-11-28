<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventBooking;
use App\Models\User;
use Database\Seeders\MasterclassEventSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventMasterclassWebTest extends TestCase
{
    use DatabaseTransactions;

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
        $this->assertEquals(0.00, (float) $booking->total_amount);
        $this->assertEquals('free', $booking->payment_method);
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
