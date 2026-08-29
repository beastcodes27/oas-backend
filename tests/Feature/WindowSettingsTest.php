<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WindowSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_school_resource_exposes_window_settings(): void
    {
        $school = School::factory()->create([
            'applications_open' => true,
            'window_opens_at' => now()->subDay(),
            'window_closes_at' => now()->addMonth(),
        ]);

        $this->getJson('/api/v1/schools')
            ->assertOk()
            ->assertJsonPath('data.0.id', $school->id)
            ->assertJsonPath('data.0.applications_open', true)
            ->assertJsonStructure([
                'data' => [
                    ['applications_open', 'window_opens_at', 'window_closes_at'],
                ],
            ]);
    }

    public function test_admin_can_open_and_close_the_application_window(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $school = School::factory()->create(['applications_open' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/staff/settings/window', [
                'applications_open' => false,
                'window_opens_at' => '2027-03-01 00:00:00',
                'window_closes_at' => '2027-04-30 23:59:59',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.applications_open', false);

        $this->assertDatabaseHas('schools', [
            'id' => $school->id,
            'applications_open' => false,
        ]);
    }

    public function test_window_update_requires_valid_dates(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/staff/settings/window', [
                'applications_open' => 'not-a-bool',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('applications_open');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/staff/settings/window', [
                'applications_open' => true,
                'window_opens_at' => '2027-05-01',
                'window_closes_at' => '2027-04-01',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('window_closes_at');
    }

    public function test_non_admin_cannot_change_the_window(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/staff/settings/window', ['applications_open' => false])
            ->assertStatus(403);
    }

    public function test_admission_officer_can_change_the_window(): void
    {
        $officer = User::factory()->create(['is_admissions' => true]);
        $school = School::factory()->create(['applications_open' => true]);

        $this->actingAs($officer, 'sanctum')
            ->patchJson('/api/v1/staff/settings/window', ['applications_open' => false])
            ->assertOk();

        $this->assertDatabaseHas('schools', ['id' => $school->id, 'applications_open' => false]);
    }

    public function test_application_submission_is_rejected_when_window_is_closed(): void
    {
        $user = User::factory()->create();
        $school = School::factory()->create(['applications_open' => false]);

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
                    'exam_confirmed' => true,
                ],
                'guardian' => [
                    'name' => 'Khalid Hassan',
                    'relation' => 'Father',
                ],
            ])
            ->assertStatus(403)
            ->assertJson(['message' => 'Applications are currently closed. The next application window has not opened yet.']);

        $this->assertDatabaseCount('applications', 0);
    }
}
