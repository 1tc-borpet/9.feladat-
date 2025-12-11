<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\User;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        Post::create([
            'user_id' => $users[0]->id,
            'content' => 'Ez az első posztom! Milyen szép nap van ma!',
            'image' => 'https://via.placeholder.com/600x400',
        ]);

        Post::create([
            'user_id' => $users[0]->id,
            'content' => 'Tegnap este láttam a legszebb naplementét!',
            'image' => 'https://via.placeholder.com/600x400',
        ]);

        Post::create([
            'user_id' => $users[1]->id,
            'content' => 'Új programozási projekt indult! Laravel REST API fejlesztés.',
            'image' => null,
        ]);

        Post::create([
            'user_id' => $users[2]->id,
            'content' => 'Hétvégén kirándulni voltunk a hegyekben.',
            'image' => 'https://via.placeholder.com/600x400',
        ]);

        Post::create([
            'user_id' => $users[3]->id,
            'content' => 'Ma elkészítettem az első tortámat!',
            'image' => 'https://via.placeholder.com/600x400',
        ]);
    }
}
