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
        if (Schema::hasTable('time_managements')) {
            return; // Table already exists, skip creation
        }
        
        // Check the type of employees.id to match it
        $useInteger = false;
        if (Schema::hasTable('employees')) {
            $employeeIdType = \DB::select("SHOW COLUMNS FROM employees WHERE Field = 'id'");
            if (!empty($employeeIdType) && str_contains(strtolower($employeeIdType[0]->Type), 'int') && !str_contains(strtolower($employeeIdType[0]->Type), 'bigint')) {
                $useInteger = true; // employees.id is int
            }
        }
        
        Schema::create('time_managements', function (Blueprint $table) use ($useInteger) {
            $table->id();
            $table->string('ticket_number')->nullable();
            
            // Use the appropriate type based on employees.id
            if ($useInteger) {
                $table->unsignedInteger('employee_id')->nullable();
            } else {
                $table->unsignedBigInteger('employee_id')->nullable();
            }
            
            $table->string('employee_name')->nullable();
            $table->string('project_name')->nullable();
            $table->date('job_card_date')->nullable();
            $table->decimal('standard_man_hours', 8, 2)->nullable();
            $table->datetime('start_time')->nullable();
            $table->datetime('end_time')->nullable();
            $table->decimal('duration_hours', 8, 2)->nullable();
            $table->string('status')->nullable();
            $table->integer('delayed_days')->nullable();
            $table->text('delay_reason')->nullable();
            $table->decimal('performance_percent', 5, 2)->nullable();
            // last_delay_email_sent_at added in 2025_12_16_053345
            $table->timestamps();
        });
        
        // Add foreign key constraint separately after table creation
        // This allows us to catch errors properly
        if (Schema::hasTable('employees')) {
            try {
                Schema::table('time_managements', function (Blueprint $table) {
                    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
                });
            } catch (\Exception $e) {
                // Foreign key might fail due to type incompatibility, continue without it
                // The column will still be created
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_managements');
    }
};
