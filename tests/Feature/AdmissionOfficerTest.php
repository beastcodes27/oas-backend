<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionOfficerTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
    }

    private function officer(): User
    {
        return User::factory()->create(['is_admissions' => true]);
    }

    public function test_officer_can_view_all_applications(): void
    {
        Application::factory()->count(2)->create(['school_id' => $this->school->id]);

        $this->actingAs($this->officer(), 'sanctum')
            ->getJson('/api/v1/staff/applications')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_officer_can_verify_an_application(): void
    {
        $application = Application::factory()->create([
            'school_id' => $this->school->id,
            'status' => ApplicationStatus::Pending,
        ]);

        $this->actingAs($this->officer(), 'sanctum')
            ->patchJson('/api/v1/staff/applications/'.$application->id.'/status', ['status' => 'verified'])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'verified');
    }

    public function test_officer_can_record_a_draft_decision(): void
    {
        $application = Application::factory()->create([
            'school_id' => $this->school->id,
            'status' => ApplicationStatus::Reviewing,
        ]);

        $this->actingAs($this->officer(), 'sanctum')
            ->patchJson('/api/v1/staff/applications/'.$application->id.'/status', [
                'status' => 'approved',
                'notes' => 'Strong PSLE results',
            ])
            ->assertOk()
            ->assertJsonPath('data.status.value', 'approved');
    }

    public function test_officer_cannot_set_final_selection_statuses(): void
    {
        $application = Application::factory()->create([
            'school_id' => $this->school->id,
            'status' => ApplicationStatus::Reviewing,
        ]);

        $this->actingAs($this->officer(), 'sanctum')
            ->patchJson('/api/v1/staff/applications/'.$application->id.'/status', ['status' => 'selected'])
            ->assertStatus(403);
    }

    public function test_officer_cannot_change_a_finalised_application(): void
    {
        $application = Application::factory()->create([
            'school_id' => $this->school->id,
            'status' => ApplicationStatus::Selected,
        ]);

        $this->actingAs($this->officer(), 'sanctum')
            ->patchJson('/api/v1/staff/applications/'.$application->id.'/status', ['status' => 'pending'])
            ->assertStatus(403);
    }

    public function test_officer_cannot_publish_selections(): void
    {
        $this->actingAs($this->officer(), 'sanctum')
            ->patchJson('/api/v1/admin/selections/publish')
            ->assertStatus(403);
    }

    public function test_tracking_hides_draft_decisions_from_students(): void
    {
        $application = Application::factory()->create([
            'school_id' => $this->school->id,
            'status' => ApplicationStatus::Approved,
        ]);

        $this->getJson('/api/v1/applications/status/'.$application->reference)
            ->assertOk()
            ->assertJsonPath('data.status.value', 'reviewing')
            ->assertJsonMissingPath('data.status.approved');
    }

    public function test_admin_publishes_all_draft_selections_at_once(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Application::factory()->create(['school_id' => $this->school->id, 'status' => ApplicationStatus::Approved]);
        Application::factory()->create(['school_id' => $this->school->id, 'status' => ApplicationStatus::Approved]);
        Application::factory()->create(['school_id' => $this->school->id, 'status' => ApplicationStatus::Declined]);
        Application::factory()->create(['school_id' => $this->school->id, 'status' => ApplicationStatus::Pending]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/selections/publish')
            ->assertOk()
            ->assertJson(['message' => 'Selections published to 3 applicants.']);

        $this->assertDatabaseHas('applications', ['status' => ApplicationStatus::Selected->value]);
        $this->assertDatabaseHas('applications', ['status' => ApplicationStatus::Rejected->value]);
        $this->assertDatabaseHas('applications', ['status' => ApplicationStatus::Pending->value]);
        $this->assertDatabaseCount('applications', 4);
    }

    public function test_selections_can_only_be_published_once(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->school->update(['selections_published_at' => now()]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/selections/publish')
            ->assertStatus(409);
    }
}
