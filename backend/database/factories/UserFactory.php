<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name'     => fake()->name(),
            'email'    => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'phone'    => '+2376' . fake()->numerify('########'),
            'plan'     => User::PLAN_FREE,
        ];
    }

    public function pro(): static
    {
        return $this->state([
            'plan'            => User::PLAN_PRO,
            'plan_expires_at' => now()->addMonth(),
        ]);
    }

    public function business(): static
    {
        return $this->state([
            'plan'            => User::PLAN_BUSINESS,
            'plan_expires_at' => now()->addMonth(),
        ]);
    }

    /** A paid plan whose term has run out — should behave exactly like free. */
    public function expired(): static
    {
        return $this->state([
            'plan'            => User::PLAN_PRO,
            'plan_expires_at' => now()->subDay(),
        ]);
    }
}
