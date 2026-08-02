<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $merchant;
    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->merchant = User::factory()->create();
        $this->business = Business::factory()->for($this->merchant, 'user')->create();
        $this->merchant->refresh();
    }

    public function test_a_service_carries_both_the_url_and_the_path_of_each_image(): void
    {
        $service = Service::factory()->for($this->business)->create();

        $this->actingAs($this->merchant)->postJson("/api/services/{$service->id}", [
            '_method'  => 'PUT',
            'images'   => [UploadedFile::fake()->image('coupe.jpg')],
        ])->assertOk();

        $response = $this->actingAs($this->merchant)->getJson('/api/services')->assertOk();

        $image = $response->json('data.0.images.0');

        // `url` renders in an <img>; `path` is the identifier the edit form
        // sends back to say which images to keep. Both are needed.
        $this->assertArrayHasKey('url', $image);
        $this->assertArrayHasKey('path', $image);
        Storage::disk('public')->assertExists($image['path']);
    }

    public function test_removing_an_image_deletes_it_from_storage(): void
    {
        $service = Service::factory()->for($this->business)->create();

        $this->actingAs($this->merchant)->postJson("/api/services/{$service->id}", [
            '_method' => 'PUT',
            'images'  => [
                UploadedFile::fake()->image('a.jpg'),
                UploadedFile::fake()->image('b.jpg'),
            ],
        ])->assertOk();

        [$keep, $drop] = $service->fresh()->images;

        $this->actingAs($this->merchant)->postJson("/api/services/{$service->id}", [
            '_method'         => 'PUT',
            'existing_images' => [$keep],
        ])->assertOk();

        $this->assertSame([$keep], $service->fresh()->images);
        Storage::disk('public')->assertMissing($drop);
    }

    /**
     * existing_images names paths on a shared disk, so an unfiltered value
     * would let one merchant delete — or adopt — another's pictures.
     */
    public function test_a_foreign_image_path_cannot_be_adopted(): void
    {
        $service = Service::factory()->for($this->business)->create(['images' => ['services/mine.jpg']]);

        $this->actingAs($this->merchant)->postJson("/api/services/{$service->id}", [
            '_method'         => 'PUT',
            'existing_images' => ['services/mine.jpg', 'services/somebody-elses.jpg'],
        ])->assertOk();

        $this->assertSame(['services/mine.jpg'], $service->fresh()->images);
    }

    public function test_a_service_with_upcoming_bookings_cannot_be_deleted(): void
    {
        $service = Service::factory()->for($this->business)->create();

        Booking::factory()->for($this->business)->for($service)
            ->confirmed()
            ->at(now()->addDays(3)->toDateString(), '10:00')
            ->create();

        $this->actingAs($this->merchant)->deleteJson("/api/services/{$service->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('services', ['id' => $service->id]);
    }

    public function test_a_service_without_upcoming_bookings_can_be_deleted(): void
    {
        $service = Service::factory()->for($this->business)->create();

        $this->actingAs($this->merchant)->deleteJson("/api/services/{$service->id}")
            ->assertOk();

        $this->assertDatabaseMissing('services', ['id' => $service->id]);
    }

    public function test_a_service_longer_than_a_working_day_is_refused(): void
    {
        $this->actingAs($this->merchant)->postJson('/api/services', [
            'name'     => 'Marathon',
            'duration' => 600,
            'price'    => 1000,
        ])->assertStatus(422)->assertJsonValidationErrors('duration');
    }

    public function test_editing_a_service_does_not_rewrite_existing_bookings(): void
    {
        $service = Service::factory()->for($this->business)->create(['duration' => 60, 'price' => 5000]);

        $booking = Booking::factory()->for($this->business)->for($service)
            ->at(now()->addDay()->toDateString(), '10:00', 60)
            ->create(['price' => 5000]);

        $this->actingAs($this->merchant)->postJson("/api/services/{$service->id}", [
            '_method'  => 'PUT',
            'duration' => 120,
            'price'    => 9000,
        ])->assertOk();

        // The booking keeps what the customer agreed to.
        $booking->refresh();
        $this->assertSame(60, $booking->duration);
        $this->assertSame('5000', (string) $booking->price);
        $this->assertSame('11:00', $booking->ends_at->format('H:i'));
    }
}
