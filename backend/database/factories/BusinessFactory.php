<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Business>
 */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        return [
            'user_id'                => User::factory(),
            'name'                   => fake()->company(),
            'category'               => 'Salon de coiffure',
            'city'                   => 'Douala',
            'country'                => 'CM',
            'timezone'               => 'Africa/Douala',
            'currency'               => 'XAF',
            'phone'                  => '+2376' . fake()->numerify('########'),
            'whatsapp'               => '+2376' . fake()->numerify('########'),
            'working_hours'          => Business::defaultWorkingHours(),
            'slot_duration'          => 30,
            // Tests that care about lead time opt into it explicitly; the
            // default of zero keeps "today at 09:00" bookable in a fixed clock.
            'booking_notice'         => 0,
            'notifications_whatsapp' => false,
            'notifications_sms'      => false,
            'notifications_email'    => false,
            'is_active'              => true,
            'accent_color'           => '#6366f1',
        ];
    }

    /** @param array<string, array{is_open: bool, open: string, close: string}> $hours */
    public function openAllWeek(string $open = '08:00', string $close = '18:00'): static
    {
        $hours = [];
        foreach (Business::DAYS as $day) {
            $hours[$day] = ['is_open' => true, 'open' => $open, 'close' => $close];
        }

        return $this->state(['working_hours' => $hours]);
    }

    public function closedOn(string $day): static
    {
        return $this->state(function (array $attributes) use ($day) {
            $hours       = $attributes['working_hours'];
            $hours[$day] = ['is_open' => false, 'open' => '08:00', 'close' => '18:00'];

            return ['working_hours' => $hours];
        });
    }
}
