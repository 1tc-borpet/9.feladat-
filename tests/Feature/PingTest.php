<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PingTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_returns_ok()
    {
        $this->getJson('/api/ping')->assertStatus(200)->assertJson(['message' => 'API works!']);
    }
}
