<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\LikeController;

// Public routes
Route::get('/ping', function () {
    return response()->json(['message' => 'API works!'], 200);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (Bearer authentication required)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/users/me', [AuthController::class, 'me']);

    // Posts
    Route::get('/posts', [PostController::class, 'index']);
    Route::get('/posts/{id}', [PostController::class, 'show']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::put('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);
    Route::get('/users/{id}/posts', [PostController::class, 'userPosts']);

    // Likes
    Route::post('/posts/{id}/like', [LikeController::class, 'like']);
    Route::delete('/posts/{id}/unlike', [LikeController::class, 'unlike']);
    Route::get('/posts/{id}/likes', [LikeController::class, 'postLikes']);
});
