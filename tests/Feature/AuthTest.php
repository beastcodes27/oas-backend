<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receives_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Amina Khalid',
            'email' => 'amina@example.com',
            'phone' => '0755 100 100',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'token', 'user' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'amina@example.com']);
    }

    public function test_register_rejects_weak_passwords(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Amina Khalid',
            'email' => 'amina@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'amina@example.com']);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'amina@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Another User',
            'email' => 'amina@example.com',
            'password' => 'SecurePass1',
            'password_confirmation' => 'SecurePass1',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_user_can_login_and_receive_token(): void
    {
        $user = User::factory()->create(['password' => 'SecurePass1']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'SecurePass1',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_login_with_wrong_credentials_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'SecurePass1']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_unauthenticated_user_is_rejected(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_user_can_change_email(): void
    {
        $user = User::factory()->create(['email' => 'old@example.com']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/auth/profile', ['email' => 'new@example.com'])
            ->assertOk()
            ->assertJsonPath('data.email', 'new@example.com');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'new@example.com']);
    }

    public function test_email_change_must_be_unique(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/auth/profile', ['email' => 'taken@example.com'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPass1']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/auth/profile', [
                'current_password' => 'CurrentPass1',
                'password' => 'NewPass123',
                'password_confirmation' => 'NewPass123',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('NewPass123', $user->fresh()->password));
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => 'CurrentPass1']);

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/auth/profile', [
                'current_password' => 'WrongPass',
                'password' => 'NewPass123',
                'password_confirmation' => 'NewPass123',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('current_password');

        $this->assertTrue(Hash::check('CurrentPass1', $user->fresh()->password));
    }

    public function test_user_can_save_their_intake_early(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->patchJson('/api/v1/auth/intake', [
                'entry_level' => 'Form 1',
                'exam_type' => 'psle',
                'exam_reg_number' => 'P0101/0001/2024',
                'exam_year' => 2024,
                'exam_confirmed' => true,
                'exam_result' => ['division' => 'A', 'points' => 7],
            ])
            ->assertOk()
            ->assertJsonPath('data.intake.entry_level', 'Form 1')
            ->assertJsonPath('data.intake.exam_reg_number', 'P0101/0001/2024')
            ->assertJsonPath('data.intake.exam_confirmed', true);

        $fresh = $user->fresh();
        $this->assertSame('Form 1', $fresh->entry_level);
        $this->assertTrue($fresh->exam_confirmed);
        $this->assertNotNull($fresh->exam_confirmed_at);
    }
}
