<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_school_content(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $school = School::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/school/content', [
                'combinations' => [
                    'PCM — Physics, Chemistry, Advanced Maths',
                    'PCB — Physics, Chemistry, Biology',
                    'HGL — History, Geography, Kiswahili',
                ],
                'result_links' => [
                    ['name' => 'Form 4 Results 2023', 'url' => 'https://onlinesys.necta.go.tz/results/2023/csee/results/p0138.htm'],
                    ['name' => 'Form 2 Results 2024', 'url' => 'https://onlinesys.necta.go.tz/results/2024/ftna/results/P0104.htm'],
                ],
            ]);

        $response->assertOk()
            ->assertJsonCount(3, 'data.combinations')
            ->assertJsonCount(2, 'data.result_links')
            ->assertJsonPath('data.result_links.0.name', 'Form 4 Results 2023');

        $this->assertSame(3, count($school->fresh()->combinations));
        $this->assertSame(2, count($school->fresh()->result_links));
    }

    public function test_admin_can_clear_school_content(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $school = School::factory()->create([
            'combinations' => ['PCM', 'PCB'],
            'result_links' => [['name' => 'X', 'url' => 'https://example.com']],
        ]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/school/content', [
                'combinations' => [],
                'result_links' => [],
            ])
            ->assertOk()
            ->assertJsonCount(0, 'data.combinations')
            ->assertJsonCount(0, 'data.result_links');
    }

    public function test_non_admin_cannot_update_school_content(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/admin/school/content', [
                'combinations' => ['PCM'],
            ])
            ->assertStatus(403);
    }

    public function test_result_link_requires_a_valid_url(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/school/content', [
                'result_links' => [
                    ['name' => 'Broken', 'url' => 'not-a-url'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('result_links.0.url');
    }

    public function test_school_resource_exposes_content(): void
    {
        School::factory()->create([
            'combinations' => ['PCM', 'PCB'],
            'result_links' => [['name' => 'Form 4 Results', 'url' => 'https://example.com/r']],
        ]);

        $this->getJson('/api/v1/schools')
            ->assertOk()
            ->assertJsonPath('data.0.combinations', ['PCM', 'PCB'])
            ->assertJsonCount(1, 'data.0.result_links');
    }
}
