# Követési rendszer REST API Dokumentáció

Ez a dokumentáció a **Követési rendszer** REST API-jához készült, amely egy közösségi média típusú alkalmazás backend rendszerét valósítja meg Laravel keretrendszerrel.

## Projekt áttekintés

**Technológiai stack:**
- Laravel 11.x
- Laravel Sanctum (Bearer token authentication)
- MySQL adatbázis

**Adatbázis struktúra:**
```
users (id, name, email, password, profile_picture, created_at, updated_at)
  ↓ 1:N
posts (id, user_id, content, image, created_at, updated_at)
  ↓ 1:N
likes (id, user_id, post_id, created_at)
```

**Fő funkciók:**
-  Felhasználó regisztráció és authentikáció (Bearer token)
-  Posztok CRUD műveletek (csak saját poszt szerkeszthető/törölhető)
-  Like/unlike funkciók
-  Felhasználók és posztok listázása
-  Teljes API dokumentáció és tesztelési útmutató

**API Végpontok száma:** 14 végpont (3 nyilvános + 11 védett)

---
## I. Előkészítés

### Szükséges eszközök

- MySQL adatbázis (sqlite helyett MySQL-t használ a projekt)
- Postman (javasolt API teszteléshez)

### Projekt inicializálás

1. **Laravel projekt telepítése:**
    ```bash
    composer create-project laravel/laravel --prefer-dist 20feladat
    cd 20feladat
    ```

2. **Laravel Sanctum telepítése:**
    ```bash
    composer require laravel/sanctum
    php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
    php artisan install:api
    ```

3. **`.env` konfigurálása:**
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=20feladat
    DB_USERNAME=root
    DB_PASSWORD=
    ```

4. **MySQL adatbázis létrehozása:**
    - Nyisd meg: `http://localhost/phpmyadmin`
    - Kattints az "Új" gombra
    - Adatbázis neve: `20feladat`
    - Karakter kódolás: `utf8mb4_unicode_ci`
    - Kattints "Létrehozás"

5. **Migráció és seeder futtatása:**
    ```bash
    php artisan migrate:fresh --seed
    ```

### Model-ek és kapcsolatok

**User Model (app/Models/User.php):**
```php
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'profile_picture',
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}
```

**Post Model (app/Models/Post.php):**
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;
    
    protected $fillable = ['user_id', 'content', 'image'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}
```

**Like Model (app/Models/Like.php):**
```php
class Like extends Model
{
    public $timestamps = false;
    protected $fillable = ['user_id', 'post_id', 'created_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
```
    
---
## II. Controllerek és Végpontok

### Controller-ek létrehozása

```bash
php artisan make:controller AuthController
php artisan make:controller PostController
php artisan make:controller LikeController
```

### AuthController (app/Http/Controllers/AuthController.php)

**Regisztráció:**
```php
public function register(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|confirmed|min:8',
        'profile_picture' => 'nullable|string|max:255',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'profile_picture' => $request->profile_picture,
    ]);

    return response()->json([
        'message' => 'User created successfully',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_picture' => $user->profile_picture,
        ],
    ], 201);
}
```

**Bejelentkezés:**
```php
public function login(Request $request)
{
    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid email or password'], 401);
    }

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'user' => [...],
        'access' => [
            'token' => $token,
            'token_type' => 'Bearer'
        ]
    ]);
}
```

**Kijelentkezés:**
```php
public function logout(Request $request)
{
    $request->user()->tokens()->delete();
    return response()->json(['message' => 'Logout successful']);
}
```

### PostController (app/Http/Controllers/PostController.php)

**Összes poszt listázása:**
```php
public function index()
{
    $posts = Post::with(['user:id,name,email,profile_picture', 'likes'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($post) {
            return [
                'id' => $post->id,
                'user' => [...],
                'content' => $post->content,
                'image' => $post->image,
                'likes_count' => $post->likes->count(),
                'created_at' => $post->created_at,
            ];
        });

    return response()->json(['posts' => $posts]);
}
```

**Új poszt létrehozása:**
```php
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

    return response()->json([
        'message' => 'Post created successfully',
        'post' => $post
    ], 201);
}
```

**Poszt módosítása (csak saját):**
```php
public function update(Request $request, $id)
{
    $post = Post::find($id);

    if (!$post) {
        return response()->json(['message' => 'Post not found'], 404);
    }

    if ($post->user_id !== $request->user()->id) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    $post->update($request->only(['content', 'image']));
    
    return response()->json([
        'message' => 'Post updated successfully',
        'post' => $post
    ]);
}
```

**Poszt törlése (csak saját) - Soft Delete:**
```php
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
```

### LikeController (app/Http/Controllers/LikeController.php)

**Poszt likeolása:**
```php
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
        'like' => $like
    ], 201);
}
```

**Like visszavonása:**
```php
public function unlike(Request $request, $postId)
{
    $like = Like::where('user_id', $request->user()->id)
        ->where('post_id', $postId)
        ->first();

    if (!$like) {
        return response()->json(['message' => 'Like not found'], 404);
    }

    $like->delete();

    return response()->json(['message' => 'Post unliked successfully']);
}
```

### API Routes (routes/api.php)

```php
<?php

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
```

Minden végponthoz Bearer tokenes autentikáció szükséges (kivéve regisztráció/bejelentkezés).

### API Végpontok összefoglalása

| Művelet                   | URL                  | Módszer | Auth? | Leírás                                       |
|---------------------------|----------------------|---------|-------|-----------------------------------------------|
| API teszt                 | /ping                | GET     | Nem   | API működésének ellenőrzése                   |
| Regisztráció              | /register            | POST    | Nem   | Új felhasználó létrehozása                    |
| Bejelentkezés             | /login               | POST    | Nem   | Token visszaadása e-mail + jelszó alapján     |
| Kijelentkezés             | /logout              | POST    | Igen  | Tokenek törlése                               |
| Saját profil lekérdezés   | /users/me            | GET     | Igen  | Bejelentkezett felhasználó adatai             |
| Poszt létrehozás          | /posts               | POST    | Igen  | Új poszt írása                                |
| Poszt lista               | /posts               | GET     | Igen  | Minden poszt összesítve                       |
| Poszt lekérdezése         | /posts/{id}          | GET     | Igen  | Egy poszt részletei                           |
| Poszt szerkesztése        | /posts/{id}          | PUT     | Igen  | Poszt szerkesztése (csak saját)               |
| Poszt törlése             | /posts/{id}          | DELETE  | Igen  | Saját poszt törlése                           |
| User posztjai             | /users/{id}/posts    | GET     | Igen  | Egy felhasználó posztjainak listája           |
| Lájk hozzáadása           | /posts/{id}/like     | POST    | Igen  | Poszt lájkolása                               |
| Lájk visszavonása         | /posts/{id}/unlike   | DELETE  | Igen  | Poszt lájk törlése                            |
| Poszt lájklistája         | /posts/{id}/likes    | GET     | Igen  | Kik lájkolták a posztot                       |

### Példák a végpontok használatára

| Művelet                   | URL                  | Módszer | Auth? | Leírás                                       |
|---------------------------|----------------------|---------|-------|-----------------------------------------------|
| Regisztráció              | /auth/register       | POST    | Nem   | Új felhasználó létrehozása                    |
| Bejelentkezés             | /auth/login          | POST    | Nem   | Token visszaadása e-mail + jelszó alapján     |
| Saját profil lekérdezés   | /me                  | GET     | Igen  | Bejelentkezett felhasználó adatai             |
| User lista                | /users               | GET     | Igen  | Összes felhasználó listája                    |
| Egy user lekérdezése      | /users/:id           | GET     | Igen  | Felhasználó megjelenítése id alapján          |
| User szerkesztése         | /users/:id           | PUT     | Igen  | Felhasználó adatainak módosítása              |
| Saját posztok lekérdez.   | /posts/my            | GET     | Igen  | Saját poszt lista                             |
| Poszt létrehozás          | /posts               | POST    | Igen  | Új poszt írása                                |
| Poszt lista               | /posts               | GET     | Igen  | Minden poszt összesítve                       |
| Poszt lekérdezése         | /posts/:id           | GET     | Igen  | Egy poszt részletei                           |
| Poszt szerkesztése        | /posts/:id           | PUT     | Igen  | Poszt szerkesztése (saját)                    |
| Poszt törlése             | /posts/:id           | DELETE  | Igen  | Saját poszt törlése                           |
| Lájk hozzáadása           | /posts/:id/like      | POST    | Igen  | Poszt lájkolása                               |
| Lájk visszavonása         | /posts/:id/unlike    | POST    | Igen  | Poszt lájk törlése                            |
| Egy poszt lájklistája     | /posts/:id/likes     | GET     | Igen  | Kiket érkeztek lájkok a posztra               |

### Példa - Regisztráció (POST /auth/register)

Request body:
```json
{
  "name": "boros1",
  "email": "boros1@gmail.com",
  "password": "jelszo123"
}
```
Response (201):
```json
{
  "message": "Sikeres regisztráció",
  "user": {
    "id": 1,
    "name": "boros1",
    "email": "boros1@gmail.com",
    "created_at": "2025-11-17T23:14:52Z"
  }
}
```

### Példa - Bejelentkezés (POST /auth/login)

```json
{
  "email": "boros1@gmail.com",
  "password": "jelszo123"
}
```
Response:
```json
{
  "token": "eyJhbGciOi..."
}
```

### Példa - Likelés (POST /posts/5/like)

Header:
```
Authorization: Bearer <eyJhbGciOi...>
```
Response (200):
```json
{
  "message": "Poszt sikeresen lájkolva."
}
```

### Példa - Lájk törlése (POST /posts/5/unlike)

Ez a végpont eltávolítja a bejelentkezett felhasználó lájkját az adott posztról.

**URL:** `POST /posts/:id/unlike`

**Header:**
```
Authorization: Bearer <eyJhbGciOi...>
```

**Response (200) - Sikeres törlés:**
```json
{
  "message": "Lájk sikeresen visszavonva."
}
```

**Response (404) - Ha nem volt lájkolva:**
```json
{
  "error": "Nem található lájk ehhez a poszthoz."
}
```

**Response (404) - Ha a poszt nem létezik:**
```json
{
  "error": "A poszt nem található."
}
```

### Teljes végpont leírásokat részletesen lásd a repo `/routes` és `/controllers` könyvtáraiban!

---

## III. Tesztelés és dokumentáció

### Postman tesztelés lépésről lépésre

#### 1. Projekt indítása

**Opció A - XAMPP:**
- Indítsd el az XAMPP Apache és MySQL szolgáltatásokat
- URL: `http://127.0.0.1/20feladat/public/api`

**Opció B - Laravel serve:**
```bash
php artisan serve
```
- URL: `http://127.0.0.1:8000/api`

#### 2. API teszt végpont

**GET** `http://127.0.0.1:8000/api/ping`

Headers:
```
Content-Type: application/json
Accept: application/json
```

Expected Response (200):
```json
{
  "message": "API works!"
}
```

---

#### 3. Regisztráció tesztelése

**POST** `http://127.0.0.1:8000/api/register`

Headers:
```
Content-Type: application/json
Accept: application/json
```

Body (raw JSON):
```json
{
  "name": "Kiss János",
  "email": "janos@example.com",
  "password": "Jelszo_2025",
  "password_confirmation": "Jelszo_2025",
  "profile_picture": "https://via.placeholder.com/150"
}
```

Expected Response (201):
```json
{
  "message": "User created successfully",
  "user": {
    "id": 6,
    "name": "Kiss János",
    "email": "janos@example.com",
    "profile_picture": "https://via.placeholder.com/150"
  }
}
```

**Hibás regisztráció tesztelése (duplikált email):**
- Ugyanaz a kérés másodszorra
- Expected Response (422):
```json
{
  "message": "Failed to register user",
  "errors": {
    "email": [
      "The email has already been taken."
    ]
  }
}
```

---

#### 4. Bejelentkezés tesztelése

**POST** `http://127.0.0.1:8000/api/login`

Headers:
```
Content-Type: application/json
Accept: application/json
```

Body (raw JSON):
```json
{
  "email": "janos@example.com",
  "password": "Jelszo_2025"
}
```

Expected Response (200):
```json
{
  "message": "Login successful",
  "user": {
    "id": 1,
    "name": "Kiss János",
    "email": "janos@example.com",
    "profile_picture": "https://via.placeholder.com/150"
  },
  "access": {
    "token": "2|7Fbr79b5zn8RxMfOqfdzZ31SnGWvgDidjahbdRfL2a98cfd8",
    "token_type": "Bearer"
  }
}
```

**Hibás bejelentkezés tesztelése:**
```json
{
  "email": "janos@example.com",
  "password": "rossz_jelszo"
}
```

Expected Response (401):
```json
{
  "message": "Invalid email or password"
}
```

---

#### 5. Védett végpontok tesztelése

**Minden következő kérésnél add hozzá ezt a headert:**
```
Authorization: Bearer 2|7Fbr79b5zn8RxMfOqfdzZ31SnGWvgDidjahbdRfL2a98cfd8
Content-Type: application/json
Accept: application/json
```

##### 5.1 Saját profil lekérése

**GET** `http://127.0.0.1:8000/api/users/me`

Expected Response (200):
```json
{
  "user": {
    "id": 1,
    "name": "Kiss János",
    "email": "janos@example.com",
    "profile_picture": "https://via.placeholder.com/150"
  }
}
```

##### 5.2 Összes poszt listázása

**GET** `http://127.0.0.1:8000/api/posts`

Expected Response (200):
```json
{
  "posts": [
    {
      "id": 1,
      "user": {
        "id": 1,
        "name": "Kiss János",
        "email": "janos@example.com",
        "profile_picture": "https://via.placeholder.com/150"
      },
      "content": "Ez az első posztom! Milyen szép nap van ma!",
      "image": "https://via.placeholder.com/600x400",
      "likes_count": 3,
      "created_at": "2025-12-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "user": {...},
      "content": "Tegnap este láttam a legszebb naplementét!",
      "image": "https://via.placeholder.com/600x400",
      "likes_count": 0,
      "created_at": "2025-12-01T09:30:00.000000Z"
    }
  ]
}
```

##### 5.3 Egy poszt részleteinek lekérése

**GET** `http://127.0.0.1:8000/api/posts/1`

Expected Response (200):
```json
{
  "post": {
    "id": 1,
    "user": {
      "id": 1,
      "name": "Kiss János",
      "email": "janos@example.com",
      "profile_picture": "https://via.placeholder.com/150"
    },
    "content": "Ez az első posztom!",
    "image": "https://via.placeholder.com/600x400",
    "likes": [
      {
        "user_id": 2,
        "user_name": "Nagy Anna",
        "created_at": "2025-12-01T10:30:00.000000Z"
      },
      {
        "user_id": 3,
        "user_name": "Kovács Péter",
        "created_at": "2025-12-01T10:25:00.000000Z"
      }
    ],
    "created_at": "2025-12-01T10:00:00.000000Z",
    "updated_at": "2025-12-01T10:00:00.000000Z"
  }
}
```

##### 5.4 Új poszt létrehozása

**POST** `http://127.0.0.1:8000/api/posts`

Body (raw JSON):
```json
{
  "content": "Ez egy új teszt poszt!",
  "image": "https://via.placeholder.com/600x400"
}
```

Expected Response (201):
```json
{
  "message": "Post created successfully",
  "post": {
    "id": 6,
    "user": {
      "id": 1,
      "name": "Kiss János",
      "email": "janos@example.com",
      "profile_picture": "https://via.placeholder.com/150"
    },
    "content": "Ez egy új teszt poszt!",
    "image": "https://via.placeholder.com/600x400",
    "created_at": "2025-12-01T11:00:00.000000Z",
    "updated_at": "2025-12-01T11:00:00.000000Z"
  }
}
```

**Validációs hiba tesztelése (hiányzó content):**
```json
{
  "image": "https://via.placeholder.com/600x400"
}
```

Expected Response (422):
```json
{
  "message": "The content field is required.",
  "errors": {
    "content": ["The content field is required."]
  }
}
```

##### 5.5 Poszt módosítása

**PUT** `http://127.0.0.1:8000/api/posts/6`

Body (raw JSON):
```json
{
  "content": "Módosított tartalom!",
  "image": "https://via.placeholder.com/800x600"
}
```

Expected Response (200):
```json
{
  "message": "Post updated successfully",
  "post": {
    "id": 6,
    "user": {...},
    "content": "Módosított tartalom!",
    "image": "https://via.placeholder.com/800x600",
    "created_at": "2025-12-01T11:00:00.000000Z",
    "updated_at": "2025-12-01T11:15:00.000000Z"
  }
}
```

**Más felhasználó posztjának módosítása (Forbidden):**

**PUT** `http://127.0.0.1:8000/api/posts/2` (ha ez nem a te posztod)

Expected Response (403):
```json
{
  "message": "Forbidden"
}
```

##### 5.6 Poszt likeolása

**POST** `http://127.0.0.1:8000/api/posts/1/like`

Expected Response (201):
```json
{
  "message": "Post liked successfully",
  "like": {
    "id": 6,
    "user_id": 1,
    "post_id": 1,
    "created_at": "2025-12-01T11:30:00.000000Z"
  }
}
```

**Ugyanazon poszt újbóli likeolása (Conflict):**

Expected Response (409):
```json
{
  "message": "Already liked this post"
}
```

##### 5.7 Like visszavonása

**DELETE** `http://127.0.0.1:8000/api/posts/1/unlike`

Expected Response (200):
```json
{
  "message": "Post unliked successfully"
}
```

**Nem létező like törlése:**

Expected Response (404):
```json
{
  "message": "Like not found"
}
```

##### 5.8 Poszt like-jainak listázása

**GET** `http://127.0.0.1:8000/api/posts/1/likes`

Expected Response (200):
```json
{
  "likes": [
    {
      "id": 1,
      "user": {
        "id": 2,
        "name": "Nagy Anna",
        "email": "anna@example.com",
        "profile_picture": "https://via.placeholder.com/150"
      },
      "created_at": "2025-12-01T10:30:00.000000Z"
    },
    {
      "id": 2,
      "user": {
        "id": 3,
        "name": "Kovács Péter",
        "email": "peter@example.com",
        "profile_picture": "https://via.placeholder.com/150"
      },
      "created_at": "2025-12-01T10:25:00.000000Z"
    }
  ]
}
```

##### 5.9 Felhasználó posztjainak listázása

**GET** `http://127.0.0.1:8000/api/users/1/posts`

Expected Response (200):
```json
{
  "posts": [
    {
      "id": 1,
      "content": "Ez az első posztom!",
      "image": "https://via.placeholder.com/600x400",
      "likes_count": 3,
      "created_at": "2025-12-01T10:00:00.000000Z",
      "updated_at": "2025-12-01T10:00:00.000000Z"
    },
    {
      "id": 2,
      "content": "Tegnap este láttam a legszebb naplementét!",
      "image": "https://via.placeholder.com/600x400",
      "likes_count": 0,
      "created_at": "2025-12-01T09:30:00.000000Z",
      "updated_at": "2025-12-01T09:30:00.000000Z"
    }
  ]
}
```

##### 5.10 Poszt törlése (Soft Delete)

**DELETE** `http://127.0.0.1:8000/api/posts/6`

Expected Response (200):
```json
{
  "message": "Post deleted successfully (soft delete)"
}
```

**Végleges törlés force paraméterrel:**

**DELETE** `http://127.0.0.1:8000/api/posts/6?force=true`

Expected Response (200):
```json
{
  "message": "Post permanently deleted"
}
```

**Már törölt poszt törlése:**

Expected Response (404):
```json
{
  "message": "Post not found"
}
```

##### 5.11 Kijelentkezés

**POST** `http://127.0.0.1:8000/api/logout`

Expected Response (200):
```json
{
  "message": "Logout successful"
}
```

**Kijelentkezés után védett végpont hívása:**

**GET** `http://127.0.0.1:8000/api/users/me`

Expected Response (401):
```json
{
  "message": "Unauthenticated."
}
```

---

### Tesztelési sorrend összefoglalója

1.  **API teszt** - `/ping` endpoint
2.  **Regisztráció** - új felhasználó létrehozása
3.  **Bejelentkezés** - token megszerzése
4.  **Saját profil** - token működésének ellenőrzése
5.  **Posztok listázása** - seeder adatok megtekintése
6.  **Új poszt** - poszt létrehozása
7.  **Poszt módosítása** - saját poszt szerkesztése
8.  **Poszt likeolása** - like hozzáadása
9.  **Like-ok listázása** - poszt likejai
10.  **Like visszavonása** - unlike funkció
11.  **User posztjai** - felhasználó által írt posztok
12.  **Poszt törlése** - saját poszt eltávolítása
13.  **Kijelentkezés** - token törlése

---

### Hibakezelés összefoglalója

| HTTP Kód | Jelentés | Példa |
|----------|----------|-------|
| 200 OK | Sikeres kérés | Poszt listázása, like visszavonása |
| 201 Created | Erőforrás létrehozva | Új poszt, regisztráció, like |
| 401 Unauthorized | Nincs vagy érvénytelen token | Hiányzó Authorization header |
| 403 Forbidden | Nincs jogosultság | Más felhasználó posztjának módosítása |
| 404 Not Found | Nem található | Nem létező poszt vagy like |
| 409 Conflict | Konfliktus | Poszt már likeolva |
| 422 Unprocessable Entity | Validációs hiba | Hiányzó vagy érvénytelen mezők |

---

## További megjegyzések

- **Minden privát végpont Bearer tokent vár fejlécben** (`Authorization: Bearer <token>`).
- A token a `personal_access_tokens` táblában tárolódik
- Kijelentkezéskor az összes token törlődik
- A posztokat csak a tulajdonosuk módosíthatja/törölheti
- Egy felhasználó egy posztot csak egyszer likeolhat

---
