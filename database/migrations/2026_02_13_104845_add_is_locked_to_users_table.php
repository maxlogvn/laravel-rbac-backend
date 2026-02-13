<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_locked')->default(false)->after('email_verified_at');
            $table->timestamp('locked_at')->nullable()->after('is_locked');
            $table->string('lock_reason')->nullable()->after('locked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'lock_reason')) {
                $table->dropColumn('lock_reason');
            }
            if (Schema::hasColumn('users', 'locked_at')) {
                $table->dropColumn('locked_at');
            }
            if (Schema::hasColumn('users', 'is_locked')) {
                $table->dropColumn('is_locked');
            }
        });
    }
};
