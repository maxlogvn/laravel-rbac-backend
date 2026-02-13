<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

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
