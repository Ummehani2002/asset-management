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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_id')->unique();
            $table->string('project_name');
            $table->string('entity')->nullable();
            $table->string('project_manager')->nullable();
            $table->string('pc_secretary')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints from dependent tables first
        // Check if simcard_transactions table exists and has foreign key
        if (Schema::hasTable('simcard_transactions')) {
            try {
                $foreignKeys = \DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'simcard_transactions' 
                    AND REFERENCED_TABLE_NAME = 'projects'
                ");
                
                foreach ($foreignKeys as $fk) {
                    try {
                        \DB::statement("ALTER TABLE simcard_transactions DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                    } catch (\Exception $e) {
                        // Foreign key might not exist, continue
                    }
                }
            } catch (\Exception $e) {
                // Table might not exist or query failed, continue
            }
        }
        
        // Also check other tables that might reference projects
        $tablesToCheck = ['internet_services', 'asset_transactions', 'issue_notes'];
        foreach ($tablesToCheck as $tableName) {
            if (Schema::hasTable($tableName)) {
                try {
                    $foreignKeys = \DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = ? 
                        AND REFERENCED_TABLE_NAME = 'projects'
                    ", [$tableName]);
                    
                    foreach ($foreignKeys as $fk) {
                        try {
                            \DB::statement("ALTER TABLE {$tableName} DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                        } catch (\Exception $e) {
                            // Foreign key might not exist, continue
                        }
                    }
                } catch (\Exception $e) {
                    // Continue to next table
                }
            }
        }
        
        Schema::dropIfExists('projects');
    }
};
