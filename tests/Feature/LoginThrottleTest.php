<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sixth_login_attempt_returns_too_many_requests(): void
    {
        $payload = [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ];

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/login', $payload)->assertStatus(422);
        }

        $this->postJson('/login', $payload)->assertStatus(429);
    }
}
