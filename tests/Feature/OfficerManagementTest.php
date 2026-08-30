<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OfficerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_admission_officers(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->count(2)->create(['is_admissions' => true]);
        User::factory()->create(); // a regular user, should not appear

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/admin/officers')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_create_an_admission_officer(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/officers', [
                'name' => 'New Officer',
                'email' => 'officer2@shuleyetu.ac.tz',
                'phone' => '0755 111 222',
                'password' => 'Officer@12345',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_admissions', true)
            ->assertJsonPath('data.is_admin', false);

        $this->assertDatabaseHas('users', ['email' => 'officer2@shuleyetu.ac.tz', 'is_admissions' => true]);
    }

    public function test_officer_email_must_be_unique(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/admin/officers', [
                'name' => 'New Officer',
                'email' => 'taken@example.com',
                'password' => 'Officer@12345',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_admin_can_reset_an_officers_password(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $officer = User::factory()->create(['is_admissions' => true]);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/admin/officers/'.$officer->id.'/password', [
                'password' => 'NewPass123',
                'password_confirmation' => 'NewPass123',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('NewPass123', $officer->fresh()->password));
    }

    public function test_admin_can_remove_an_admission_officer(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $officer = User::factory()->create(['is_admissions' => true]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/v1/admin/officers/'.$officer->id)
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $officer->id]);
    }

    public function test_non_admin_cannot_manage_officers(): void
    {
        $officer = User::factory()->create(['is_admissions' => true]);

        $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/admin/officers')
            ->assertStatus(403);
    }
}
