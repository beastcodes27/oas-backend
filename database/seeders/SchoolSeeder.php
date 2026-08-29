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
                'combinations' => [
                    'PCM — Physics, Chemistry, Advanced Mathematics',
                    'PCB — Physics, Chemistry, Biology',
                    'PGM — Physics, Geography, Advanced Mathematics',
                    'HGL — History, Geography, Kiswahili',
                    'HKL — History, Kiswahili, Literature in English',
                ],
                'result_links' => [
                    ['name' => 'Form 4 Results 2023', 'url' => 'https://onlinesys.necta.go.tz/results/2023/csee/results/p0138.htm'],
                    ['name' => 'Form 2 Results 2024', 'url' => 'https://onlinesys.necta.go.tz/results/2024/ftna/results/P0104.htm'],
                ],
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
                'applications_open' => true,
                'window_opens_at' => now()->subDays(30),
                'window_closes_at' => now()->addDays(60),
            ],
        );
    }
}
