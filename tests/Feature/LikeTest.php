<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Post;
use App\Models\Like;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_like_creates_like()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::create(['user_id' => $other->id, 'content' => 'Likeable', 'image' => null]);

        $this->actingAs($user, 'sanctum')->postJson('/api/posts/' . $post->id . '/like')->assertStatus(201)->assertJson(['message' => 'Post liked successfully']);

        $this->assertDatabaseHas('likes', ['user_id' => $user->id, 'post_id' => $post->id]);
    }

    public function test_like_duplicate_returns_409()
    {
        $user = User::factory()->create();
        $postOwner = User::factory()->create();
        $post = Post::create(['user_id' => $postOwner->id, 'content' => 'Likeable', 'image' => null]);

        $this->actingAs($user, 'sanctum')->postJson('/api/posts/' . $post->id . '/like')->assertStatus(201);
        $this->actingAs($user, 'sanctum')->postJson('/api/posts/' . $post->id . '/like')->assertStatus(409);
    }

    public function test_unlike_removes_like()
    {
        $user = User::factory()->create();
        $postOwner = User::factory()->create();
        $post = Post::create(['user_id' => $postOwner->id, 'content' => 'Likeable', 'image' => null]);

        $this->actingAs($user, 'sanctum')->postJson('/api/posts/' . $post->id . '/like')->assertStatus(201);
        $this->actingAs($user, 'sanctum')->deleteJson('/api/posts/' . $post->id . '/unlike')->assertStatus(200)->assertJson(['message' => 'Post unliked successfully']);

        $this->assertDatabaseMissing('likes', ['user_id' => $user->id, 'post_id' => $post->id]);
    }

    public function test_post_likes_returns_likes_list()
    {
        $user = User::factory()->create();
        $u2 = User::factory()->create();
        $post = Post::create(['user_id' => $user->id, 'content' => 'Likeable', 'image' => null]);

        Like::create(['user_id' => $u2->id, 'post_id' => $post->id, 'created_at' => now()]);

        $this->actingAs($user, 'sanctum')->getJson('/api/posts/' . $post->id . '/likes')->assertStatus(200)->assertJsonStructure(['likes']);
    }
}
