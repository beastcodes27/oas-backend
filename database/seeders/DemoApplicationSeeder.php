<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Guardian;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoApplicationSeeder extends Seeder
{
    /**
     * Seed a small set of demo applications for local development.
     */
    public function run(): void
    {
        if (Application::query()->exists()) {
            return;
        }

        $school = School::query()->firstOrFail();

        $demo = [
            [
                'name' => 'Amina Khalid',
                'email' => 'amina@example.com',
                'entry_level' => 'Form 5',
                'status' => ApplicationStatus::Selected,
                'student' => ['first_name' => 'Amina', 'last_name' => 'Khalid', 'gender' => 'female', 'region' => 'Kilimanjaro', 'district' => 'Moshi', 'ward' => 'Korongoni'],
                'guardian' => ['name' => 'Khalid Hassan', 'relation' => 'Father', 'phone' => '0755 100 100'],
            ],
            [
                'name' => 'Baraka Joseph',
                'email' => 'baraka@example.com',
                'entry_level' => 'Form 1',
                'status' => ApplicationStatus::Verified,
                'student' => ['first_name' => 'Baraka', 'last_name' => 'Joseph', 'gender' => 'male', 'region' => 'Arusha', 'district' => 'Meru', 'ward' => 'Maji ya Chai'],
                'guardian' => ['name' => 'Joseph Peter', 'relation' => 'Father', 'phone' => '0712 200 200'],
            ],
            [
                'name' => 'Zawadi William',
                'email' => 'zawadi@example.com',
                'entry_level' => 'Form 1',
                'status' => ApplicationStatus::Reviewing,
                'student' => ['first_name' => 'Zawadi', 'last_name' => 'William', 'gender' => 'female', 'region' => 'Mbeya', 'district' => 'Mbeya CC', 'ward' => 'Iganjo'],
                'guardian' => ['name' => 'William Mwakyusa', 'relation' => 'Guardian', 'phone' => '0765 300 300'],
            ],
        ];

        foreach ($demo as $item) {
            $user = User::query()->create([
                'name' => $item['name'],
                'email' => $item['email'],
                'phone' => $item['guardian']['phone'],
                'password' => 'password',
            ]);

            $student = $user->students()->create($item['student']);
            Guardian::query()->create(array_merge($item['guardian'], ['student_id' => $student->id]));

            $student->applications()->create([
                'user_id' => $user->id,
                'school_id' => $school->id,
                'entry_level' => $item['entry_level'],
                'reference' => 'OAS-'.strtoupper(bin2hex(random_bytes(3))).'-'.date('Y'),
                'status' => $item['status'],
                'submitted_at' => now()->subDays(15),
                'decided_at' => $item['status']->isFinal() ? now()->subDays(2) : null,
            ]);
        }
    }
}
