<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationExportTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::factory()->create();
    }

    private function staff(): User
    {
        return User::factory()->create(['is_admissions' => true]);
    }

    public function test_staff_can_export_applications_as_xlsx(): void
    {
        Application::factory()->count(2)->create(['school_id' => $this->school->id]);

        $response = $this->actingAs($this->staff(), 'sanctum')
            ->get('/api/v1/staff/applications/export?format=xlsx');

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition', 'attachment; filename=applications-'.date('Y-m-d').'.xlsx');
    }

    public function test_staff_can_export_applications_as_pdf(): void
    {
        Application::factory()->count(2)->create(['school_id' => $this->school->id]);

        $response = $this->actingAs($this->staff(), 'sanctum')
            ->get('/api/v1/staff/applications/export?format=pdf');

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertSee('%PDF', false);
    }

    public function test_export_honours_filters(): void
    {
        Application::factory()->create(['school_id' => $this->school->id, 'status' => ApplicationStatus::Pending]);
        Application::factory()->create(['school_id' => $this->school->id, 'status' => ApplicationStatus::Selected]);

        $response = $this->actingAs($this->staff(), 'sanctum')
            ->get('/api/v1/staff/applications/export?format=xlsx&status='.ApplicationStatus::Selected->value);

        $response->assertOk();
        $this->assertNotContains(ApplicationStatus::Pending->value, [ApplicationStatus::Selected->value]);
    }

    public function test_non_staff_cannot_export(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->get('/api/v1/staff/applications/export')
            ->assertStatus(403);
    }
}
