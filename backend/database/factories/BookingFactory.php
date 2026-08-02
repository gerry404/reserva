<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Business;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Live bookings are unique per (business, start), so the default start has
     * to move: building a batch with a fixed date and time would trip the
     * slot_key index instead of testing whatever the batch was for. Callers
     * that care about a specific moment use at().
     */
    private static int $slotCursor = 0;

    public function definition(): array
    {
        $offset = self::$slotCursor++;

        return [
            'business_id'    => Business::factory(),
            'service_id'     => Service::factory(),
            'customer_name'  => fake()->name(),
            'customer_phone' => '+2376' . fake()->numerify('########'),
            'customer_email' => fake()->safeEmail(),
            // A fresh (day, half-hour) pair every time the factory is called.
            'date'           => now()->addDays(1 + intdiv($offset, 48))->toDateString(),
            'time_slot'      => sprintf('%02d:%02d', intdiv($offset % 48, 2), ($offset % 2) * 30),
            'duration'       => 30,
            'price'          => 5000,
            'status'         => Booking::STATUS_PENDING,
        ];
    }

    public function at(string $date, string $time, int $duration = 30): static
    {
        return $this->state([
            'date'      => $date,
            'time_slot' => $time,
            'duration'  => $duration,
        ]);
    }

    public function status(string $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function cancelled(): static
    {
        return $this->status(Booking::STATUS_CANCELLED);
    }

    public function confirmed(): static
    {
        return $this->status(Booking::STATUS_CONFIRMED);
    }
}
