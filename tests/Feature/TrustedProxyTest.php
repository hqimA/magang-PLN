<?php

namespace Tests\Feature;

use Tests\TestCase;

class TrustedProxyTest extends TestCase
{
    public function test_https_forwarded_requests_generate_secure_application_urls(): void
    {
        $response = $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
        ])->get('/register');

        $response->assertOk();
        $response->assertSee('action="https://localhost:8000/register"', false);
    }
}
