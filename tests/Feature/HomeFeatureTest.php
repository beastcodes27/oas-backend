<?php

namespace Tests\Feature;

use App\Models\HomeFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_list_home_features(): void
    {
        HomeFeature::factory()->count(3)->create();

        $this->getJson('/api/v1/home-features')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'title', 'text', 'image', 'sort_order'],
                ],
            ]);
    }

    public function test_admin_can_create_a_home_feature(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/home-features', [
                'title' => 'My Feature',
                'text' => 'Some description.',
                'image' => 'https://loremflickr.com/640/300/school',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'My Feature');

        $this->assertDatabaseCount('home_features', 1);
    }

    public function test_admin_can_update_a_home_feature(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $feature = HomeFeature::factory()->create(['title' => 'Old']);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/admin/home-features/'.$feature->id, [
                'title' => 'New Title',
                'text' => 'Updated text.',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'New Title');
    }

    public function test_admin_can_delete_a_home_feature(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $feature = HomeFeature::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/home-features/'.$feature->id)
            ->assertOk();

        $this->assertDatabaseCount('home_features', 0);
    }

    public function test_feature_requires_a_title(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/home-features', ['title' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_non_admin_cannot_manage_home_features(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/home-features', ['title' => 'x'])
            ->assertStatus(403);
    }
}
