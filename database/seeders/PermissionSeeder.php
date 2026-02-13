<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // User management permissions
            'create users',
            'edit users',
            'delete users',
            'view users',

            // Content management permissions
            'create posts',
            'edit posts',
            'delete posts',
            'view posts',

            // Moderation permissions
            'moderate comments',
            'moderate posts',

            // Admin permissions
            'manage roles',
            'manage permissions',
            'view analytics',
            'manage settings',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'api']
            );
        }

        if ($this->command) {
            $this->command->info('Permissions seeded successfully.');
        }
    }
}
