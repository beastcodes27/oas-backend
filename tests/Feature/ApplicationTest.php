<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'entry_level' => 'Form 1',
            'school_id' => $this->school->id,
            'student' => [
                'first_name' => 'Amina',
                'last_name' => 'Khalid',
                'gender' => 'female',
                'birth_date' => '2013-05-12',
                'birth_place' => 'Moshi',
                'nationality' => 'Tanzania',
                'region' => 'Kilimanjaro',
                'district' => 'Moshi',
                'ward' => 'Korongoni',
                'phone' => '0755 100 100',
                'email' => 'amina.student@example.com',
                'previous_school' => 'Umoja Primary School',
                'previous_class' => 'Standard Seven',
                'previous_marks' => '195',
                'disability' => '',
            ],
            'guardian' => [
                'name' => 'Khalid Hassan',
                'relation' => 'Father',
                'phone' => '0755 100 100',
                'email' => 'khalid@example.com',
                'occupation' => 'Teacher',
                'address' => 'P.O. Box 45, Moshi',
            ],
        ], $overrides);
    }

    public function test_unauthenticated_user_cannot_submit_an_application(): void
    {
        $this->postJson('/api/v1/applications', $this->payload())
            ->assertStatus(401);
    }

    public function test_authenticated_user_can_submit_an_application(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/applications', $this->payload());

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'reference',
                    'entry_level',
                    'status' => ['value', 'label'],
                    'student' => ['first_name', 'last_name'],
                    'guardian' => ['name', 'relation'],
                    'school' => ['name'],
                ],
            ]);

        $this->assertDatabaseCount('applications', 1);
        $this->assertDatabaseCount('students', 1);
        $this->assertDatabaseCount('guardians', 1);

        $this->assertDatabaseHas('applications', [
            'user_id' => $user->id,
            'entry_level' => 'Form 1',
            'status' => ApplicationStatus::Pending->value,
        ]);
    }

    public function test_application_requires_valid_student_details(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/applications', $this->payload([
                'student' => ['first_name' => '', 'region' => ''],
            ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student.first_name', 'student.region']);
    }

    public function test_application_rejects_invalid_entry_level(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/applications', $this->payload(['entry_level' => 'Form 9']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors('entry_level');
    }

    public function test_user_can_list_own_applications(): void
    {
        $user = User::factory()->create();
        Application::factory()->count(2)->create(['user_id' => $user->id]);

        $other = User::factory()->create();
        Application::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/applications');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_application_can_be_tracked_by_reference(): void
    {
        $application = Application::factory()->create([
            'school_id' => $this->school->id,
            'status' => ApplicationStatus::Reviewing,
        ]);

        $this->getJson('/api/v1/applications/status/'.$application->reference)
            ->assertOk()
            ->assertJsonPath('data.reference', $application->reference)
            ->assertJsonPath('data.status.value', 'reviewing')
            ->assertJsonStructure([
                'data' => [
                    'reference',
                    'student_name',
                    'school',
                    'status' => ['value', 'label'],
                    'timeline' => [
                        ['title', 'text', 'state'],
                    ],
                ],
            ]);
    }

    public function test_tracking_unknown_reference_returns_404(): void
    {
        $this->getJson('/api/v1/applications/status/OAS-UNKNOWN-2026')
            ->assertStatus(404);
    }

    public function test_non_admin_cannot_access_staff_endpoints(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/staff/applications')
            ->assertStatus(403);
    }

    public function test_admin_can_list_applications(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Application::factory()->count(3)->create(['school_id' => $this->school->id]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/staff/applications')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_update_application_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $application = Application::factory()->create([
            'school_id' => $this->school->id,
            'status' => ApplicationStatus::Pending,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/staff/applications/'.$application->id.'/status', [
                'status' => 'selected',
                'notes' => 'Admitted to Form 1',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status.value', 'selected');

        $application->refresh();

        $this->assertEquals(ApplicationStatus::Selected, $application->status);
        $this->assertNotNull($application->decided_at);
        $this->assertEquals('Admitted to Form 1', $application->decision_notes);
    }
}
