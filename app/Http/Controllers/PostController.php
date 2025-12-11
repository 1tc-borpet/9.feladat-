<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * GET /posts
     * Összes poszt lekérése
     */
    public function index()
    {
        $posts = Post::with(['user:id,name,email,profile_picture', 'likes.user:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'user' => [
                        'id' => $post->user->id,
                        'name' => $post->user->name,
                        'email' => $post->user->email,
                        'profile_picture' => $post->user->profile_picture,
                    ],
                    'content' => $post->content,
                    'image' => $post->image,
                    'likes_count' => $post->likes->count(),
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,
                ];
            });

        return response()->json(['posts' => $posts]);
    }

    /**
     * GET /posts/{id}
     * Egy adott poszt lekérése
     */
    public function show($id)
    {
        $post = Post::with(['user:id,name,email,profile_picture', 'likes.user:id,name'])
            ->find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        return response()->json([
            'post' => [
                'id' => $post->id,
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'email' => $post->user->email,
                    'profile_picture' => $post->user->profile_picture,
                ],
                'content' => $post->content,
                'image' => $post->image,
                'likes' => $post->likes->map(function ($like) {
                    return [
                        'user_id' => $like->user_id,
                        'user_name' => $like->user->name,
                        'created_at' => $like->created_at,
                    ];
                }),
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ]
        ]);
    }

    /**
     * POST /posts
     * Új poszt létrehozása
     */
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'image' => 'nullable|string|max:255',
        ]);

        $post = Post::create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
            'image' => $request->image,
        ]);

        $post->load('user:id,name,email,profile_picture');

        return response()->json([
            'message' => 'Post created successfully',
            'post' => [
                'id' => $post->id,
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'email' => $post->user->email,
                    'profile_picture' => $post->user->profile_picture,
                ],
                'content' => $post->content,
                'image' => $post->image,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ]
        ], 201);
    }

    /**
     * PUT /posts/{id}
     * Poszt módosítása (csak saját poszt)
     */
    public function update(Request $request, $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'content' => 'sometimes|required|string',
            'image' => 'nullable|string|max:255',
        ]);

        if ($request->has('content')) {
            $post->content = $request->content;
        }
        if ($request->has('image')) {
            $post->image = $request->image;
        }

        $post->save();
        $post->load('user:id,name,email,profile_picture');

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => [
                'id' => $post->id,
                'user' => [
                    'id' => $post->user->id,
                    'name' => $post->user->name,
                    'email' => $post->user->email,
                    'profile_picture' => $post->user->profile_picture,
                ],
                'content' => $post->content,
                'image' => $post->image,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ]
        ]);
    }

    /**
     * DELETE /posts/{id}?force=true
     * Poszt törlése (csak saját poszt)
     * force=true esetén végleges törlés
     */
    public function destroy(Request $request, $id)
    {
        $post = Post::withTrashed()->find($id);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Ha force=true paraméter van, akkor végleges törlés
        if ($request->query('force') === 'true') {
            $post->forceDelete();
            return response()->json(['message' => 'Post permanently deleted']);
        }

        // Különben soft delete
        $post->delete();

        return response()->json(['message' => 'Post deleted successfully (soft delete)']);
    }

    /**
     * GET /users/{id}/posts
     * Egy felhasználó posztjainak lekérése
     */
    public function userPosts($userId)
    {
        $posts = Post::with(['user:id,name,email,profile_picture', 'likes'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'content' => $post->content,
                    'image' => $post->image,
                    'likes_count' => $post->likes->count(),
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,
                ];
            });

        return response()->json(['posts' => $posts]);
    }
}
