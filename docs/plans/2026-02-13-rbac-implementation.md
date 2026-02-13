# Hệ thống Role-Based Access Control (RBAC) - Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Xây dựng hệ thống phân quyền role-based với 3 roles (admin, moderator, member) và permission-based access control sử dụng spatie/laravel-permission package cho Laravel 12 API với JWT authentication.

**Architecture:**
- Sử dụng spatie/laravel-permission package để quản lý roles & permissions
- Middleware-based authorization để protect API endpoints
- JWT guard 'api' cho authentication
- Permission granularity cho từng action (create posts, delete posts, manage users, etc.)

**Tech Stack:**
- spatie/laravel-permission ^6.0
- tymon/jwt-auth (existing)
- Laravel 12
- Pest 4 for testing
- OpenAPI/Swagger for documentation

---

## Task 1: Cài đặt spatie/laravel-permission package

**Files:**
- Modify: `composer.json` (via composer require)

**Step 1: Install package**

Run:
```bash
composer require spatie/laravel-permission
```

Expected output:
```
Info from https://repo.packagist.org: ....
Installing spatie/laravel-permission (x.x.x)
  - Downloading spatie/laravel-permission (x.x.x)
  - Installing spatie/laravel-permission (x.x.x): Extracting archive
./composer.json has been updated
Running composer update spatie/laravel-permission
...
Discovered package: spatie/laravel-permission
```

**Step 2: Publish config and migrations**

Run:
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Expected output:
```
Publishing complete.
Copied File [\config\permission.php]
Copied Directory [\database\migrations\{timestamp}_create_permission_tables.php]
```

**Step 3: Commit**

```bash
git add composer.json composer.lock config/permission.php database/migrations
git commit -m "feat: install spatie/laravel-permission package"
```

---

## Task 2: Chạy migrations để tạo permission tables

**Files:**
- Run: database migrations

**Step 1: Run migrations**

Run:
```bash
php artisan migrate
```

Expected output:
```
Migrating: 2024_XX_XX_XXXXXX_create_permission_tables
Migrated:  2024_XX_XX_XXXXXX_create_permission_tables (XX.XX ms)
```

Verify tables created:
```bash
php artisan db:table permissions
php artisan db:table roles
php artisan db:table model_has_roles
php artisan db:table model_has_permissions
php artisan db:table role_has_permissions
```

**Step 2: Commit**

```bash
git add database/migrations/*
git commit -m "feat: create permission tables"
```

---

## Task 3: Thêm HasRoles trait vào User model

**Files:**
- Modify: `app/Models/User.php`

**Step 1: Write failing test**

Create test file:
```php
<?php

use App\Models\User;

// tests/Unit/UserRoleTest.php

test('user can be assigned role', function () {
    $user = User::factory()->create();

    $user->assignRole('admin');

    expect($user->roles->first()->name)->toBe('admin');
});
```

**Step 2: Run test to verify it fails**

Run:
```bash
php artisan test --filter=UserRoleTest
```

Expected: FAIL - Method `assignRole` does not exist.

**Step 3: Add HasRoles trait to User model**

Modify `app/Models/User.php`:

Add at top of file after namespace:
```php
use Spatie\Permission\Traits\HasRoles;
```

Add `HasRoles` to trait list in class:
```php
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;  // ← Add HasRoles here
```

Full modification at line 14:
```php
    use HasFactory, Notifiable, HasRoles;
```

**Step 4: Run test to verify it passes**

Run:
```bash
php artisan test --filter=UserRoleTest
```

Expected: PASS

**Step 5: Commit**

```bash
git add app/Models/User.php tests/Unit/UserRoleTest.php
git commit -m "feat: add HasRoles trait to User model"
```

---

## Task 4: Register permission middleware

**Files:**
- Modify: `bootstrap/app.php`

**Step 1: Add middleware aliases**

Modify `bootstrap/app.php` in the `withMiddleware()` section:

Around line 14-16, change:
```php
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
```

To:
```php
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
```

**Step 2: Verify middleware is registered**

Run:
```bash
php artisan route:list --columns=uri,middleware
```

Expected: You should see middleware alias is available (no error).

**Step 3: Commit**

```bash
git add bootstrap/app.php
git commit -m "feat: register permission middleware aliases"
```

---

## Task 5: Tạo RolePermissionSeeder

**Files:**
- Create: `database/seeders/RolePermissionSeeder.php`

**Step 1: Create seeder file**

Run:
```bash
php artisan make:seeder RolePermissionSeeder
```

**Step 2: Write seeder code**

Replace content of `database/seeders/RolePermissionSeeder.php` with:
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'manage users',
            'manage roles',
            'delete posts',
            'lock users',
            'create posts',
            'edit own posts',
            'comment',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        // Create Roles and assign Permissions
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $moderator = Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'api']);
        $member = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'api']);

        // Admin gets all permissions
        $admin->syncPermissions(Permission::all());

        // Moderator permissions
        $moderator->syncPermissions([
            'delete posts',
            'lock users',
            'create posts',
            'edit own posts',
            'comment',
        ]);

        // Member permissions
        $member->syncPermissions([
            'create posts',
            'edit own posts',
            'comment',
        ]);
    }
}
```

**Step 3: Commit**

```bash
git add database/seeders/RolePermissionSeeder.php
git commit -m "feat: create RolePermissionSeeder"
```

---

## Task 6: Run seeder và verify

**Files:**
- Execute: database seeder

**Step 1: Run seeder**

Run:
```bash
php artisan db:seed --class=RolePermissionSeeder
```

Expected output:
```
Seeding: Database\Seeders\RolePermissionSeeder
```

**Step 2: Verify roles and permissions created**

Run:
```bash
php artisan tinker --execute="echo 'Roles: ' . \Spatie\Permission\Models\Role::count() . PHP_EOL; echo 'Permissions: ' . \Spatie\Permission\Models\Permission::count();"
```

Expected:
```
Roles: 3
Permissions: 7
```

**Step 3: Verify role-permission mapping**

Run tinker:
```bash
php artisan tinker
```

Then run:
```php
$admin = \Spatie\Permission\Models\Role::findByName('admin', 'api');
echo $admin->permissions->pluck('name')->implode(', ');
```

Expected: All 7 permissions listed.

**Step 4: Update DatabaseSeeder**

Modify `database/seeders/DatabaseSeeder.php` to include RolePermissionSeeder:

Add to run() method:
```php
$this->call([
    RolePermissionSeeder::class,
]);
```

**Step 5: Commit**

```bash
git add database/seeders/DatabaseSeeder.php
git commit -m "chore: add RolePermissionSeeder to DatabaseSeeder"
```

---

## Task 7: Thêm role states vào UserFactory

**Files:**
- Modify: `database/factories/UserFactory.php`

**Step 1: Add admin state**

Read current `database/factories/UserFactory.php` first, then add state method:

Add at end of class (after definition() method):
```php
    public function admin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole('admin'));
    }

    public function moderator(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole('moderator'));
    }

    public function member(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole('member'));
    }
```

**Step 2: Test factory states**

Create test `tests/Unit/UserFactoryTest.php`:
```php
<?php

use App\Models\User;

test('admin factory creates admin user', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->hasRole('admin'))->toBeTrue();
});

test('moderator factory creates moderator user', function () {
    $moderator = User::factory()->moderator()->create();

    expect($moderator->hasRole('moderator'))->toBeTrue();
});
```

Run:
```bash
php artisan test --filter=UserFactoryTest
```

Expected: PASS

**Step 3: Commit**

```bash
git add database/factories/UserFactory.php tests/Unit/UserFactoryTest.php
git commit -m "feat: add role states to UserFactory"
```

---

## Task 8: Thêm method me() vào AuthController

**Files:**
- Modify: `app/Http/Controllers/AuthController.php`

**Step 1: Write failing test**

Create `tests/Feature/Auth/MeEndpointTest.php`:
```php
<?php

use App\Models\User;
use Illuminate\Support\Str;

test('authenticated user can get their info with roles', function () {
    $user = User::factory()->admin()->create();

    $token = auth('api')->login($user);

    $response = $this->withToken($token)
        ->getJson('/api/auth/me')
        ->assertStatus(200)
        ->assertJsonPath('user.email', $user->email)
        ->assertJsonPath('user.roles', ['admin']);
});

test('guest cannot access me endpoint', function () {
    $this->getJson('/api/auth/me')
        ->assertStatus(401);
});
```

**Step 2: Run test to verify it fails**

Run:
```bash
php artisan test --filter=MeEndpointTest
```

Expected: FAIL - Route `/api/auth/me` does not exist.

**Step 3: Add route**

Add to `routes/api.php`:
```php
Route::middleware(['auth:api'])->get('/me', [AuthController::class, 'me']);
```

**Step 4: Add me() method to AuthController**

Add to `app/Http/Controllers/AuthController.php`:
```php
    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     summary="Get current user info with roles and permissions",
     *     tags={"Authentication"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User info with roles and permissions",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="roles", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="permissions", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     )
     * )
     */
    public function me(Request $request)
    {
        $user = auth('api')->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name'),
                'permissions' => $user->getAllPermissions()->pluck('name'),
            ],
        ]);
    }
```

**Step 5: Run test to verify it passes**

Run:
```bash
php artisan test --filter=MeEndpointTest
```

Expected: PASS

**Step 6: Commit**

```bash
git add routes/api.php app/Http/Controllers/AuthController.php tests/Feature/Auth/MeEndpointTest.php
git commit -m "feat: add /me endpoint with roles and permissions"
```

---

## Task 9: Tạo Admin\UserController

**Files:**
- Create: `app/Http/Controllers/Admin/UserController.php`
- Create: `app/Http/Requests/Admin/StoreUserRequest.php`

**Step 1: Create StoreUserRequest**

Run:
```bash
php artisan make:request Admin/StoreUserRequest
```

Modify `app/Http/Requests/Admin/StoreUserRequest.php`:
```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['sometimes', 'in:admin,moderator,member'],
        ];
    }
}
```

**Step 2: Create Admin\UserController**

Create `app/Http/Controllers/Admin/UserController.php`:
```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->paginate(15);
        return response()->json($users);
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->validated());

        // Assign default role
        $role = $request->input('role', 'member');
        $user->assignRole($role);

        return response()->json($user->load('roles'), 201);
    }

    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|exists:roles,name'
        ]);

        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => 'Role assigned successfully',
            'user' => $user->load('roles')
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return response()->json(null, 204);
    }
}
```

**Step 3: Commit**

```bash
git add app/Http/Controllers/Admin/UserController.php app/Http/Requests/Admin/StoreUserRequest.php
git commit -m "feat: create Admin UserController"
```

---

## Task 10: Tạo Admin routes với protection

**Files:**
- Modify: `routes/api.php`

**Step 1: Add admin routes**

Add to `routes/api.php`:
```php
// Admin routes
Route::middleware(['auth:api', 'role:admin'])->prefix('admin')->group(function () {
    Route::apiResource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::post('/users/{id}/assign-role', [\App\Http\Controllers\Admin\UserController::class, 'assignRole']);
});
```

**Step 2: Write tests**

Create `tests/Feature/Admin/UserManagementTest.php`:
```php
<?php

use App\Models\User;

test('admin can access admin routes', function () {
    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this->withToken($token)
        ->getJson('/api/admin/users')
        ->assertStatus(200);
});

test('member cannot access admin routes', function () {
    $member = User::factory()->member()->create();
    $token = auth('api')->login($member);

    $this->withToken($token)
        ->getJson('/api/admin/users')
        ->assertStatus(403);
});

test('admin can create user with role', function () {
    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this->withToken($token)
        ->postJson('/api/admin/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'role' => 'moderator'
        ])
        ->assertStatus(201)
        ->assertJsonPath('user.roles.0.name', 'moderator');
});
```

**Step 3: Run tests**

Run:
```bash
php artisan test --filter=UserManagementTest
```

Expected: PASS

**Step 4: Commit**

```bash
git add routes/api.php tests/Feature/Admin/UserManagementTest.php
git commit -m "feat: add admin routes with role protection"
```

---

## Task 11: Tạo ModeratorController

**Files:**
- Create: `app/Http/Controllers/ModeratorController.php`

**Step 1: Create controller**

Run:
```bash
php artisan make:controller ModeratorController
```

Modify `app/Http/Controllers/ModeratorController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class ModeratorController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/moderate/users/{id}/lock",
     *     summary="Lock a user (Moderator+ only)",
     *     tags={"Moderation"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response="200", description="User locked"),
     *     @OA\Response(response="403", description="Forbidden - Moderator only")
     * )
     */
    public function lockUser(Request $request, User $user)
    {
        // Cannot lock admin
        if ($user->hasRole('admin')) {
            return response()->json([
                'message' => 'Không thể khóa tài khoản Admin'
            ], 403);
        }

        // Cannot lock yourself
        if ($user->id === auth('api')->id()) {
            return response()->json([
                'message' => 'Không thể khóa chính mình'
            ], 403);
        }

        $user->update(['is_locked' => true]);

        return response()->json([
            'message' => "Đã khóa user {$user->name}"
        ]);
    }
}
```

**Step 2: Add migration for is_locked column**

Run:
```bash
php artisan make:migration add_is_locked_to_users_table --table=users
```

Edit the migration file:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_locked');
        });
    }
};
```

**Step 3: Run migration**

Run:
```bash
php artisan migrate
```

**Step 4: Commit**

```bash
git add app/Http/Controllers/ModeratorController.php database/migrations
git commit -m "feat: add ModeratorController with lock user action"
```

---

## Task 12: Thêm moderator routes

**Files:**
- Modify: `routes/api.php`

**Step 1: Add moderator routes**

Add to `routes/api.php`:
```php
// Moderator routes
Route::middleware(['auth:api', 'permission:lock users'])
    ->post('/moderate/users/{id}/lock', [\App\Http\Controllers\ModeratorController::class, 'lockUser']);
```

**Step 2: Write tests**

Create `tests/Feature/ModeratorTest.php`:
```php
<?php

use App\Models\User;

test('moderator can lock users', function () {
    $moderator = User::factory()->moderator()->create();
    $targetUser = User::factory()->create();
    $token = auth('api')->login($moderator);

    $this->withToken($token)
        ->postJson("/api/moderate/users/{$targetUser->id}/lock")
        ->assertStatus(200)
        ->assertJsonPath('message', "Đã khóa user {$targetUser->name}");
});

test('member cannot lock users', function () {
    $member = User::factory()->member()->create();
    $targetUser = User::factory()->create();
    $token = auth('api')->login($member);

    $this->withToken($token)
        ->postJson("/api/moderate/users/{$targetUser->id}/lock")
        ->assertStatus(403);
});

test('moderator cannot lock admin', function () {
    $moderator = User::factory()->moderator()->create();
    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($moderator);

    $this->withToken($token)
        ->postJson("/api/moderate/users/{$admin->id}/lock")
        ->assertStatus(403)
        ->assertJsonPath('message', 'Không thể khóa tài khoản Admin');
});
```

**Step 3: Run tests**

Run:
```bash
php artisan test --filter=ModeratorTest
```

Expected: PASS

**Step 4: Commit**

```bash
git add routes/api.php tests/Feature/ModeratorTest.php
git commit -m "feat: add moderator routes with permission protection"
```

---

## Task 13: Update Swagger documentation

**Files:**
- Modify: `app/Http/Controllers/AuthController.php`
- Modify: Existing controllers to add Swagger annotations

**Step 1: Add security scheme definition**

Update Swagger configuration (check existing docs/swagger/swagger.yaml or similar):

Add security scheme:
```yaml
securitySchemes:
  bearerAuth:
    type: http
    scheme: bearer
    bearerFormat: JWT
```

**Step 2: Verify all protected endpoints have security annotation**

Ensure all new endpoints have:
```php
*     security={{"bearerAuth":{}}}
```

**Step 3: Regenerate Swagger docs**

Run:
```bash
php artisan l5-swagger:generate
```

**Step 4: Commit**

```bash
git add .
git commit -m "docs: update Swagger documentation for RBAC endpoints"
```

---

## Task 14: Final integration tests

**Files:**
- Create: `tests/Feature/RBACIntegrationTest.php`

**Step 1: Create comprehensive integration test**

Create `tests/Feature/RBACIntegrationTest.php`:
```php
<?php

use App\Models\User;

test('complete rbac flow works correctly', function () {
    // 1. Admin can do everything
    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this->withToken($token)
        ->getJson('/api/admin/users')
        ->assertStatus(200);

    // 2. Moderator can moderate but not admin
    $moderator = User::factory()->moderator()->create();
    $modToken = auth('api')->login($moderator);

    $this->withToken($modToken)
        ->getJson('/api/admin/users')
        ->assertStatus(403);

    $member = User::factory()->create();
    $this->withToken($modToken)
        ->postJson("/api/moderate/users/{$member->id}/lock")
        ->assertStatus(200);

    // 3. Member has basic permissions
    $member = User::factory()->member()->create();
    $memberToken = auth('api')->login($member);

    $this->withToken($memberToken)
        ->getJson('/api/auth/me')
        ->assertStatus(200)
        ->assertJsonPath('user.roles', ['member']);
});

test('permissions are correctly cached and cleared', function () {
    $user = User::factory()->member()->create();

    expect($user->hasPermissionTo('create posts'))->toBeTrue();
    expect($user->hasPermissionTo('manage users'))->toBeFalse();

    // Change role
    $user->syncRoles(['admin']);

    // Clear cache to see changes
    \Illuminate\Support\Facades\Cache::forget('spatie.permission.cache');

    expect($user->fresh()->hasPermissionTo('manage users'))->toBeTrue();
});
```

**Step 2: Run all tests**

Run:
```bash
php artisan test
```

Expected: All tests PASS

**Step 3: Run Pint**

Run:
```bash
vendor/bin/pint --dirty
```

**Step 4: Final commit**

```bash
git add tests/Feature/RBACIntegrationTest.php
git commit -m "test: add comprehensive RBAC integration tests"
```

---

## Task 15: Update existing users to have default role

**Files:**
- Create: `database/migrations/XXXX_XX_XX_XXXXXX_assign_member_role_to_existing_users.php`

**Step 1: Create migration**

Run:
```bash
php artisan make:migration assign_member_role_to_existing_users
```

**Step 2: Write migration**

Edit the migration file:
```php
<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Assign 'member' role to all existing users without roles
        $memberRole = Role::where('name', 'member')
            ->where('guard_name', 'api')
            ->first();

        if ($memberRole) {
            User::whereDoesntHave('roles')
                ->get()
                ->each(fn ($user) => $user->assignRole('member'));
        }
    }

    public function down(): void
    {
        // Remove member role from users who were auto-assigned
        // This is a no-op as we can't track which were auto-assigned
    }
};
```

**Step 3: Run migration**

Run:
```bash
php artisan migrate
```

**Step 4: Verify**

Run:
```bash
php artisan tinker --execute="echo 'Users with role: ' . \App\Models\User::whereHas('roles')->count();"
```

**Step 5: Commit**

```bash
git add database/migrations
git commit -m "feat: assign member role to existing users"
```

---

## Task 16: Documentation & cleanup

**Files:**
- Update: `README.md` or existing documentation

**Step 1: Update documentation**

Add to main README or docs:
```markdown
## Role-Based Access Control

### Roles
- **Guest**: Unauthenticated users
- **Member**: Can create posts, comment
- **Moderator**: Can delete posts, lock users
- **Admin**: Full access, manage users and roles

### API Usage

Get current user info:
\`\`\`bash
GET /api/auth/me
Authorization: Bearer {token}
\`\`\`

Create user (Admin only):
\`\`\`bash
POST /api/admin/users
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "name": "User Name",
  "email": "user@example.com",
  "password": "password123",
  "role": "member"
}
\`\`\`
```

**Step 2: Run final test suite**

Run:
```bash
php artisan test --compact
```

**Step 3: Final commit**

```bash
git add README.md docs/
git commit -m "docs: add RBAC usage documentation"
```

---

## Testing & Verification Checklist

After completing all tasks:

- [ ] All unit tests pass
- [ ] All feature tests pass
- [ ] Swagger docs generated correctly
- [ ] Existing users have 'member' role
- [ ] New users get 'member' role by default
- [ ] Admin cannot be locked by moderator
- [ ] Members cannot access admin routes
- [ ] Permission middleware works correctly
- [ ] Code formatted with Pint

## Rollback Plan

If issues arise:
```bash
# Rollback migrations
php artisan migrate:rollback

# Remove package
composer remove spatie/laravel-permission

# Restore previous commit
git revert HEAD~1
```

---

**Implementation Plan Version:** 1.0
**Created:** 2026-02-13
**Total Estimated Time:** 2-3 hours
