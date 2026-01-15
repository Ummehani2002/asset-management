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
        // Check if columns don't exist before adding them
        if (!Schema::hasColumn('projects', 'project_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('project_id')->unique()->after('id');
            });
        }
        if (!Schema::hasColumn('projects', 'project_name')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('project_name')->after('project_id');
            });
        }
        if (!Schema::hasColumn('projects', 'entity')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('entity')->nullable()->after('project_name');
            });
        }
        if (!Schema::hasColumn('projects', 'project_manager')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('project_manager')->nullable()->after('entity');
            });
        }
        if (!Schema::hasColumn('projects', 'pc_secretary')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('pc_secretary')->nullable()->after('project_manager');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('projects', 'pc_secretary')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('pc_secretary');
            });
        }
        if (Schema::hasColumn('projects', 'project_manager')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('project_manager');
            });
        }
        if (Schema::hasColumn('projects', 'entity')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('entity');
            });
        }
        if (Schema::hasColumn('projects', 'project_name')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('project_name');
            });
        }
        if (Schema::hasColumn('projects', 'project_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('project_id');
            });
        }
    }
};
