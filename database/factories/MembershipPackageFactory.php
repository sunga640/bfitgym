<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\MembershipPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MembershipPackage>
 */
class MembershipPackageFactory extends Factory
{
    protected $model = MembershipPackage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $duration_type = $this->faker->randomElement(['days', 'weeks', 'months', 'years']);
        $duration_value = match ($duration_type) {
            'days' => $this->faker->numberBetween(1, 30),
            'weeks' => $this->faker->numberBetween(1, 12),
            'months' => $this->faker->numberBetween(1, 12),
            'years' => $this->faker->numberBetween(1, 3),
        };

        $packages = [
            'Basic' => [50000, 100000],
            'Standard' => [100000, 200000],
            'Premium' => [200000, 500000],
            'VIP' => [500000, 1000000],
            'Student' => [30000, 80000],
            'Corporate' => [150000, 400000],
            'Family' => [300000, 800000],
            'Trial' => [10000, 30000],
        ];

        $package_name = $this->faker->randomElement(array_keys($packages));
        $price_range = $packages[$package_name];

        return [
            'branch_id' => Branch::factory(),
            'name' => $package_name . ' ' . ucfirst($duration_type) . ' Package',
            'description' => $this->faker->optional(0.7)->sentence(10),
            'price' => $this->faker->numberBetween($price_range[0], $price_range[1]),
            'duration_type' => $duration_type,
            'duration_value' => $duration_value,
            'is_renewable' => $this->faker->boolean(80),
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'inactive']),
        ];
    }

    /**
     * Indicate that the package is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the package is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Create a monthly package.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_type' => 'months',
            'duration_value' => 1,
        ]);
    }

    /**
     * Create an annual package.
     */
    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_type' => 'years',
            'duration_value' => 1,
        ]);
    }

    /**
     * Create a weekly package.
     */
    public function weekly(): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_type' => 'weeks',
            'duration_value' => 1,
        ]);
    }
}

