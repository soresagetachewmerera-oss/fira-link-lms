<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventBooking;
use App\Models\User;
use Database\Seeders\MasterclassEventSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EventMasterclassWebTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterclassEventSeeder::class);
    }

    public function test_masterclass_event_is_published_and_free(): void
    {
        $slug = 'coffee-pulses-export-masterclass-a8-a9-a10-cert';
        $event = Event::where('slug', $slug)->first();

        $this->assertNotNull($event);
        $this->assertTrue($event->is_published);
        $this->assertTrue($event->is_online);
        $this->assertTrue($event->is_free);
        $this->assertEquals(0.00, (float) $event->price);
        $this->assertStringContainsString('በተግባር የተፈተነ የቡና እና የጥራጥሬ ኤክስፖርት master-class', $event->title);
        $this->assertStringContainsString('A8,A9,A10', $event->title);
    }

    public function test_events_index_page_displays_masterclass(): void
    {
        $response = $this->get(route('events.index'));

        $response->assertStatus(200);
        $response->assertSee('በተግባር የተፈተነ የቡና እና የጥራጥሬ ኤክስፖርት master-class');
        $response->assertSee('Free');
    }

    public function test_event_show_page_renders_masterclass_details(): void
    {
        $slug = 'coffee-pulses-export-masterclass-a8-a9-a10-cert';
        $response = $this->get(route('events.show', $slug));

        $response->assertStatus(200);
        $response->assertSee('በተግባር የተፈተነ የቡና እና የጥራጥሬ ኤክስፖርት master-class');
        $response->assertSee('A8, A9, A10');
        $response->assertSee('የተግባር የቡና እና የጥራጥሬ ኤክስፖርት ስሌት');
    }

    public function test_authenticated_user_can_view_free_booking_page(): void
    {
        $user = User::factory()->create();
        $slug = 'coffee-pulses-export-masterclass-a8-a9-a10-cert';

        $response = $this->actingAs($user)->get(route('events.book', $slug));

        $response->assertStatus(200);
        $response->assertSee('Free Admission');
        $response->assertSee('Confirm Free Registration');
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
            'notes' => 'Attending A10 Certificate & Practical Masterclass session',
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
}
