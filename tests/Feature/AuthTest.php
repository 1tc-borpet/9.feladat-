<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_returns_201()
    {
        $payload = [
            'name' => 'Teszt',
            'email' => 'teszt@example.com',
            'password' => 'Jelszo_2025',
            'password_confirmation' => 'Jelszo_2025',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'user' => ['id','name','email']]);

        $this->assertDatabaseHas('users', ['email' => 'teszt@example.com']);
    }

    public function test_login_returns_token()
    {
        $user = User::create([
            'name' => 'LoginUser',
            'email' => 'login@example.com',
            'password' => Hash::make('Jelszo_2025'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'Jelszo_2025',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message','user','access' => ['token','token_type']]);
    }

    public function test_logout_revokes_token()
    {
        $user = User::create([
            'name' => 'LogoutUser',
            'email' => 'logout@example.com',
            'password' => Hash::make('Jelszo_2025'),
        ]);

        $user->createToken('test-token')->plainTextToken;

        // Assert user has tokens before logout
        $this->assertGreaterThan(0, $user->tokens()->count());

        $this->actingAs($user, 'sanctum')->postJson('/api/logout')->assertStatus(200)->assertJson(['message' => 'Logout successful']);

        // Assert all tokens are deleted after logout
        $this->assertEquals(0, $user->tokens()->count());
    }
}
