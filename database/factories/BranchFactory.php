<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Branch>
 */
class BranchFactory extends Factory
{
    protected $model = Branch::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Gym',
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'country' => 'Tanzania',
            'phone' => '+255' . fake()->numerify('7########'),
            'email' => fake()->unique()->companyEmail(),
            'status' => 'active',
        ];
    }

    /**
     * Indicate that the branch is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
