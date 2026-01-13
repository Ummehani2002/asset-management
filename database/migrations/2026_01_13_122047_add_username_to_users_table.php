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
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->unique()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // SQLite has limitations with dropping columns that have unique indexes
        // For SQLite, we need to handle this differently
        $driver = Schema::getConnection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support dropping columns easily, especially with indexes
            // We'll skip the rollback for SQLite to avoid errors
            // The column will remain but won't cause issues
            return;
        }
        
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                // For other databases, try to drop unique constraint first
                try {
                    $table->dropUnique(['username']);
                } catch (\Exception $e) {
                    // Index might not exist or have different name, continue
                }
                $table->dropColumn('username');
            }
        });
    }
};
