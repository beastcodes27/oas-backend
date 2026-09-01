<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Jobs\VerifyNectaResult;
use App\Models\Application;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();

        // The verification job is dispatched on submission; keep it off the
        // network in these tests (it is covered by its own test suite).
        Queue::fake();
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
                'disability' => '',
                'exam_type' => 'psle',
                'exam_reg_number' => 'PS11001001',
                'exam_year' => 2023,
                'exam_confirmed' => true,
                'exam_result' => [
                    'candidate_name' => 'Amina Khalid',
                    'division' => 'II',
                    'points' => 15,
                ],
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

    public function test_user_cannot_submit_duplicate_entry_level_in_same_window(): void
    {
        $this->school->update([
            'applications_open' => true,
            'window_opens_at' => now()->subDay(),
            'window_closes_at' => now()->addMonth(),
        ]);
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/applications', $this->payload())
            ->assertStatus(201);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/applications', $this->payload())
            ->assertStatus(409);
    }

    public function test_returning_student_can_apply_for_a_new_entry_level_in_a_new_window(): void
    {
        $this->school->update([
            'applications_open' => true,
            'window_opens_at' => now()->subDay(),
            'window_closes_at' => now()->addMonth(),
        ]);
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/applications', $this->payload(['entry_level' => 'Form 1']))
            ->assertStatus(201);

        // Simulate a Form 1 application submitted in a previous intake (2017).
        Application::where('user_id', $user->id)->update(['submitted_at' => '2017-04-15 10:00:00']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/applications', $this->payload(['entry_level' => 'Form 5']))
            ->assertStatus(201);

        $this->assertDatabaseCount('applications', 2);
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

        Queue::assertPushed(VerifyNectaResult::class);
    }

    public function test_owner_can_download_application_form_pdf(): void
    {
        $user = User::factory()->create();
        $application = Application::factory()->create(['user_id' => $user->id, 'school_id' => $this->school->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->get('/api/v1/applications/'.$application->id.'/form');

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertSee('%PDF', false);
    }

    public function test_user_cannot_download_another_users_form(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $application = Application::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other, 'sanctum')
            ->get('/api/v1/applications/'.$application->id.'/form')
            ->assertStatus(403);
    }

    public function test_guest_cannot_download_an_application_form(): void
    {
        $application = Application::factory()->create(['user_id' => User::factory()->create()->id]);

        $this->getJson('/api/v1/applications/'.$application->id.'/form')
            ->assertStatus(401);
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
            ->assertJsonPath('data.status.value', 'pending')
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

    public function test_tracked_status_stays_pending_until_selections_published(): void
    {
        $application = Application::factory()->create([
            'school_id' => $this->school->id,
            'status' => ApplicationStatus::Approved,
        ]);

        $this->getJson('/api/v1/applications/status/'.$application->reference)
            ->assertOk()
            ->assertJsonPath('data.status.value', 'pending');
    }

    public function test_tracked_status_shows_decision_after_selections_published(): void
    {
        $this->school->update(['selections_published_at' => now()]);

        $application = Application::factory()->create([
            'school_id' => $this->school->id,
            'status' => ApplicationStatus::Selected,
            'decided_at' => now(),
        ]);

        $this->getJson('/api/v1/applications/status/'.$application->reference)
            ->assertOk()
            ->assertJsonPath('data.status.value', 'selected');
    }

    public function test_dashboard_exposes_applicant_status_as_pending_until_published(): void
    {
        $user = User::factory()->create();
        Application::factory()->create([
            'user_id' => $user->id,
            'school_id' => $this->school->id,
            'status' => ApplicationStatus::Declined,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/applications')
            ->assertOk()
            ->assertJsonPath('data.0.applicant_status.value', 'pending')
            ->assertJsonPath('data.0.status.value', 'declined');
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
