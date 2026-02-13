# Hệ thống Role-Based Access Control (RBAC)

**Ngày tạo:** 2026-02-13
**Tác giả:** Claude Code
**Trạng thái:** Approved

---

## 1. Tổng quan

Hệ thống phân quyền role-based với permission granularity cho phép quản lý quyền hạn chi tiết cho người dùng. Hệ thống hỗ trợ 4 levels access: Guest (chưa đăng nhập), Member, Moderator, và Admin.

### 1.1 Mục tiêu

- Phân quyền user thành admin, moderator, member, guest
- Quản lý permissions chi tiết với spatie/laravel-permission package
- Middleware-based authorization cho API endpoints
- Hỗ trợ JWT authentication với guard `api`

### 1.2 Tech Stack

- **Package:** spatie/laravel-permission ^6.0
- **Authentication:** JWT (tymon/jwt-auth)
- **Guard:** api
- **Laravel Version:** 12

---

## 2. Database Schema

Package sẽ tự động tạo 5 tables:

### 2.1 roles
Lưu trữ các vai trò người dùng.

| Column | Type | Description |
|---------|------|-------------|
| id | BIGINT UNSIGNED PK | Primary key |
| name | VARCHAR(255) UNIQUE | Tên role: admin, moderator, member |
| guard_name | VARCHAR(255) | Guard name: api |
| created_at | TIMESTAMP | Thời gian tạo |
| updated_at | TIMESTAMP | Thời gian cập nhật |

### 2.2 permissions
Lưu trữ các quyền hạn cụ thể.

| Column | Type | Description |
|---------|------|-------------|
| id | BIGINT UNSIGNED PK | Primary key |
| name | VARCHAR(255) UNIQUE | Tên permission: manage users, delete posts, etc. |
| guard_name | VARCHAR(255) | Guard name: api |
| created_at | TIMESTAMP | Thời gian tạo |
| updated_at | TIMESTAMP | Thời gian cập nhật |

### 2.3 model_has_permissions
Many-to-Many relationship giữa User và Permission.

| Column | Type | Description |
|---------|------|-------------|
| permission_id | BIGINT UNSIGNED FK | Reference to permissions.id |
| model_type | VARCHAR(255) | App\Models\User |
| model_id | BIGINT UNSIGNED | User ID |

### 2.4 model_has_roles
Many-to-Many relationship giữa User và Role.

| Column | Type | Description |
|---------|------|-------------|
| role_id | BIGINT UNSIGNED FK | Reference to roles.id |
| model_type | VARCHAR(255) | App\Models\User |
| model_id | BIGINT UNSIGNED | User ID |

### 2.5 role_has_permissions
Many-to-Many relationship giữa Role và Permission.

| Column | Type | Description |
|---------|------|-------------|
| permission_id | BIGINT UNSIGNED FK | Reference to permissions.id |
| role_id | BIGINT UNSIGNED FK | Reference to roles.id |

---

## 3. Roles & Permissions Matrix

### 3.1 Role Definitions

| Role | Mô tả |
|------|---------|
| **Guest** | User chưa đăng nhập - chỉ xem public content |
| **Member** | User đã đăng ký - tạo bài viết, bình luận |
| **Moderator** | Moderation content - xóa bài viết, lock user |
| **Admin** | Toàn quyền - quản lý users, roles, permissions |

### 3.2 Permissions Mapping

| Permission | Admin | Moderator | Member | Guest |
|-------------|:------:|:----------:|:-------:|:-----:|
| manage users | ✅ | ❌ | ❌ | ❌ |
| manage roles | ✅ | ❌ | ❌ | ❌ |
| delete posts | ✅ | ✅ | ❌ | ❌ |
| lock users | ✅ | ✅ | ❌ | ❌ |
| create posts | ✅ | ✅ | ✅ | ❌ |
| edit own posts | ✅ | ✅ | ✅ | ❌ |
| comment | ✅ | ✅ | ✅ | ❌ |

---

## 4. Implementation Details

### 4.1 User Model

Thêm `HasRoles` trait vào User model:

```php
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, HasRoles;
    // ...
}
```

### 4.2 Middleware Configuration

Register middleware trong `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
    ]);
})
```

### 4.3 Route Protection Examples

```php
// Single permission
Route::middleware(['auth:api', 'permission:create posts'])
    ->post('/posts', [PostController::class, 'store']);

// Single role
Route::middleware(['auth:api', 'role:admin'])
    ->delete('/admin/users/{id}', [Admin\UserController::class, 'destroy']);

// Multiple roles (cần ít nhất 1)
Route::middleware(['auth:api', 'role:admin|moderator'])
    ->post('/moderate/posts/{id}', [ModeratorController::class, 'approve']);
```

### 4.4 Error Response Format

Standardized 403 response:

```json
{
  "message": "Bạn không có quyền thực hiện hành động này",
  "error": "permission_denied",
  "required_permission": "manage users",
  "user_roles": ["member"]
}
```

---

## 5. Controllers

### 5.1 AuthController - Thêm role info

```php
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

### 5.2 Admin Controllers

- `Admin\UserController` - Quản lý users (CRUD)
- `Admin\RoleController` - Quản lý roles & permissions
- `ModeratorController` - Moderation actions (lock users, approve posts)

---

## 6. Database Seeding

Tạo `RolePermissionSeeder` để khởi tạo:

```php
$admin = Role::create(['name' => 'admin', 'guard_name' => 'api']);
$moderator = Role::create(['name' => 'moderator', 'guard_name' => 'api']);
$member = Role::create(['name' => 'member', 'guard_name' => 'api']);

// Permissions và gán vào roles
$admin->givePermissionTo(Permission::all());
$moderator->givePermissionTo(['delete posts', 'lock users', 'create posts', 'edit own posts', 'comment']);
$member->givePermissionTo(['create posts', 'edit own posts', 'comment']);
```

---

## 7. Testing Strategy

### 7.1 Unit Tests

- Test role assignment
- Test permission checking
- Test role-permission inheritance

### 7.2 Feature Tests

- Guest không thể truy cập protected routes
- Member có quyền của member
- Moderator có quyền member + moderator permissions
- Admin có toàn quyền
- Ownership checks (member chỉ edit bài của mình)
- Moderator không thể lock admin

### 7.3 Test Factories

```php
User::factory()->admin()->create();
User::factory()->moderator()->create();
User::factory()->withPermission('edit posts')->create();
```

---

## 8. Swagger Documentation

Thêm annotations cho role-based endpoints:

```php
/**
 * @OA\Post(
 *     path="/api/admin/users",
 *     summary="Create new user (Admin only)",
 *     tags={"Users", "Admin"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(response="201", description="User created"),
 *     @OA\Response(response="403", description="Forbidden - Admin only")
 * )
 */
```

---

## 9. Migration & Rollout

### 9.1 Installation Steps

1. Install package: `composer require spatie/laravel-permission`
2. Publish config: `php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"`
3. Run migrations: `php artisan migrate`
4. Run seeder: `php artisan db:seed --class=RolePermissionSeeder`

### 9.2 Backwards Compatibility

- Existing users không có role = chưa được assign permission
- Migrate existing users → assign role 'member' mặc định

---

## 10. Security Considerations

### 10.1 Important Notes

- **ALWAYS** specify `'api'` guard khi check permissions với JWT
- Clear permission cache sau khi update: `php artisan cache:forget spatie.permission.cache`
- Auth middleware phải come TRƯỚC permission middleware

### 10.2 Best Practices

- Never trust client-side role claims
- Always verify permissions server-side
- Use direct permissions sparingly (prefer role-based)
- Log permission denials for security monitoring

---

## 11. Future Enhancements

- Custom permissions per resource (edit post X only)
- Time-based permissions (temporary moderator)
- Permission groups/categories
- Audit log for permission changes
- Role hierarchy with inheritance

---

**Document Version:** 1.0
**Last Updated:** 2026-02-13
