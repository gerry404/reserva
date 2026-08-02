<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * The rules that decide what a customer is allowed to book.
 *
 * The headline case is the one Nuvo shipped wrong: a booking used to occupy
 * only the slot it started on, so a three-hour service left every half hour
 * after it bookable and six customers could be promised the same chair.
 */
class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $availability;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        // A fixed Monday, so weekday-dependent opening hours are deterministic.
        Carbon::setTestNow(Carbon::parse('2026-08-03 07:00:00', 'Africa/Douala'));

        $this->availability = app(AvailabilityService::class);
        $this->business     = Business::factory()->openAllWeek('08:00', '18:00')->create([
            'slot_duration'  => 30,
            'booking_notice' => 0,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_offers_every_slot_of_an_empty_day(): void
    {
        $service = $this->service(30);

        $slots = $this->slotsFor($service);

        // 08:00 → 17:30 inclusive, in half-hour steps.
        $this->assertCount(20, $slots);
        $this->assertSame('08:00', $slots[0]);
        $this->assertSame('17:30', end($slots));
    }

    public function test_a_long_service_blocks_every_slot_it_spans(): void
    {
        $service = $this->service(180);

        Booking::factory()->for($this->business)->for($service)
            ->at($this->tomorrow(), '10:00', 180)
            ->create();

        $slots = $this->slotsFor($service);

        // This is the bug: 10:30 through 12:30 used to stay on offer.
        foreach (['10:00', '10:30', '11:00', '11:30', '12:00', '12:30'] as $taken) {
            $this->assertNotContains($taken, $slots, "{$taken} overlaps a 3h booking and must not be offered");
        }

        // The slot the booking ends on is free again — intervals are half-open.
        $this->assertContains('13:00', $slots);

        // And nothing earlier fits: a three-hour service starting anywhere from
        // 08:00 would still be running when the 10:00 booking begins, so the
        // whole morning is correctly unavailable *for this service*.
        $this->assertNotContains('08:00', $slots);
        $this->assertNotContains('09:30', $slots);
    }

    public function test_a_short_service_cannot_start_inside_a_longer_booking(): void
    {
        $long  = $this->service(180);
        $short = $this->service(30);

        Booking::factory()->for($this->business)->for($long)
            ->at($this->tomorrow(), '10:00', 180)
            ->create();

        $slots = $this->slotsFor($short);

        $this->assertNotContains('11:00', $slots);
        $this->assertContains('13:00', $slots);
    }

    public function test_a_service_must_finish_before_closing_time(): void
    {
        $service = $this->service(120);

        $slots = $this->slotsFor($service);

        // Closing is 18:00, so the last two-hour start is 16:00.
        $this->assertSame('16:00', end($slots));
        $this->assertNotContains('16:30', $slots);
        $this->assertNotContains('17:00', $slots);
    }

    public function test_a_cancelled_booking_frees_its_slot(): void
    {
        $service = $this->service(60);

        Booking::factory()->for($this->business)->for($service)
            ->at($this->tomorrow(), '10:00', 60)
            ->cancelled()
            ->create();

        $this->assertContains('10:00', $this->slotsFor($service));
    }

    public function test_a_no_show_keeps_its_slot(): void
    {
        $service = $this->service(60);

        Booking::factory()->for($this->business)->for($service)
            ->at($this->tomorrow(), '10:00', 60)
            ->status(Booking::STATUS_NO_SHOW)
            ->create();

        // The merchant waited through it; the hour was really spent.
        $this->assertNotContains('10:00', $this->slotsFor($service));
    }

    public function test_a_closed_day_offers_nothing(): void
    {
        $business = Business::factory()->openAllWeek()->closedOn('mardi')->create();
        $service  = Service::factory()->for($business)->lasting(30)->create();

        // 2026-08-04 is a Tuesday.
        $this->assertSame([], $this->availability->slotsFor($business, $service, '2026-08-04'));
    }

    public function test_booking_notice_hides_slots_that_are_too_soon(): void
    {
        $business = Business::factory()->openAllWeek('08:00', '18:00')->create([
            'slot_duration'  => 30,
            'booking_notice' => 120,
        ]);
        $service = Service::factory()->for($business)->lasting(30)->create();

        // Now is 07:00; with two hours' notice nothing before 09:00 qualifies.
        $slots = $this->availability->slotsFor($business, $service, '2026-08-03');

        $this->assertNotContains('08:00', $slots);
        $this->assertNotContains('08:30', $slots);
        $this->assertContains('09:00', $slots);
    }

    public function test_past_slots_are_never_offered_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-03 12:15:00', 'Africa/Douala'));

        $service = $this->service(30);
        $slots   = $this->availability->slotsFor($this->business, $service, '2026-08-03');

        $this->assertNotContains('12:00', $slots);
        $this->assertContains('12:30', $slots);
    }

    public function test_availability_is_computed_on_the_business_clock_not_the_server(): void
    {
        // 07:00 in Douala (UTC+1) is 09:00 in Nairobi (UTC+3). A Nairobi salon
        // opening at 08:00 has already been open an hour; slots before 09:00
        // are in its past, not its future.
        $nairobi = Business::factory()->openAllWeek('08:00', '18:00')->create([
            'timezone'       => 'Africa/Nairobi',
            'slot_duration'  => 30,
            'booking_notice' => 0,
        ]);
        $service = Service::factory()->for($nairobi)->lasting(30)->create();

        $slots = $this->availability->slotsFor($nairobi, $service, '2026-08-03');

        $this->assertNotContains('08:00', $slots);
        $this->assertNotContains('08:30', $slots);
        $this->assertContains('09:00', $slots);
    }

    public function test_malformed_opening_hours_read_as_closed(): void
    {
        $business = Business::factory()->create([
            'working_hours' => [
                // Closing before opening: a range that used to spin out slots.
                'lundi' => ['is_open' => true, 'open' => '18:00', 'close' => '08:00'],
            ],
        ]);
        $service = Service::factory()->for($business)->lasting(30)->create();

        $this->assertSame([], $this->availability->slotsFor($business, $service, '2026-08-03'));
    }

    public function test_open_days_report_remaining_capacity_per_day(): void
    {
        $service = $this->service(30);

        $days = $this->availability->openDaysBetween(
            $this->business,
            $service,
            '2026-08-03',
            '2026-08-05',
        );

        $this->assertSame(['2026-08-03', '2026-08-04', '2026-08-05'], array_keys($days));
        $this->assertSame(20, $days['2026-08-04']);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function service(int $minutes): Service
    {
        return Service::factory()->for($this->business)->lasting($minutes)->create();
    }

    private function tomorrow(): string
    {
        return '2026-08-04';
    }

    /** @return list<string> */
    private function slotsFor(Service $service): array
    {
        return $this->availability->slotsFor($this->business, $service, $this->tomorrow());
    }
}
