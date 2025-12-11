<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Post;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_posts()
    {
        $user = User::factory()->create();
        Post::create(['user_id' => $user->id, 'content' => 'Teszt poszt', 'image' => null]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/posts');

        $response->assertStatus(200)->assertJsonStructure(['posts']);
    }

    public function test_show_returns_post()
    {
        $user = User::factory()->create();
        $post = Post::create(['user_id' => $user->id, 'content' => 'Mutasd a posztot', 'image' => null]);

        $this->actingAs($user, 'sanctum')->getJson('/api/posts/' . $post->id)->assertStatus(200)->assertJsonStructure(['post' => ['id','user','content']]);
    }

    public function test_store_creates_post_authenticated()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/posts', [
            'content' => 'Új poszt tartalom',
        ]);

        $response->assertStatus(201)->assertJson(['message' => 'Post created successfully']);
        $this->assertDatabaseHas('posts', ['content' => 'Új poszt tartalom']);
    }

    public function test_update_updates_own_post()
    {
        $user = User::factory()->create();
        $post = Post::create(['user_id' => $user->id, 'content' => 'Eredeti', 'image' => null]);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/posts/' . $post->id, ['content' => 'Frissített']);

        $response->assertStatus(200)->assertJson(['message' => 'Post updated successfully']);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'content' => 'Frissített']);
    }

    public function test_update_forbidden_when_not_owner()
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::create(['user_id' => $owner->id, 'content' => 'Eredeti', 'image' => null]);

        $this->actingAs($other, 'sanctum')->putJson('/api/posts/' . $post->id, ['content' => 'Hack'])->assertStatus(403);
    }

    public function test_soft_delete_marks_deleted()
    {
        $user = User::factory()->create();
        $post = Post::create(['user_id' => $user->id, 'content' => 'Torolheto', 'image' => null]);

        $this->actingAs($user, 'sanctum')->deleteJson('/api/posts/' . $post->id)->assertStatus(200)->assertJson(['message' => 'Post deleted successfully (soft delete)']);

        $this->assertDatabaseMissing('posts', ['id' => $post->id, 'deleted_at' => null]);
        $this->assertNotNull(Post::withTrashed()->find($post->id));
    }

    public function test_force_delete_removes_record()
    {
        $user = User::factory()->create();
        $post = Post::create(['user_id' => $user->id, 'content' => 'Vegtelen torles', 'image' => null]);

        $this->actingAs($user, 'sanctum')->deleteJson('/api/posts/' . $post->id . '?force=true')->assertStatus(200)->assertJson(['message' => 'Post permanently deleted']);

        $this->assertNull(Post::withTrashed()->find($post->id));
    }

    public function test_user_posts_returns_only_user_posts()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Post::create(['user_id' => $user->id, 'content' => 'U1', 'image' => null]);
        Post::create(['user_id' => $other->id, 'content' => 'U2', 'image' => null]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/users/' . $user->id . '/posts');

        $response->assertStatus(200)->assertJsonCount(1, 'posts');
    }
}
