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
        // Check the type of employees.id to match it
        $useInteger = false;
        if (Schema::hasTable('employees')) {
            $employeeIdType = \DB::select("SHOW COLUMNS FROM employees WHERE Field = 'id'");
            if (!empty($employeeIdType) && str_contains(strtolower($employeeIdType[0]->Type), 'int') && !str_contains(strtolower($employeeIdType[0]->Type), 'bigint')) {
                $useInteger = true; // employees.id is int
            }
        }
        
        $employeeIdAdded = false;
        Schema::table('issue_notes', function (Blueprint $table) use ($useInteger, &$employeeIdAdded) {
            if (!Schema::hasColumn('issue_notes', 'entity')) {
                $table->string('entity')->nullable()->after('department');
            }
            if (!Schema::hasColumn('issue_notes', 'employee_id')) {
                // Use the appropriate type based on employees.id
                if ($useInteger) {
                    $table->unsignedInteger('employee_id')->nullable()->after('id');
                } else {
                    $table->unsignedBigInteger('employee_id')->nullable()->after('id');
                }
                $employeeIdAdded = true;
            }
        });
        
        // Add foreign key constraint separately after column creation
        // This allows us to catch errors properly
        if ($employeeIdAdded && Schema::hasTable('employees')) {
            try {
                Schema::table('issue_notes', function (Blueprint $table) {
                    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might fail due to type incompatibility, continue without it
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if table exists before trying to modify it
        if (!Schema::hasTable('issue_notes')) {
            return;
        }
        
        Schema::table('issue_notes', function (Blueprint $table) {
            if (Schema::hasColumn('issue_notes', 'employee_id')) {
                // Check if foreign key exists before trying to drop it
                $foreignKeys = \DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'issue_notes' 
                    AND COLUMN_NAME = 'employee_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                foreach ($foreignKeys as $fk) {
                    try {
                        \DB::statement("ALTER TABLE issue_notes DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                    } catch (\Exception $e) {
                        // Foreign key might not exist, continue
                    }
                }
                
                $table->dropColumn('employee_id');
            }
            if (Schema::hasColumn('issue_notes', 'entity')) {
                $table->dropColumn('entity');
            }
        });
    }
};
