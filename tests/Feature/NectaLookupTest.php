<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NectaLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_requires_authentication(): void
    {
        $this->postJson('/api/v1/necta/lookup', [
            'exam_type' => 'psle',
            'reg_number' => 'PS11001001',
            'year' => 2023,
        ])->assertStatus(401);
    }

    public function test_lookup_returns_a_deterministic_result(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/necta/lookup', [
                'exam_type' => 'psle',
                'reg_number' => 'PS11001001',
                'year' => 2023,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'candidate_name',
                    'school_name',
                    'exam_type',
                    'exam_label',
                    'reg_number',
                    'year',
                    'division',
                    'points',
                    'subjects' => [
                        ['name', 'grade'],
                    ],
                ],
            ])
            ->assertJsonPath('data.reg_number', 'PS11001001')
            ->assertJsonPath('data.year', 2023)
            ->assertJsonPath('data.school_name', fn (string $value) => $value !== '');

        $first = $response->json('data');
        $second = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/necta/lookup', [
                'exam_type' => 'psle',
                'reg_number' => 'PS11001001',
                'year' => 2023,
            ])
            ->json('data');

        $this->assertSame($first, $second);
    }

    public function test_lookup_rejects_an_invalid_exam_type(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/necta/lookup', [
                'exam_type' => 'university',
                'reg_number' => 'PS11001001',
                'year' => 2023,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('exam_type');
    }

    public function test_application_accepts_form_3_entry_with_ftna(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/applications', [
                'entry_level' => 'Form 3',
                'school_id' => $school->id,
                'student' => [
                    'first_name' => 'Neema',
                    'last_name' => 'Msaki',
                    'region' => 'Kilimanjaro',
                    'district' => 'Moshi',
                    'ward' => 'Korongoni',
                    'phone' => '0755 100 100',
                    'exam_type' => 'ftna',
                    'exam_reg_number' => 'F2.001.2022',
                    'exam_year' => 2022,
                    'exam_confirmed' => true,
                ],
                'guardian' => [
                    'name' => 'Parent One',
                    'relation' => 'Mother',
                ],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.entry_level', 'Form 3')
            ->assertJsonPath('data.student.exam_type', 'ftna');
    }

    public function test_application_requires_exam_confirmation(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/applications', [
                'entry_level' => 'Form 1',
                'school_id' => $school->id,
                'student' => [
                    'first_name' => 'Amina',
                    'last_name' => 'Khalid',
                    'region' => 'Kilimanjaro',
                    'district' => 'Moshi',
                    'ward' => 'Korongoni',
                    'phone' => '0755 100 100',
                    'exam_type' => 'psle',
                    'exam_reg_number' => 'PS11001001',
                    'exam_year' => 2023,
                    'exam_confirmed' => false,
                ],
                'guardian' => [
                    'name' => 'Parent One',
                    'relation' => 'Father',
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('student.exam_confirmed');
    }
}
