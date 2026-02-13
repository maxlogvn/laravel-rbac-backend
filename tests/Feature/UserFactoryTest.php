<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    // Clean up and seed roles before each test
    User::query()->delete();

    $seeder = new RolePermissionSeeder;
    $seeder->run();
});

test('user factory creates user with default attributes', function () {
    $user = User::factory()->create();

    expect($user)
        ->toBeInstanceOf(User::class)
        ->and($user->name)->not->toBeEmpty()
        ->and($user->email)->not->toBeEmpty()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->remember_token)->not->toBeEmpty();
});

test('user factory admin state assigns admin role', function () {
    $user = User::factory()->admin()->create();

    expect($user)
        ->toBeInstanceOf(User::class)
        ->and($user->hasRole('admin'))->toBeTrue()
        ->and($user->hasRole('moderator'))->toBeFalse()
        ->and($user->hasRole('member'))->toBeFalse();
});

test('user factory moderator state assigns moderator role', function () {
    $user = User::factory()->moderator()->create();

    expect($user)
        ->toBeInstanceOf(User::class)
        ->and($user->hasRole('moderator'))->toBeTrue()
        ->and($user->hasRole('admin'))->toBeFalse()
        ->and($user->hasRole('member'))->toBeFalse();
});

test('user factory member state assigns member role', function () {
    $user = User::factory()->member()->create();

    expect($user)
        ->toBeInstanceOf(User::class)
        ->and($user->hasRole('member'))->toBeTrue()
        ->and($user->hasRole('admin'))->toBeFalse()
        ->and($user->hasRole('moderator'))->toBeFalse();
});

test('user factory unverified state sets email_verified_at to null', function () {
    $user = User::factory()->unverified()->create();

    expect($user)
        ->toBeInstanceOf(User::class)
        ->and($user->email_verified_at)->toBeNull();
});

test('user factory can combine role states with other states', function () {
    $user = User::factory()->admin()->unverified()->create();

    expect($user)
        ->toBeInstanceOf(User::class)
        ->and($user->hasRole('admin'))->toBeTrue()
        ->and($user->email_verified_at)->toBeNull();
});

test('user factory creates multiple users with same role state', function () {
    $users = User::factory()->moderator()->count(3)->create();

    expect($users)->toHaveCount(3);

    foreach ($users as $user) {
        expect($user->hasRole('moderator'))->toBeTrue();
    }
});

test('user factory creates unique emails', function () {
    $users = User::factory()->count(10)->create();

    expect($users)->toHaveCount(10);

    $emails = $users->pluck('email');
    expect($emails)->toHaveCount(10); // Unique emails
});
