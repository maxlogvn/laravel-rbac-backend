# Swagger API Documentation Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Install and configure L5-Swagger with JWT authentication for Laravel 12 API documentation

**Architecture:** Use L5-Swagger package to auto-generate OpenAPI 3.0 specification from PHPDoc annotations in controllers, integrate tymon/jwt-auth for JWT authentication, and serve Swagger UI at /api/documentation route.

**Tech Stack:** Laravel 12, L5-Swagger (darkaonline/l5-swagger), JWT-Auth (tymon/jwt-auth), Pest 4, OpenAPI 3.0

---

## Task 1: Install L5-Swagger Package

**Files:**
- Modify: `composer.json`
- Create: `config/l5-swagger.php` (via publish)

**Step 1: Require L5-Swagger package**

Run: `composer require darkaonline/l5-swagger --no-interaction`

Expected output:
```
Info from https://repo.packagist.org: .../darkaonline/l5-swagger.json
./composer.json has been updated
Installing darkaonline/l5-swagger
```

**Step 2: Publish L5-Swagger configuration**

Run: `php artisan vendor:publish --provider="L5Swagger\L5SwaggerServiceProvider"`

Expected output:
```
Copied File [\config\l5-swagger.php]
Publishing complete.
```

**Step 3: Verify configuration file exists**

Run: `ls -la config/l5-swagger.php`

Expected: File exists with ~500+ lines

**Step 4: Commit**

```bash
git add composer.json composer.lock config/l5-swagger.php
git commit -m "feat: install L5-Swagger package

- Install darkaonline/l5-swagger via composer
- Publish L5-Swagger configuration
- Ready for Swagger API documentation"
```

---

## Task 2: Install and Configure JWT-Auth Package

**Files:**
- Modify: `composer.json`
- Create: `config/jwt.php` (via publish)
- Modify: `app/Models/User.php`
- Modify: `.env`

**Step 1: Require JWT-Auth package**

Run: `composer require tymon/jwt-auth --no-interaction`

Expected output:
```
Installing tymon/jwt-auth
./composer.json has been updated
```

**Step 2: Publish JWT configuration**

Run: `php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"`

Expected: `Copied File [\config\jwt.php]`

**Step 3: Generate JWT secret key**

Run: `php artisan jwt:secret`

Expected:
```
jwt-secret successfully set in .env file
```

**Step 4: Add HasApiTokens trait to User model**

Read: `app/Models/User.php`

Add trait to User class:
```php
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
```

**Step 5: Update JWT config for API auth**

Read: `config/jwt.php`

Find and set:
```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'jwt', // Changed from 'token' to 'jwt'
        'provider' => 'users',
        'hash' => false,
    ],
],
```

**Step 6: Commit**

```bash
git add composer.json composer.lock config/jwt.php app/Models/User.php .env
git commit -m "feat: install JWT-Auth package

- Install tymon/jwt-auth via composer
- Publish JWT configuration
- Generate JWT secret key
- Add JWTSubject implementation to User model
- Configure API guard to use JWT driver"
```

---

## Task 3: Add Base Swagger Annotations to Controller

**Files:**
- Modify: `app/Http/Controllers/Controller.php`

**Step 1: Write test for swagger annotations**

Create: `tests/Feature/SwaggerAnnotationsTest.php`
```php
<?php

use Tests\TestCase;

it('base controller has swagger info annotation', function () {
    $reflection = new ReflectionClass(App\Http\Controllers\Controller::class);
    $comment = $reflection->getDocComment();

    expect($comment)->not->toBeFalse()
        ->and($comment)->toContain('@OA\Info')
        ->and($comment)->toContain('@OA\SecurityScheme');
});
```

Run: `php artisan test --filter=SwaggerAnnotationsTest`

Expected: FAIL - Controller doesn't have annotations yet

**Step 2: Add Swagger annotations to base Controller**

Read: `app/Http/Controllers/Controller.php`

Replace entire content with:
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Laravel API",
 *     version="1.0.0",
 *     description="API documentation for Laravel application",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     )
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter JWT token obtained from /api/auth/login"
 * )
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
```

**Step 3: Run test to verify passes**

Run: `php artisan test --filter=SwaggerAnnotationsTest`

Expected: PASS

**Step 4: Commit**

```bash
git add app/Http/Controllers/Controller.php tests/Feature/SwaggerAnnotationsTest.php
git commit -m "feat: add base Swagger annotations to Controller

- Add OpenAPI info metadata (title, version, description)
- Add server configuration
- Add JWT Bearer authentication security scheme
- Add test to verify annotations exist"
```

---

## Task 4: Annotate HelloController with Swagger

**Files:**
- Modify: `app/Http/Controllers/HelloController.php`

**Step 1: Write test for HelloController annotations**

Create: `tests/Feature/HelloControllerSwaggerTest.php`
```php
<?php

use Tests\TestCase;

it('hello controller has swagger documentation', function () {
    $reflection = new ReflectionClass(App\Http\Controllers\HelloController::class);
    $method = $reflection->getMethod('__invoke');
    $comment = $method->getDocComment();

    expect($comment)->not->toBeFalse()
        ->and($comment)->toContain('@OA\Get')
        ->and($comment)->toContain('/api/hello');
});

it('hello endpoint returns documented response', function () {
    $this->getJson('/api/hello')
        ->assertStatus(200)
        ->assertJson([
            'message' => 'Hello World',
            'status' => 'success',
        ]);
});
```

Run: `php artisan test --filter=HelloControllerSwaggerTest`

Expected: FAIL - No annotations yet

**Step 2: Add Swagger annotations to HelloController**

Read: `app/Http/Controllers/HelloController.php`

Replace entire content with:
```php
<?php

namespace App\Http\Controllers;

/**
 * @OA\Get(
 *     path="/api/hello",
 *     summary="Get hello message",
 *     description="Returns a friendly greeting message",
 *     tags={"Hello"},
 *     @OA\Response(
 *         response=200,
 *         description="Successful response",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="message",
 *                     type="string",
 *                     example="Hello World"
 *                 ),
 *                 @OA\Property(
 *                     property="status",
 *                     type="string",
 *                     example="success"
 *                 )
 *             )
 *         )
 *     )
 * )
 */
class HelloController extends Controller
{
    public function __invoke(): array
    {
        return [
            'message' => 'Hello World',
            'status' => 'success',
        ];
    }
}
```

**Step 3: Run tests to verify pass**

Run: `php artisan test --filter=HelloControllerSwaggerTest`

Expected: PASS (2 tests)

**Step 4: Commit**

```bash
git add app/Http/Controllers/HelloController.php tests/Feature/HelloControllerSwaggerTest.php
git commit -m "feat: add Swagger documentation to HelloController

- Add OpenAPI GET endpoint documentation
- Document response schema with message and status
- Add tests to verify annotations and API response"
```

---

## Task 5: Generate Swagger Documentation

**Files:**
- Create: `storage/api-docs/api-docs.json` (auto-generated)
- Modify: `config/l5-swagger.php`

**Step 1: Write test for documentation generation**

Create: `tests/Feature/SwaggerGenerationTest.php`
```php
<?php

use Tests\TestCase;

it('generates openapi json file', function () {
    expect(storage_path('api-docs/api-docs.json'))->toBeFile();
});

it('openapi json contains valid structure', function () {
    $json = json_decode(file_get_contents(storage_path('api-docs/api-docs.json')), true);

    expect($json)->toHaveKey('openapi')
        ->and($json)->toHaveKey('info')
        ->and($json)->toHaveKey('paths')
        ->and($json['openapi'])->toBe('3.0.0');
});

it('openapi json contains hello endpoint', function () {
    $json = json_decode(file_get_contents(storage_path('api-docs/api-docs.json')), true);

    expect($json['paths'])->toHaveKey('/api/hello');
});
```

Run: `php artisan test --filter=SwaggerGenerationTest`

Expected: FAIL - Documentation not generated yet

**Step 2: Configure L5-Swagger to scan annotations**

Read: `config/l5-swagger.php`

Find and update these settings:
```php
'generate_always' => false, // Keep false for performance
'generate_yaml_copy' => false,

'documents' => [
    'api-docs' => [
        'apis' => [
            [
                'domain' => env('L5_SWAGGER_DOMAIN_CONST_HOST', env('APP_URL')),
                'path' => storage_path('api-docs/api-docs.json'),
            ],
        ],
        'scan' => [
            /**
             * Path to scan for annotations
             */
            'paths' => [
                base_path('app'),
            ],
        ],
    ],
],
```

**Step 3: Generate Swagger documentation**

Run: `php artisan l5-swagger:generate`

Expected output:
```
Generated swagger docs for api-docs in /storage/api-docs/api-docs.json
Regenerated openapi.json
```

**Step 4: Verify generated file exists**

Run: `ls -lh storage/api-docs/api-docs.json`

Expected: File exists with size > 0

**Step 5: Run tests to verify pass**

Run: `php artisan test --filter=SwaggerGenerationTest`

Expected: PASS (3 tests)

**Step 6: Test Swagger UI route**

Run: `php artisan serve --background`

Visit: `http://localhost:8000/api/documentation`

Expected: Swagger UI displays with Hello endpoint

**Step 7: Commit**

```bash
git add config/l5-swagger.php tests/Feature/SwaggerGenerationTest.php
git add storage/api-docs/api-docs.json
git commit -m "feat: generate Swagger documentation

- Configure L5-Swagger to scan app directory
- Generate OpenAPI 3.0 JSON specification
- Add tests to verify documentation generation
- Swagger UI accessible at /api/documentation"
```

---

## Task 6: Add Composer Scripts for Convenience

**Files:**
- Modify: `composer.json`

**Step 1: Add docs generation script**

Read: `composer.json`

Add to `scripts` section (remove old "docs" script if exists):
```json
"scripts": {
    "docs": "php artisan l5-swagger:generate",
    "docs-validate": "php artisan l5-swagger:generate --validate",
    // ... other scripts
}
```

**Step 2: Test scripts work**

Run: `composer docs`

Expected: Documentation regenerates successfully

Run: `composer docs-validate`

Expected: No validation errors

**Step 3: Commit**

```bash
git add composer.json
git commit -m "chore: add convenience scripts for documentation

- Add 'composer docs' to generate Swagger docs
- Add 'composer docs-validate' to validate annotations"
```

---

## Task 7: Create Auth Controller for JWT Login

**Files:**
- Create: `app/Http/Controllers/AuthController.php`
- Modify: `routes/api.php`

**Step 1: Write test for login endpoint**

Create: `tests/Feature/AuthControllerTest.php`
```php
<?php

use App\Models\User;
use Tests\TestCase;

it('issues jwt token on valid credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
        ]);
});

it('fails on invalid credentials', function () {
    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401);
});
```

Run: `php artisan test --filter=AuthControllerTest`

Expected: FAIL - AuthController doesn't exist

**Step 2: Create AuthController**

Create: `app/Http/Controllers/AuthController.php`
```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API authentication endpoints"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     summary="Login user and generate JWT token",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"email","password"},
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="password", type="string", format="password")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }
}
```

**Step 3: Add authentication routes**

Read: `routes/api.php`

Add login route:
```php
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HelloController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', AuthController::class . '@login');
Route::get('/hello', HelloController::class);
```

**Step 4: Run tests to verify pass**

Run: `php artisan test --filter=AuthControllerTest`

Expected: PASS (2 tests)

**Step 5: Regenerate Swagger docs**

Run: `php artisan l5-swagger:generate`

Expected: Documentation includes login endpoint

**Step 6: Commit**

```bash
git add app/Http/Controllers/AuthController.php routes/api.php tests/Feature/AuthControllerTest.php storage/api-docs/api-docs.json
git commit -m "feat: add JWT authentication controller

- Create AuthController with login endpoint
- Add Swagger documentation for login API
- Add authentication tests
- Update API routes
- Regenerate Swagger documentation"
```

---

## Task 8: Create Protected Endpoint Example

**Files:**
- Create: `app/Http/Controllers/ProtectedController.php`
- Modify: `routes/api.php`
- Create: `config/auth.php` (if not exists or modify)

**Step 1: Write test for protected endpoint**

Create: `tests/Feature/ProtectedEndpointTest.php`
```php
<?php

use App\Models\User;
use Tests\TestCase;

it('returns 401 without token', function () {
    $this->getJson('/api/protected')
        ->assertStatus(401);
});

it('returns 401 with invalid token', function () {
    $this->withToken('invalid-token')
        ->getJson('/api/protected')
        ->assertStatus(401);
});

it('returns data with valid token', function () {
    $user = User::factory()->create();
    $token = auth()->login($user);

    $this->withToken($token)
        ->getJson('/api/protected')
        ->assertStatus(200)
        ->assertJson([
            'message' => 'Protected data',
            'user_id' => $user->id,
        ]);
});
```

Run: `php artisan test --filter=ProtectedEndpointTest`

Expected: FAIL - ProtectedController doesn't exist

**Step 2: Create ProtectedController**

Create: `app/Http/Controllers/ProtectedController.php`
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Protected",
 *     description="Protected endpoints requiring authentication"
 * )
 */
class ProtectedController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/protected",
     *     summary="Get protected data",
     *     description="Requires valid JWT token",
     *     tags={"Protected"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="user_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function __invoke(Request $request): array
    {
        return [
            'message' => 'Protected data',
            'user_id' => $request->user()->id,
        ];
    }
}
```

**Step 3: Add protected route with JWT auth middleware**

Read: `routes/api.php`

Add protected route:
```php
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HelloController;
use App\Http\Controllers\ProtectedController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', AuthController::class . '@login');
Route::get('/hello', HelloController::class);

Route::middleware('auth:api')->get('/protected', ProtectedController::class);
```

**Step 4: Configure auth guard**

Read: `config/auth.php`

Ensure API guard uses JWT:
```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    'api' => [
        'driver' => 'jwt', // Changed from 'token' to 'jwt'
        'provider' => 'users',
        'hash' => false,
    ],
],
```

**Step 5: Run tests to verify pass**

Run: `php artisan test --filter=ProtectedEndpointTest`

Expected: PASS (3 tests)

**Step 6: Regenerate Swagger docs**

Run: `php artisan l5-swagger:generate`

Expected: Documentation shows locked icon for protected endpoint

**Step 7: Test manually with Swagger UI**

Visit: `http://localhost:8000/api/documentation`

1. Try `/api/protected` without token → Should fail with 401
2. Call `/api/auth/login` with test credentials → Get token
3. Click "Authorize" button → Enter token
4. Try `/api/protected` again → Should return data

**Step 8: Commit**

```bash
git add app/Http/Controllers/ProtectedController.php routes/api.php tests/Feature/ProtectedEndpointTest.php storage/api-docs/api-docs.json config/auth.php
git commit -m "feat: add protected endpoint with JWT authentication

- Create ProtectedController with authenticated endpoint
- Add JWT auth middleware to route
- Document security requirement in Swagger
- Add tests for authenticated/unauthenticated access
- Configure API guard to use JWT driver
- Regenerate Swagger documentation"
```

---

## Task 9: Final Verification and Documentation

**Files:**
- Create: `README_SWAGGER.md`
- Modify: (various)

**Step 1: Run full test suite**

Run: `php artisan test --compact`

Expected: All tests pass (should be 9+ tests now)

**Step 2: Validate Swagger documentation**

Run: `php artisan l5-swagger:generate --validate`

Expected: No validation errors

**Step 3: Create usage documentation**

Create: `README_SWAGGER.md`
```markdown
# Swagger API Documentation

This project uses L5-Swagger to generate interactive API documentation.

## Installation

Packages installed:
- `darkaonline/l5-swagger` - Swagger documentation generator
- `tymon/jwt-auth` - JWT authentication

## Accessing Documentation

Development: http://localhost:8000/api/documentation

## Generating Documentation

```bash
# Generate docs
composer docs

# Generate and validate
composer docs-validate

# Or use artisan directly
php artisan l5-swagger:generate
php artisan l5-swagger:generate --validate
```

## Authentication

This API uses JWT (JSON Web Tokens) for authentication.

### Getting a Token

**Request:**
```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "your-password"
}
```

**Response:**
```json
{
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "bearer",
  "expires_in": 3600
}
```

### Using the Token

**Method 1: Authorization Header**
```bash
Authorization: Bearer {your-token}
```

**Method 2: Swagger UI**
1. Click the "Authorize" button (lock icon)
2. Enter: `Bearer {your-token}`
3. Click "Authorize"
4. Close the dialog

## Available Endpoints

### Public Endpoints
- `GET /api/hello` - Hello World example

### Authentication
- `POST /api/auth/login` - Get JWT token

### Protected Endpoints (require JWT)
- `GET /api/protected` - Protected data example

## Annotating Controllers

Add Swagger annotations to document your endpoints:

```php
/**
 * @OA\Get(
 *     path="/api/endpoint",
 *     summary="Endpoint description",
 *     tags={"Category"},
 *     @OA\Response(
 *         response=200,
 *         description="Success"
 *     )
 * )
 */
public function methodName()
{
    // ...
}
```

For protected endpoints:
```php
/**
 * @OA\Get(
 *     path="/api/protected",
 *     security={{"bearerAuth":{}}},
 *     // ... rest of annotation
 * )
 */
```

## Common Annotations

- `@OA\Info` - API metadata (in base Controller)
- `@OA\Get`, `@OA\Post`, etc. - HTTP methods
- `@OA\Parameter` - Query/path parameters
- `@OA\RequestBody` - Request body schema
- `@OA\Response` - Response documentation
- `@OA\Property` - Object properties
- `@OA\Tag` - Endpoint categories

## Troubleshooting

**Documentation not updating:**
```bash
php artisan l5-swagger:generate
```

**Validation errors:**
```bash
php artisan l5-swagger:generate --validate
```

Check annotation syntax in your controllers.

## References

- [OpenAPI Specification](https://swagger.io/specification/)
- [L5-Swagger Documentation](https://github.com/DarkaOnLine/L5-Swagger)
- [JWT-Auth Documentation](https://github.com/tymondesigns/jwt-auth)
```

**Step 4: Update main README**

Read: `README.md`

Add section at end:
```markdown
## API Documentation

Interactive API documentation is available via Swagger UI. See [README_SWAGGER.md](README_SWAGGER.md) for details.
```

**Step 5: Final verification checklist**

Run all checks:
```bash
# 1. Tests pass
php artisan test --compact

# 2. Documentation validates
php artisan l5-swagger:generate --validate

# 3. Swagger UI accessible
curl -I http://localhost:8000/api/documentation

# 4. API endpoints work
curl http://localhost:8000/api/hello

# 5. Login works
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}'
```

All should pass/succeed.

**Step 6: Commit**

```bash
git add README_SWAGGER.md README.md
git commit -m "docs: add comprehensive Swagger documentation guide

- Add README_SWAGGER.md with usage instructions
- Document authentication flow
- Include annotation examples
- Add troubleshooting guide
- Update main README with reference"
```

---

## Success Criteria

After completing all tasks:
- ✅ Swagger UI accessible at `/api/documentation`
- ✅ OpenAPI 3.0 JSON auto-generated from PHPDoc
- ✅ JWT authentication working with `/api/auth/login`
- ✅ Protected endpoint demonstrates JWT auth
- ✅ All tests passing (10+ tests)
- ✅ Documentation validates without errors
- ✅ Usage guide created

## Package Versions

- `darkaonline/l5-swagger`: ^8.3 (Laravel 12 compatible)
- `tymon/jwt-auth`: ^2.0 (Laravel 12 compatible)
