<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'name'        => fake()->words(2, true),
            'description' => fake()->sentence(),
            'duration'    => 30,
            'price'       => 5000,
            'color'       => '#6366f1',
            'is_active'   => true,
        ];
    }

    public function lasting(int $minutes): static
    {
        return $this->state(['duration' => $minutes]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
