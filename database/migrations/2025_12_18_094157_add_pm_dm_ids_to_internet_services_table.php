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
        Schema::table('internet_services', function (Blueprint $table) {
            // Add columns for PM and DM IDs (without foreign key constraint to avoid compatibility issues)
            if (!Schema::hasColumn('internet_services', 'project_manager_id')) {
                $table->unsignedBigInteger('project_manager_id')->nullable()->after('person_in_charge_id');
            }
            if (!Schema::hasColumn('internet_services', 'document_controller_id')) {
                $table->unsignedBigInteger('document_controller_id')->nullable()->after('project_manager_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internet_services', function (Blueprint $table) {
            // Columns were added without foreign key constraints, so just drop the columns
            if (Schema::hasColumn('internet_services', 'document_controller_id')) {
                $table->dropColumn('document_controller_id');
            }
            if (Schema::hasColumn('internet_services', 'project_manager_id')) {
                $table->dropColumn('project_manager_id');
            }
        });
    }
};
