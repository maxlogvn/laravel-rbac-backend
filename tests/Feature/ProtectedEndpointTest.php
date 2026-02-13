<?php

use App\Models\User;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

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
    $token = JWTAuth::fromUser($user);

    $this->withToken($token)
        ->getJson('/api/protected')
        ->assertStatus(200)
        ->assertJson([
            'message' => 'Protected data',
            'user_id' => $user->id,
        ]);
});
