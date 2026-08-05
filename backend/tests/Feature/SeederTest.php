<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Business;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The demo data has to obey the same rules as real data.
 *
 * A seeder that can create states the application refuses to create is a seeder
 * that hides bugs: the old one wrote overlapping bookings straight to the
 * table, which is precisely the defect it should have exposed.
 */
class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_data_seeds_without_overlapping_bookings(): void
    {
        $this->seed(DatabaseSeeder::class);

        $business = Business::firstOrFail();

        $this->assertNotNull($business->user);
        $this->assertGreaterThan(0, $business->allServices()->count());

        $bookings = Booking::query()->forBusiness($business->id)->active()->get();
        $this->assertGreaterThan(0, $bookings->count());

        foreach ($bookings as $booking) {
            $clashes = Booking::query()
                ->forBusiness($business->id)
                ->active()
                ->whereKeyNot($booking->id)
                ->overlapping($booking->starts_at, $booking->ends_at)
                ->count();

            $this->assertSame(0, $clashes, "Booking {$booking->reference} overlaps another");
        }
    }

    public function test_the_seeded_business_is_reachable_from_its_public_page(): void
    {
        $this->seed(DatabaseSeeder::class);

        $slug = Business::firstOrFail()->slug;

        $this->getJson("/api/b/{$slug}")
            ->assertOk()
            ->assertJsonPath('data.slug', $slug);
    }
}
