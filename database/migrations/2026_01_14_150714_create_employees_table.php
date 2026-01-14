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
        if (Schema::hasTable('employees')) {
            return; // Table already exists, skip creation
        }

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 20)->unique();
            $table->string('name', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('entity_name', 100);
            $table->string('department_name', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('employees')) {
            return;
        }
        
        // Drop foreign keys from dependent tables first
        $tablesToCheck = ['asset_transactions', 'time_managements', 'issue_notes', 'entity_budgets'];
        foreach ($tablesToCheck as $tableName) {
            if (Schema::hasTable($tableName)) {
                try {
                    $foreignKeys = \DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = ? 
                        AND COLUMN_NAME = 'employee_id'
                        AND REFERENCED_TABLE_NAME = 'employees'
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
        
        Schema::dropIfExists('employees');
    }
};
