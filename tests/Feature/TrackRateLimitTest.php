<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_tracking_is_rate_limited_per_ip(): void
    {
        // Reference numbers are unguessable, but status lookups are public,
        // so brute-force attempts must be throttled hard.
        for ($i = 0; $i < 10; $i++) {
            $response = $this->getJson('/api/v1/applications/status/OAS-UNKN0WN-2026');
            $response->assertStatus(404);
        }

        $this->getJson('/api/v1/applications/status/OAS-UNKN0WN-2026')
            ->assertStatus(429);
    }
}
