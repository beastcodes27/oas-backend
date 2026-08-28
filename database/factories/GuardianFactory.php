<?php

namespace Database\Factories;

use App\Models\Guardian;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
class GuardianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'name' => fake()->name(),
            'relation' => fake()->randomElement(['Father', 'Mother', 'Guardian']),
            'phone' => fake()->numerify('07## ######'),
            'email' => fake()->safeEmail(),
            'occupation' => fake()->jobTitle(),
            'address' => fake()->address(),
        ];
    }
}
