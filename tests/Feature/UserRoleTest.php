<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Clear cache
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Create roles
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
    Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'api']);
    Role::firstOrCreate(['name' => 'member', 'guard_name' => 'api']);

    // Create permissions
    Permission::firstOrCreate(['name' => 'manage users', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'create posts', 'guard_name' => 'api']);
    Permission::firstOrCreate(['name' => 'delete posts', 'guard_name' => 'api']);
});

test('user can be assigned role', function () {
    $user = User::factory()->create();

    $user->assignRole('admin');

    expect($user->roles->first()->name)->toBe('admin');
});

test('user can have multiple roles', function () {
    $user = User::factory()->create();

    $user->assignRole('member');
    $user->assignRole('moderator');

    expect($user->roles->count())->toBe(2);
});

test('user can check if has role', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->hasRole('member'))->toBeFalse();
});

test('user can sync roles', function () {
    $user = User::factory()->create();

    $user->assignRole('member');
    expect($user->hasRole('member'))->toBeTrue();

    $user->syncRoles(['admin']);
    expect($user->hasRole('admin'))->toBeTrue();
    expect($user->hasRole('member'))->toBeFalse();
});

test('user can be assigned permission', function () {
    $user = User::factory()->create();

    $user->givePermissionTo('create posts');

    expect($user->permissions->first()->name)->toBe('create posts');
});

test('user can check if has permission', function () {
    $user = User::factory()->create();

    $user->givePermissionTo('create posts');

    expect($user->hasPermissionTo('create posts'))->toBeTrue();
    expect($user->hasPermissionTo('delete posts'))->toBeFalse();
});

test('user can have permissions through role', function () {
    $user = User::factory()->create();

    // Get existing admin role (created in beforeEach)
    $adminRole = \Spatie\Permission\Models\Role::where('name', 'admin')->where('guard_name', 'api')->first();
    $adminRole->givePermissionTo('manage users');

    // Assign role to user
    $user->assignRole('admin');

    // User should have permission through role
    expect($user->hasPermissionTo('manage users'))->toBeTrue();
});

test('user can remove role', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    expect($user->hasRole('admin'))->toBeTrue();

    $user->removeRole('admin');

    expect($user->hasRole('admin'))->toBeFalse();
});
