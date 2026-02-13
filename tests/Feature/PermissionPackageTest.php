<?php

use Illuminate\Support\Facades\Config;

test('spatie permission package is installed', function () {
    $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);

    expect($composerJson['require'])->toHaveKey('spatie/laravel-permission')
        ->and($composerJson['require']['spatie/laravel-permission'])->not->toBeEmpty();
});

test('permission config file exists', function () {
    expect(Config::get('permission'))->not->toBeNull()
        ->and(Config::get('permission.models.permission'))->toBe('Spatie\Permission\Models\Permission')
        ->and(Config::get('permission.models.role'))->toBe('Spatie\Permission\Models\Role');
});

test('permission service provider is registered', function () {
    $app = app();

    expect($app->getProviders(\Spatie\Permission\PermissionServiceProvider::class))->not->toBeEmpty();
});

test('permission facade is accessible', function () {
    expect(class_exists(\Spatie\Permission\PermissionRegistrar::class))->toBeTrue();
});

test('package models are accessible', function () {
    expect(class_exists(\Spatie\Permission\Models\Permission::class))->toBeTrue()
        ->and(class_exists(\Spatie\Permission\Models\Role::class))->toBeTrue();
});
