<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookieAuthTest extends TestCase
{
    use RefreshDatabase;

    private function frontendHeaders(): array
    {
        return ['Origin' => 'http://localhost:3000', 'Accept' => 'application/json'];
    }

    public function test_frontend_login_returns_token_only_in_http_only_cookie(): void
    {
        $user = User::factory()->create(['email' => 'amina@example.com', 'password' => 'SecurePass1']);

        $response = $this->withHeaders($this->frontendHeaders())
            ->postJson('/api/v1/auth/login', [
                'email' => 'amina@example.com',
                'password' => 'SecurePass1',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'user'])
            ->assertJsonMissingPath('token');

        $cookies = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === 'oas_token');

        $this->assertNotNull($cookies, 'oas_token cookie was not set');
        $this->assertTrue($cookies->isHttpOnly(), 'oas_token cookie must be httpOnly');
        $this->assertNotEmpty($cookies->getValue());
    }

    public function test_external_login_still_receives_a_plaintext_token(): void
    {
        User::factory()->create(['email' => 'amina@example.com', 'password' => 'SecurePass1']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'amina@example.com',
            'password' => 'SecurePass1',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user']);
    }

    public function test_cookie_authenticated_request_can_fetch_profile(): void
    {
        $user = User::factory()->create(['email' => 'amina@example.com', 'password' => 'SecurePass1']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withCredentials()->withUnencryptedCookies(['oas_token' => $token])
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', 'amina@example.com');
    }

    public function test_cookie_authenticated_write_without_csrf_header_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'amina@example.com',
            'password' => 'SecurePass1',
        ]);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withCredentials()->withUnencryptedCookies(['oas_token' => $token])
            ->patchJson('/api/v1/auth/profile', ['name' => 'Amina New'])
            ->assertStatus(419);
    }

    public function test_cookie_authenticated_write_with_csrf_header_is_accepted(): void
    {
        $user = User::factory()->create([
            'email' => 'amina@example.com',
            'password' => 'SecurePass1',
        ]);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withCredentials()->withUnencryptedCookies(['oas_token' => $token])
            ->withHeader('X-OAS-Request', '1')
            ->patchJson('/api/v1/auth/profile', ['name' => 'Amina New'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Amina New');
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = User::factory()->create(['email' => 'amina@example.com', 'password' => 'SecurePass1']);
        $tokenModel = $user->createToken('auth-token');
        $tokenModel->accessToken->forceFill(['created_at' => now()->subDays(30)])->save();

        $this->withHeader('Cookie', 'oas_token='.$tokenModel->plainTextToken)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_logout_with_cookie_revokes_token_and_expires_cookie(): void
    {
        $user = User::factory()->create(['email' => 'amina@example.com', 'password' => 'SecurePass1']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withCredentials()->withUnencryptedCookies(['oas_token' => $token])
            ->withHeader('X-OAS-Request', '1')
            ->postJson('/api/v1/auth/logout');

        $response->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === 'oas_token');
        $this->assertNotNull($cookie);
        $this->assertEquals('', $cookie->getValue());
    }
}
