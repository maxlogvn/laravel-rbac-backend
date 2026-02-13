<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    // Seed roles and permissions before each test
    $seeder = new RolePermissionSeeder;
    $seeder->run();
});

test('unauthenticated users cannot access admin routes', function () {
    $response = $this->getJson('/api/admin/users');

    $response->assertStatus(401);
});

test('non_admin users cannot access admin routes', function () {
    $user = User::factory()->member()->create();

    $response = $this->actingAs($user, 'api')->getJson('/api/admin/users');

    $response->assertStatus(403);
});

test('admin users can list users', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(3)->create();

    $response = $this->actingAs($admin, 'api')->getJson('/api/admin/users');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'current_page',
            'per_page',
            'total',
        ]);
});

test('admin users can create new users', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin, 'api')->postJson('/api/admin/users', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'role' => 'member',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);
});

test('admin users can assign roles to users', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->member()->create();

    $response = $this->actingAs($admin, 'api')->postJson("/api/admin/users/{$user->id}/assign-role", [
        'role' => 'moderator',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Role assigned successfully',
        ]);

    $this->assertTrue($user->fresh()->hasRole('moderator'));
});

test('admin users can delete users', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $response = $this->actingAs($admin, 'api')->deleteJson("/api/admin/users/{$user->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});
