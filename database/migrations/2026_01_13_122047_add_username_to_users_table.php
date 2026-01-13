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
                // First add the column as nullable
                $table->string('username')->nullable()->after('name');
            }
        });
        
        // Update existing users to have unique usernames based on their email or id
        \DB::table('users')->whereNull('username')->orWhere('username', '')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $username = $user->email ? explode('@', $user->email)[0] : 'user_' . $user->id;
                $counter = 1;
                $originalUsername = $username;
                
                // Ensure uniqueness
                while (\DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                    $username = $originalUsername . '_' . $counter;
                    $counter++;
                }
                
                \DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            }
        });
        
        // Now make it unique and not nullable
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                // MySQL automatically drops indexes when dropping a column
                // So we can just drop the column directly
                $table->dropColumn('username');
            }
        });
    }
};
