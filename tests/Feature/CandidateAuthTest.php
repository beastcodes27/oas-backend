<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CandidateAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_candidate_can_register_with_index_number_and_password(): void
    {
        $response = $this->postJson('/api/v1/auth/candidate/register', [
            'index_number' => 's0104/0002/2024',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'token', 'user' => ['id', 'username', 'name', 'email']])
            ->assertJsonPath('user.username', 'S0104/0002/2024')
            ->assertJsonPath('user.name', null)
            ->assertJsonPath('user.email', null);

        $this->assertDatabaseHas('users', ['username' => 'S0104/0002/2024']);
    }

    public function test_candidate_registration_rejects_a_duplicate_index_number(): void
    {
        User::factory()->create(['username' => 'S0104/0002/2024']);

        $response = $this->postJson('/api/v1/auth/candidate/register', [
            'index_number' => 's0104/0002/2024',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    public function test_candidate_registration_rejects_weak_passwords(): void
    {
        $response = $this->postJson('/api/v1/auth/candidate/register', [
            'index_number' => 'S0104/0002/2024',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseMissing('users', ['username' => 'S0104/0002/2024']);
    }

    public function test_candidate_can_log_in_with_index_number(): void
    {
        User::factory()->create([
            'username' => 'S0104/0002/2024',
            'name' => null,
            'email' => null,
            'password' => 'SecurePass1',
        ]);

        $response = $this->postJson('/api/v1/auth/candidate/login', [
            'index_number' => 's0104/0002/2024',
            'password' => 'SecurePass1',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'token', 'user' => ['username']])
            ->assertJsonPath('user.username', 'S0104/0002/2024');
    }

    public function test_candidate_login_with_wrong_password_is_rejected(): void
    {
        User::factory()->create([
            'username' => 'S0104/0002/2024',
            'password' => 'SecurePass1',
        ]);

        $response = $this->postJson('/api/v1/auth/candidate/login', [
            'index_number' => 'S0104/0002/2024',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('index_number');
    }

    public function test_candidate_login_with_unknown_index_number_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/auth/candidate/login', [
            'index_number' => 'P0000/0000/2024',
            'password' => 'SecurePass1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('index_number');
    }

    public function test_staff_email_login_still_works_and_profile_includes_username(): void
    {
        $user = User::factory()->create([
            'username' => 'STAFF-ADM-01',
            'email' => 'officer@example.com',
            'password' => 'SecurePass1',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'officer@example.com',
            'password' => 'SecurePass1',
        ])->assertOk()->assertJsonStructure(['token', 'user' => ['username']]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.username', 'STAFF-ADM-01');
    }

    public function test_candidate_can_add_name_and_phone_to_profile(): void
    {
        $user = User::factory()->create(['username' => 'S0104/0002/2024', 'name' => null, 'phone' => null]);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/auth/profile', [
                'name' => 'Amina Khalid',
                'phone' => '0755 100 100',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Amina Khalid')
            ->assertJsonPath('data.phone', '0755 100 100');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Amina Khalid']);
    }

    public function test_candidate_can_change_password_later(): void
    {
        $user = User::factory()->create(['username' => 'S0104/0002/2024', 'password' => 'CurrentPass1']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/auth/profile', [
                'current_password' => 'CurrentPass1',
                'password' => 'NewPass123',
                'password_confirmation' => 'NewPass123',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('NewPass123', $user->fresh()->password));
    }
}
