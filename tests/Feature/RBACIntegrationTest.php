<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $seeder = new RolePermissionSeeder;
    $seeder->run();
});

test('complete rbac flow works correctly', function () {
    // 1. Admin can do everything
    $admin = User::factory()->admin()->create();
    $token = auth('api')->login($admin);

    $this->actingAs($admin, 'api')
        ->getJson('/api/admin/users')
        ->assertStatus(200);

    // 2. Moderator can moderate but not admin
    $moderator = User::factory()->moderator()->create();
    $modToken = auth('api')->login($moderator);

    $this->actingAs($moderator, 'api')
        ->getJson('/api/admin/users')
        ->assertStatus(403);

    $targetMember = User::factory()->member()->create();
    $lockResponse = $this->actingAs($moderator, 'api')
        ->postJson("/api/moderation/users/{$targetMember->id}/lock", [
            'reason' => 'Test lock',
        ]);

    // Debug: dump response to see actual status
    if ($lockResponse->status() === 404) {
        dump("Route not found! User ID: {$targetMember->id}");
        dump('Moderator permissions: '.implode(', ', $moderator->getAllPermissions()->pluck('name')->toArray()));
        dump("Response status: {$lockResponse->status()}");
    }

    $lockResponse->assertStatus(200);

    // 3. Member has basic permissions
    $member = User::factory()->member()->create();
    $memberToken = auth('api')->login($member);

    $this->actingAs($member, 'api')
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
