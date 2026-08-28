<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => 'OAS-'.strtoupper(fake()->unique()->bothify('####??')).'-'.date('Y'),
            'user_id' => User::factory(),
            'student_id' => Student::factory(),
            'school_id' => School::factory(),
            'entry_level' => fake()->randomElement(['Form 1', 'Form 5']),
            'status' => ApplicationStatus::Pending,
            'submitted_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ];
    }

    public function selected(): static
    {
        return $this->state(fn () => [
            'status' => ApplicationStatus::Selected,
            'decided_at' => now(),
        ]);
    }
}
