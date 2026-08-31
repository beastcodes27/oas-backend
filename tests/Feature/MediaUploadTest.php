<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_an_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $jpeg = base64_decode(
            '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AVN//2Q==',
        );
        $file = UploadedFile::fake()->createWithContent('feature.jpg', $jpeg);

        $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/admin/upload', ['image' => $file])
            ->assertOk()
            ->assertJsonStructure(['url']);

        $this->assertNotEmpty(Storage::disk('public')->files('uploads'));
    }

    public function test_upload_requires_an_image(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/upload', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function test_non_admin_cannot_upload(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/upload', [])
            ->assertStatus(403);
    }
}
