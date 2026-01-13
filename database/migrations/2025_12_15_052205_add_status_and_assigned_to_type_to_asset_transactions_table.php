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
        // Check if table exists before trying to modify it
        if (!Schema::hasTable('asset_transactions')) {
            return;
        }
        
        Schema::table('asset_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('asset_transactions', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('asset_transactions', 'assigned_to_type')) {
                $table->dropColumn('assigned_to_type');
            }
            if (Schema::hasColumn('asset_transactions', 'location_id')) {
                // Check if foreign key exists before trying to drop it
                $foreignKeys = \DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'asset_transactions' 
                    AND COLUMN_NAME = 'location_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($foreignKeys as $fk) {
                    try {
                        \DB::statement("ALTER TABLE asset_transactions DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                    } catch (\Exception $e) {
                        // Foreign key might not exist, continue
                    }
                }
                
                $table->dropColumn('location_id');
            }
        });
    }
};
