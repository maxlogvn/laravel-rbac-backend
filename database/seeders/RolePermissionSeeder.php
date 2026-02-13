<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'manage users',
            'manage roles',
            'delete posts',
            'lock users',
            'create posts',
            'edit own posts',
            'comment',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api',
            ]);
        }

        // Create Roles and assign Permissions
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $moderator = Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'api']);
        $member = Role::firstOrCreate(['name' => 'member', 'guard_name' => 'api']);

        // Admin gets all permissions
        $admin->syncPermissions(Permission::all());

        // Moderator permissions
        $moderator->syncPermissions([
            'delete posts',
            'lock users',
            'create posts',
            'edit own posts',
            'comment',
        ]);

        // Member permissions
        $member->syncPermissions([
            'create posts',
            'edit own posts',
            'comment',
        ]);

        if ($this->command) {
            $this->command->info('Role permissions seeded successfully.');
            $this->command->info('Admin has all '.Permission::count().' permissions.');
            $this->command->info('Moderator has 5 permissions.');
            $this->command->info('Member has 3 permissions.');
        }
    }
}
