<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class RedirectRootTest extends TestCase
{
    public function test_root_redirects_to_frontend_domain(): void
    {
        Config::set('app.frontend_url', 'https://oas.devtz.com');

        $this->get('/')
            ->assertRedirect('https://oas.devtz.com');
    }

    public function test_api_routes_are_not_redirected(): void
    {
        $this->getJson('/api/v1/ping')
            ->assertStatus(404);
    }
}
