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
        Schema::table('asset_transactions', function (Blueprint $table) {
            $table->string('status')->nullable()->after('transaction_type');
            $table->string('assigned_to_type')->nullable()->after('employee_id');
            
            // Check if location_id exists, if not add it
            if (!Schema::hasColumn('asset_transactions', 'location_id')) {
                $table->unsignedBigInteger('location_id')->nullable()->after('project_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('asset_transactions', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('asset_transactions', 'assigned_to_type')) {
                $table->dropColumn('assigned_to_type');
            }
            if (Schema::hasColumn('asset_transactions', 'location_id')) {
                $table->dropColumn('location_id');
            }
        });
    }
};
