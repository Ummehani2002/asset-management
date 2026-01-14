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
        Schema::table('category_features', function (Blueprint $table) {
            $table->json('sub_fields')->nullable()->after('feature_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if table exists before trying to modify it
        if (!Schema::hasTable('category_features')) {
            return;
        }
        
        Schema::table('category_features', function (Blueprint $table) {
            // Only drop column if it exists
            if (Schema::hasColumn('category_features', 'sub_fields')) {
            $table->dropColumn('sub_fields');
            }
        });
    }
};
