<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NectaLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_lookup_is_available_without_authentication(): void
    {
        $this->getJson('/api/v1/necta/lookup')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['exam_type', 'reg_number']);
    }

    public function test_lookup_returns_a_deterministic_result(): void
    {
        Http::fake([
            'onlinesys.necta.go.tz/*' => function ($request) {
                if (str_contains($request->url(), '/index.htm')) {
                    return Http::response('<a href="results/p0104.htm">P0104</a>', 200);
                }

                return Http::response('
                    <html><body>
                    <h3>CSEE 2024 EXAMINATION RESULTS P0104 - KIBOBO SECONDARY SCHOOL CENTRE DIVISION</h3>
                    <table>
                      <tr><td>P0104/0002</td><td>M</td><td>17</td><td>III</td><td>CIV-\'B\' HIST-\'C\' GEO-\'D\'</td></tr>
                    </table>
                    </body></html>', 200);
            },
        ]);

        $response = $this->getJson('/api/v1/necta/lookup?exam_type=csee&reg_number=S0104%2F0002%2F2024');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'candidate_name',
                    'school_name',
                    'cno',
                    'exam_type',
                    'exam_label',
                    'reg_number',
                    'division',
                    'points',
                    'subjects' => [
                        ['name', 'grade'],
                    ],
                ],
            ])
            ->assertJsonPath('data.cno', 'P0104/0002')
            ->assertJsonPath('data.division', 'III')
            ->assertJsonPath('data.points', 17)
            ->assertJsonPath('data.school_name', 'Kibobo Secondary School')
            ->assertJsonPath('data.reg_number', 'S0104/0002/2024');

        $first = $response->json('data');
        $second = $this->getJson('/api/v1/necta/lookup?exam_type=csee&reg_number=S0104%2F0002%2F2024')
            ->json('data');

        $this->assertSame($first, $second);
    }

    public function test_lookup_rejects_an_invalid_exam_type(): void
    {
        $this->getJson('/api/v1/necta/lookup?exam_type=university&reg_number=PS0101%2F0023%2F2024')
            ->assertStatus(422)
            ->assertJsonValidationErrors('exam_type');
    }

    public function test_application_accepts_form_3_entry_with_ftna(): void
    {
        Queue::fake();

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
                    'exam_reg_number' => 'E0231/0456/2022',
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
