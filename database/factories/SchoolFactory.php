<?php

namespace Database\Factories;

use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Shule Yetu',
            'short_name' => 'Shule Yetu',
            'monogram' => 'SY',
            'motto' => 'Knowledge · Discipline · Excellence',
            'type' => 'National School',
            'region' => 'Kilimanjaro',
            'district' => 'Moshi',
            'rating' => 'A',
            'capacity' => 600,
            'forms' => [1, 2, 3, 4, 5, 6],
            'streams' => ['Science', 'Business', 'Humanities'],
            'programs' => [
                ['name' => 'O-Level', 'forms' => 'Form 1 – Form 4'],
                ['name' => 'A-Level', 'forms' => 'Form 5 – Form 6'],
            ],
            'contact' => [
                'phone' => '+255 700 000 000',
                'email' => 'admissions@shuleyetu.ac.tz',
                'address' => 'P.O. Box 123, Moshi, Kilimanjaro, Tanzania',
            ],
            'window' => '1 March – 30 April 2026',
        ];
    }
}
