<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $first_name = $this->faker->firstName();
        $last_name = $this->faker->lastName();

        return [
            'branch_id' => Branch::factory(),
            'user_id' => null,
            'member_no' => 'MBR-' . strtoupper($this->faker->unique()->bothify('??###')),
            'first_name' => $first_name,
            'last_name' => $last_name,
            'gender' => $this->faker->randomElement(['male', 'female']),
            'dob' => $this->faker->dateTimeBetween('-50 years', '-18 years'),
            'phone' => '+255' . $this->faker->numerify('7########'),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->address(),
            'status' => 'active',
            'has_insurance' => $this->faker->boolean(20),
            'notes' => $this->faker->optional(0.3)->sentence(),
        ];
    }

    /**
     * Indicate that the member is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}


