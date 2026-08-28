<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Seed the application's school.
     */
    public function run(): void
    {
        School::query()->updateOrCreate(
            ['name' => 'Shule Yetu'],
            [
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
                    ['name' => 'O-Level', 'forms' => 'Form 1 – Form 4', 'intake' => 'Form 1'],
                    ['name' => 'A-Level', 'forms' => 'Form 5 – Form 6', 'intake' => 'Form 5'],
                ],
                'contact' => [
                    'phone' => '+255 700 000 000',
                    'email' => 'admissions@shuleyetu.ac.tz',
                    'address' => 'P.O. Box 123, Moshi, Kilimanjaro, Tanzania',
                ],
                'window' => '1 March – 30 April 2026',
            ],
        );
    }
}
