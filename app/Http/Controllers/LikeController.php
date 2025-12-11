<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * POST /posts/{id}/like
     * Poszt likeolása
     */
    public function like(Request $request, $postId)
    {
        $post = Post::find($postId);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $existingLike = Like::where('user_id', $request->user()->id)
            ->where('post_id', $postId)
            ->first();

        if ($existingLike) {
            return response()->json(['message' => 'Already liked this post'], 409);
        }

        $like = Like::create([
            'user_id' => $request->user()->id,
            'post_id' => $postId,
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Post liked successfully',
            'like' => [
                'id' => $like->id,
                'user_id' => $like->user_id,
                'post_id' => $like->post_id,
                'created_at' => $like->created_at,
            ]
        ], 201);
    }

    /**
     * DELETE /posts/{id}/unlike
     * Poszt unlike-olása
     */
    public function unlike(Request $request, $postId)
    {
        $post = Post::find($postId);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $like = Like::where('user_id', $request->user()->id)
            ->where('post_id', $postId)
            ->first();

        if (!$like) {
            return response()->json(['message' => 'Like not found'], 404);
        }

        $like->delete();

        return response()->json(['message' => 'Post unliked successfully']);
    }

    /**
     * GET /posts/{id}/likes
     * Poszt like-jainak lekérése
     */
    public function postLikes($postId)
    {
        $post = Post::find($postId);

        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $likes = Like::with('user:id,name,email,profile_picture')
            ->where('post_id', $postId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($like) {
                return [
                    'id' => $like->id,
                    'user' => [
                        'id' => $like->user->id,
                        'name' => $like->user->name,
                        'email' => $like->user->email,
                        'profile_picture' => $like->user->profile_picture,
                    ],
                    'created_at' => $like->created_at,
                ];
            });

        return response()->json(['likes' => $likes]);
    }
}
