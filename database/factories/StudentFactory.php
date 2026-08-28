<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'gender' => fake()->randomElement(['male', 'female']),
            'birth_date' => fake()->date(),
            'birth_place' => fake()->city(),
            'nationality' => 'Tanzania',
            'region' => 'Kilimanjaro',
            'district' => 'Moshi',
            'ward' => fake()->streetName(),
            'phone' => fake()->numerify('07## ######'),
            'email' => fake()->safeEmail(),
            'previous_school' => fake()->company().' Primary School',
            'previous_class' => 'Standard Seven',
            'previous_marks' => (string) fake()->numberBetween(150, 250),
            'disability' => null,
        ];
    }
}
