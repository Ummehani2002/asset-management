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
        Schema::table('projects', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('projects', 'project_id')) {
                $table->string('project_id')->unique()->after('id');
            }
            if (!Schema::hasColumn('projects', 'project_name')) {
                $table->string('project_name')->after('project_id');
            }
            if (!Schema::hasColumn('projects', 'entity')) {
                $table->string('entity')->nullable()->after('project_name');
            }
            if (!Schema::hasColumn('projects', 'project_manager')) {
                $table->string('project_manager')->nullable()->after('entity');
            }
            if (!Schema::hasColumn('projects', 'pc_secretary')) {
                $table->string('pc_secretary')->nullable()->after('project_manager');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'pc_secretary')) {
                $table->dropColumn('pc_secretary');
            }
            if (Schema::hasColumn('projects', 'project_manager')) {
                $table->dropColumn('project_manager');
            }
            if (Schema::hasColumn('projects', 'entity')) {
                $table->dropColumn('entity');
            }
            if (Schema::hasColumn('projects', 'project_name')) {
                $table->dropColumn('project_name');
            }
            if (Schema::hasColumn('projects', 'project_id')) {
                $table->dropColumn('project_id');
            }
        });
    }
};
