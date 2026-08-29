<?php

namespace Tests\Feature;

use App\Models\GalleryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_list_gallery_items(): void
    {
        GalleryItem::factory()->count(2)->create();

        $this->getJson('/api/v1/gallery')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'image_url', 'caption', 'description'],
                ],
            ]);
    }

    public function test_admin_can_upload_a_gallery_item(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $jpeg = base64_decode(
            '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AVN//2Q==',
        );
        $file = UploadedFile::fake()->createWithContent('school.jpg', $jpeg);

        $response = $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/admin/gallery', [
                'image' => $file,
                'caption' => 'School Assembly',
                'description' => 'Morning assembly at the school grounds.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.caption', 'School Assembly');

        $this->assertDatabaseCount('gallery_items', 1);
        $this->assertNotEmpty(Storage::disk('public')->files('gallery'));
    }

    public function test_gallery_upload_requires_an_image(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/gallery', ['caption' => 'No image'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function test_non_admin_cannot_upload_gallery_items(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/admin/gallery', ['image' => 'x'])
            ->assertStatus(403);
    }

    public function test_admin_can_delete_a_gallery_item(): void
    {
        Storage::fake('public');
        $path = 'gallery/to-delete.jpg';
        Storage::disk('public')->put($path, 'bytes');

        $item = GalleryItem::factory()->create(['image' => $path]);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/gallery/'.$item->id)
            ->assertOk();

        $this->assertDatabaseCount('gallery_items', 0);
        Storage::disk('public')->assertMissing($path);
    }
}
