<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The public booking flow end to end, including the refusals that matter:
 * a taken slot, an exhausted quota, and a service belonging to somebody else.
 */
class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-03 07:00:00', 'Africa/Douala'));
        Mail::fake();

        $this->business = Business::factory()
            ->openAllWeek('08:00', '18:00')
            ->for(User::factory()->pro(), 'user')
            ->create(['slug' => 'salon-test', 'slot_duration' => 30, 'booking_notice' => 0]);

        $this->service = Service::factory()->for($this->business)->lasting(60)->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_a_customer_can_book_an_open_slot(): void
    {
        $response = $this->postJson('/api/b/salon-test/book', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.status', Booking::STATUS_PENDING)
            ->assertJsonPath('data.time', '10:00');

        $booking = Booking::firstOrFail();

        $this->assertSame(60, $booking->duration);
        $this->assertSame('5000', (string) $booking->price);
        // The interval is derived, and it is what availability reads.
        $this->assertSame('2026-08-04 10:00:00', $booking->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-08-04 11:00:00', $booking->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_the_customer_gets_an_acknowledgement_email(): void
    {
        $this->postJson('/api/b/salon-test/book', $this->payload())->assertCreated();

        Mail::assertQueued(\App\Mail\BookingReceivedNotification::class);
    }

    public function test_a_taken_slot_is_refused(): void
    {
        Booking::factory()->for($this->business)->for($this->service)
            ->at('2026-08-04', '10:00', 60)
            ->create();

        $this->postJson('/api/b/salon-test/book', $this->payload())
            ->assertStatus(409);

        $this->assertSame(1, Booking::count());
    }

    public function test_a_slot_overlapped_by_a_longer_booking_is_refused(): void
    {
        Booking::factory()->for($this->business)->for($this->service)
            ->at('2026-08-04', '09:00', 180)
            ->create();

        // 10:00 is not itself booked, but it sits inside 09:00–12:00.
        $this->postJson('/api/b/salon-test/book', $this->payload())
            ->assertStatus(409);
    }

    public function test_a_slot_outside_opening_hours_is_refused(): void
    {
        $this->postJson('/api/b/salon-test/book', $this->payload(['time_slot' => '21:00']))
            ->assertStatus(409);
    }

    public function test_a_service_from_another_business_cannot_be_booked(): void
    {
        $foreign = Service::factory()->create();

        $this->postJson('/api/b/salon-test/book', $this->payload(['service_id' => $foreign->id]))
            ->assertNotFound();
    }

    public function test_an_inactive_service_cannot_be_booked(): void
    {
        $hidden = Service::factory()->for($this->business)->inactive()->create();

        $this->postJson('/api/b/salon-test/book', $this->payload(['service_id' => $hidden->id]))
            ->assertNotFound();
    }

    public function test_the_free_plan_quota_counts_bookings_created_this_month(): void
    {
        $this->business->user->update(['plan' => User::PLAN_FREE, 'plan_expires_at' => null]);

        Booking::factory()->count(30)->for($this->business)->for($this->service)
            ->create(['created_at' => now()->subDays(2)]);

        $this->postJson('/api/b/salon-test/book', $this->payload())
            ->assertStatus(429);
    }

    public function test_cancelled_bookings_do_not_consume_the_quota(): void
    {
        $this->business->user->update(['plan' => User::PLAN_FREE, 'plan_expires_at' => null]);

        // Thirty cancellations used to lock a merchant out for the rest of the month.
        Booking::factory()->count(30)->for($this->business)->for($this->service)
            ->cancelled()
            ->create(['created_at' => now()->subDays(2)]);

        $this->postJson('/api/b/salon-test/book', $this->payload())
            ->assertCreated();
    }

    public function test_next_month_appointments_still_count_against_this_month(): void
    {
        $this->business->user->update(['plan' => User::PLAN_FREE, 'plan_expires_at' => null]);

        // Counted on created_at: filling a distant diary is still usage today.
        Booking::factory()->count(30)->for($this->business)->for($this->service)
            ->sequence(fn ($sequence) => [
                'date' => now()->addMonths(2)->addDays($sequence->index)->toDateString(),
            ])
            ->create(['created_at' => now()]);

        $this->postJson('/api/b/salon-test/book', $this->payload())
            ->assertStatus(429);
    }

    public function test_an_expired_pro_plan_is_metered_like_a_free_one(): void
    {
        $this->business->user->update([
            'plan'            => User::PLAN_PRO,
            'plan_expires_at' => now()->subDay(),
        ]);

        Booking::factory()->count(30)->for($this->business)->for($this->service)
            ->create(['created_at' => now()]);

        $this->postJson('/api/b/salon-test/book', $this->payload())
            ->assertStatus(429);
    }

    public function test_the_honeypot_field_rejects_bots(): void
    {
        $this->postJson('/api/b/salon-test/book', $this->payload(['website' => 'https://spam.example']))
            ->assertStatus(422);

        $this->assertSame(0, Booking::count());
    }

    public function test_an_unreachable_phone_number_is_rejected(): void
    {
        $this->postJson('/api/b/salon-test/book', $this->payload(['customer_phone' => 'appelez-moi']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('customer_phone');
    }

    public function test_slots_endpoint_reflects_the_service_duration(): void
    {
        $long = Service::factory()->for($this->business)->lasting(180)->create();

        $short = $this->getJson("/api/b/salon-test/slots?service_id={$this->service->id}&date=2026-08-04");
        $wide  = $this->getJson("/api/b/salon-test/slots?service_id={$long->id}&date=2026-08-04");

        $this->assertGreaterThan(count($wide->json('slots')), count($short->json('slots')));
    }

    public function test_an_inactive_business_is_not_reachable(): void
    {
        $this->business->update(['is_active' => false]);

        $this->getJson('/api/b/salon-test')->assertNotFound();
    }

    public function test_the_public_payload_does_not_leak_merchant_data(): void
    {
        $response = $this->getJson('/api/b/salon-test');

        $response->assertOk();
        $this->assertArrayNotHasKey('user_id', $response->json('data'));
        $this->assertArrayNotHasKey('notifications_email', $response->json('data'));
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'service_id'     => $this->service->id,
            'customer_name'  => 'Awa Ndiaye',
            'customer_phone' => '+237691234567',
            'customer_email' => 'awa@example.com',
            'date'           => '2026-08-04',
            'time_slot'      => '10:00',
        ], $overrides);
    }
}
