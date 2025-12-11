<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;

class LikeSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $posts = Post::all();

        // User 2 likeol néhány posztot
        Like::create([
            'user_id' => $users[1]->id,
            'post_id' => $posts[0]->id,
            'created_at' => now(),
        ]);

        Like::create([
            'user_id' => $users[1]->id,
            'post_id' => $posts[3]->id,
            'created_at' => now(),
        ]);

        // User 3 likeol néhány posztot
        Like::create([
            'user_id' => $users[2]->id,
            'post_id' => $posts[0]->id,
            'created_at' => now(),
        ]);

        Like::create([
            'user_id' => $users[2]->id,
            'post_id' => $posts[2]->id,
            'created_at' => now(),
        ]);

        // User 4 likeol egy posztot
        Like::create([
            'user_id' => $users[3]->id,
            'post_id' => $posts[0]->id,
            'created_at' => now(),
        ]);
    }
}
