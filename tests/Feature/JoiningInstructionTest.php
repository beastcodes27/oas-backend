<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class JoiningInstructionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_joining_instruction_via_file(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $pdf = UploadedFile::fake()->create('instructions.pdf', 20, 'application/pdf');

        $response = $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/staff/joining-instruction', [
                'file' => $pdf,
                'name' => 'Joining Instructions 2026',
                'note' => 'Report on the first day with your admission letter.',
            ])
            ->assertOk()
            ->assertJsonPath('data.joining_instruction.name', 'Joining Instructions 2026');

        $instruction = $response->json('data.joining_instruction');
        $this->assertNotNull($instruction['url']);
        $this->assertNotNull($instruction['published_at']);

        $school = School::default();
        $this->assertNotNull($school->joining_instruction_published_at);
        $this->assertNotEmpty(Storage::disk('public')->files('instructions'));
    }

    public function test_officer_can_publish_joining_instruction_via_url(): void
    {
        $officer = User::factory()->create(['is_admissions' => true]);

        $this->actingAs($officer, 'sanctum')
            ->postJson('/api/v1/staff/joining-instruction', [
                'url' => 'https://example.com/instructions.pdf',
                'name' => 'Joining Instructions',
            ])
            ->assertOk()
            ->assertJsonPath('data.joining_instruction.url', 'https://example.com/instructions.pdf');
    }

    public function test_publish_requires_file_or_url(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/staff/joining-instruction', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['file', 'url']);
    }

    public function test_regular_applicant_cannot_publish_joining_instruction(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/staff/joining-instruction', ['url' => 'https://example.com/x.pdf'])
            ->assertStatus(403);
    }

    public function test_school_resource_exposes_joining_instruction(): void
    {
        School::factory()->create([
            'joining_instruction_url' => 'https://example.com/instructions.pdf',
            'joining_instruction_published_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/schools')
            ->assertOk()
            ->assertJsonPath('data.0.joining_instruction.url', 'https://example.com/instructions.pdf');

        $this->assertNotNull($response->json('data.0.joining_instruction.published_at'));
    }
}
