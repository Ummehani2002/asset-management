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
        // Check if table exists
        if (!Schema::hasTable('entity_budgets')) {
            return; // Table doesn't exist, skip
        }

        // Check if category column exists
        if (Schema::hasColumn('entity_budgets', 'category')) {
            Schema::table('entity_budgets', function (Blueprint $table) {
                $table->string('category')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('entity_budgets')) {
            return;
        }

        if (Schema::hasColumn('entity_budgets', 'category')) {
            Schema::table('entity_budgets', function (Blueprint $table) {
                $table->string('category')->nullable(false)->change();
            });
        }
    }
};
