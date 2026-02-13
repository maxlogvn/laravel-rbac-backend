<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Assign 'member' role to all existing users without roles
        $memberRole = Role::where('name', 'member')
            ->where('guard_name', 'api')
            ->first();

        if ($memberRole) {
            User::whereDoesntHave('roles')
                ->get()
                ->each(fn ($user) => $user->assignRole('member'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove member role from all users (for rollback)
        // Note: This is a simple rollback - in production you might want to be more careful
        User::whereHas('roles')
            ->get()
            ->each(fn ($user) => $user->removeRole('member'));
    }
};
