<?php

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Clean up before each test
    Permission::query()->delete();
    Role::query()->delete();
});

test('permission_seeder_creates_all_permissions', function () {
    $seeder = new PermissionSeeder;
    $seeder->run();

    $expectedPermissions = [
        'create users',
        'edit users',
        'delete users',
        'view users',
        'create posts',
        'edit posts',
        'delete posts',
        'view posts',
        'moderate comments',
        'moderate posts',
        'manage roles',
        'manage permissions',
        'view analytics',
        'manage settings',
    ];

    foreach ($expectedPermissions as $permission) {
        expect(Permission::where('name', $permission)->where('guard_name', 'api')->exists())
            ->toBeTrue();
    }

    expect(Permission::count())->toBe(count($expectedPermissions));
});

test('role_seeder_creates_all_roles', function () {
    $seeder = new RoleSeeder;
    $seeder->run();

    $expectedRoles = ['admin', 'moderator', 'member'];

    foreach ($expectedRoles as $role) {
        expect(Role::where('name', $role)->where('guard_name', 'api')->exists())
            ->toBeTrue();
    }

    expect(Role::count())->toBe(count($expectedRoles));
});

test('role_permission_seeder_assigns_permissions_correctly', function () {
    // Run RolePermissionSeeder independently (it creates all permissions and roles)
    $rolePermissionSeeder = new RolePermissionSeeder;
    $rolePermissionSeeder->run();

    $admin = Role::findByName('admin', 'api');
    $moderator = Role::findByName('moderator', 'api');
    $member = Role::findByName('member', 'api');

    // Admin should have all permissions (7 per spec)
    expect($admin->permissions->count())->toBe(7);

    // Moderator should have specific permissions (5 per spec)
    expect($moderator->permissions->count())->toBe(5);
    expect($moderator->hasPermissionTo('delete posts'))->toBeTrue();
    expect($moderator->hasPermissionTo('lock users'))->toBeTrue();
    expect($moderator->hasPermissionTo('create posts'))->toBeTrue();
    expect($moderator->hasPermissionTo('edit own posts'))->toBeTrue();
    expect($moderator->hasPermissionTo('comment'))->toBeTrue();
    expect($moderator->hasPermissionTo('manage users'))->toBeFalse();

    // Member should have basic permissions (3 per spec)
    expect($member->permissions->count())->toBe(3);
    expect($member->hasPermissionTo('create posts'))->toBeTrue();
    expect($member->hasPermissionTo('edit own posts'))->toBeTrue();
    expect($member->hasPermissionTo('comment'))->toBeTrue();
    expect($member->hasPermissionTo('manage users'))->toBeFalse();
    expect($member->hasPermissionTo('delete posts'))->toBeFalse();
});

test('seeders_are_idempotent', function () {
    $seeder = new PermissionSeeder;
    $seeder->run();

    $countBefore = Permission::count();

    // Run again
    $seeder->run();

    expect(Permission::count())->toBe($countBefore);
});

test('database_seeder_calls_all_seeders', function () {
    $this->seed(Database\Seeders\DatabaseSeeder::class);

    expect(Role::count())->toBe(3);
    // PermissionSeeder creates 14, RolePermissionSeeder creates 7 more = 18 (idempotent via firstOrCreate)
    expect(Permission::count())->toBe(18);

    $admin = Role::findByName('admin', 'api');
    // Admin gets all permissions via syncPermissions()
    expect($admin->permissions->count())->toBe(18);

    $moderator = Role::findByName('moderator', 'api');
    // Moderator gets 5 permissions from RolePermissionSeeder
    expect($moderator->permissions->count())->toBe(5);

    $member = Role::findByName('member', 'api');
    // Member gets 3 permissions from RolePermissionSeeder
    expect($member->permissions->count())->toBe(3);
});
