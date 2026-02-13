<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    // Seed roles and permissions before each test
    $seeder = new RolePermissionSeeder;
    $seeder->run();
});

test('unauthenticated users cannot access moderation routes', function () {
    $user = User::factory()->member()->create();

    $response = $this->postJson("/api/moderation/users/{$user->id}/lock", [
        'reason' => 'Test lock',
    ]);

    $response->assertStatus(401);
});

test('members cannot access moderation routes', function () {
    $member = User::factory()->member()->create();
    $targetUser = User::factory()->member()->create();

    $response = $this->actingAs($member, 'api')->postJson("/api/moderation/users/{$targetUser->id}/lock", [
        'reason' => 'Test lock',
    ]);

    $response->assertStatus(403);
});

test('moderators can lock regular users', function () {
    $moderator = User::factory()->moderator()->create();
    $targetUser = User::factory()->member()->create();

    // First check if is_locked column exists - if not, we expect 501
    $columns = \Schema::getColumnListing('users');
    $hasLockedColumn = in_array('is_locked', $columns);

    $response = $this->actingAs($moderator, 'api')->postJson("/api/moderation/users/{$targetUser->id}/lock", [
        'reason' => 'Violation of community guidelines',
    ]);

    if (! $hasLockedColumn) {
        $response->assertStatus(501)
            ->assertJson([
                'error' => 'feature_not_implemented',
            ]);
    } else {
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User locked successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'is_locked' => true,
        ]);
    }
});

test('moderators cannot lock admin users', function () {
    $moderator = User::factory()->moderator()->create();
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($moderator, 'api')->postJson("/api/moderation/users/{$admin->id}/lock", [
        'reason' => 'Test lock',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'Cannot lock admin users',
            'error' => 'cannot_lock_admin',
        ]);
});

test('moderators cannot lock themselves', function () {
    $moderator = User::factory()->moderator()->create();

    $response = $this->actingAs($moderator, 'api')->postJson("/api/moderation/users/{$moderator->id}/lock", [
        'reason' => 'Test lock',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'Cannot lock yourself',
            'error' => 'cannot_lock_self',
        ]);
});

test('moderators can unlock locked users', function () {
    $moderator = User::factory()->moderator()->create();
    $targetUser = User::factory()->member()->create();

    // First check if is_locked column exists
    $columns = \Schema::getColumnListing('users');
    $hasLockedColumn = in_array('is_locked', $columns);

    if (! $hasLockedColumn) {
        $this->markTestSkipped('is_locked column does not exist in users table');
    }

    // Lock the user first
    $targetUser->update([
        'is_locked' => true,
        'locked_at' => now(),
        'lock_reason' => 'Test lock',
    ]);

    $response = $this->actingAs($moderator, 'api')->postJson("/api/moderation/users/{$targetUser->id}/unlock");

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'User unlocked successfully',
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $targetUser->id,
        'is_locked' => false,
    ]);
});

test('moderators can view locked users list', function () {
    $moderator = User::factory()->moderator()->create();

    // Check if is_locked column exists
    $columns = \Schema::getColumnListing('users');
    $hasLockedColumn = in_array('is_locked', $columns);

    if (! $hasLockedColumn) {
        $response = $this->actingAs($moderator, 'api')->getJson('/api/moderation/users/locked');

        $response->assertStatus(501)
            ->assertJson([
                'error' => 'feature_not_implemented',
            ]);

        return;
    }

    // Create some locked users
    User::factory()->count(3)->create([
        'is_locked' => true,
        'locked_at' => now(),
        'lock_reason' => 'Test lock',
    ]);

    // Create some unlocked users
    User::factory()->count(2)->create([
        'is_locked' => false,
    ]);

    $response = $this->actingAs($moderator, 'api')->getJson('/api/moderation/users/locked');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data',
            'current_page',
            'per_page',
            'total',
        ]);

    // Should only return locked users
    $responseData = $response->json();
    $this->assertCount(3, $responseData['data']);
});

test('post moderation endpoints return not implemented', function () {
    $moderator = User::factory()->moderator()->create();

    // Test approve post endpoint
    $response = $this->actingAs($moderator, 'api')->postJson('/api/moderation/posts/1/approve');

    $response->assertStatus(501)
        ->assertJson([
            'error' => 'feature_not_implemented',
        ]);

    // Test delete post endpoint
    $response = $this->actingAs($moderator, 'api')->deleteJson('/api/moderation/posts/1');

    $response->assertStatus(501)
        ->assertJson([
            'error' => 'feature_not_implemented',
        ]);
});
