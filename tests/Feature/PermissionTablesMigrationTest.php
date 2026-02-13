<?php

use Illuminate\Support\Facades\Schema;

test('permissions table exists', function () {
    expect(Schema::hasTable('permissions'))->toBeTrue();
});

test('roles table exists', function () {
    expect(Schema::hasTable('roles'))->toBeTrue();
});

test('model_has_permissions table exists', function () {
    expect(Schema::hasTable('model_has_permissions'))->toBeTrue();
});

test('model_has_roles table exists', function () {
    expect(Schema::hasTable('model_has_roles'))->toBeTrue();
});

test('role_has_permissions table exists', function () {
    expect(Schema::hasTable('role_has_permissions'))->toBeTrue();
});

test('permissions table has required columns', function () {
    expect(Schema::hasColumns('permissions', ['id', 'name', 'guard_name', 'created_at', 'updated_at']))->toBeTrue();
});

test('roles table has required columns', function () {
    expect(Schema::hasColumns('roles', ['id', 'name', 'guard_name', 'created_at', 'updated_at']))->toBeTrue();
});

test('model_has_permissions table has required columns', function () {
    expect(Schema::hasColumns('model_has_permissions', ['permission_id', 'model_type', 'model_id']))->toBeTrue();
});

test('model_has_roles table has required columns', function () {
    expect(Schema::hasColumns('model_has_roles', ['role_id', 'model_type', 'model_id']))->toBeTrue();
});

test('role_has_permissions table has required columns', function () {
    expect(Schema::hasColumns('role_has_permissions', ['permission_id', 'role_id']))->toBeTrue();
});
