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
        if (Schema::hasTable('asset_transactions')) {
            return; // Table already exists, skip creation
        }
        
        // Check the type of assets.id to match it
        $useIntegerForAsset = false;
        if (Schema::hasTable('assets')) {
            $assetIdType = \DB::select("SHOW COLUMNS FROM assets WHERE Field = 'id'");
            if (!empty($assetIdType) && str_contains(strtolower($assetIdType[0]->Type), 'int') && !str_contains(strtolower($assetIdType[0]->Type), 'bigint')) {
                $useIntegerForAsset = true; // assets.id is int
            }
        }
        
        // Check the type of employees.id to match it
        $useIntegerForEmployee = false;
        if (Schema::hasTable('employees')) {
            $employeeIdType = \DB::select("SHOW COLUMNS FROM employees WHERE Field = 'id'");
            if (!empty($employeeIdType) && str_contains(strtolower($employeeIdType[0]->Type), 'int') && !str_contains(strtolower($employeeIdType[0]->Type), 'bigint')) {
                $useIntegerForEmployee = true; // employees.id is int
            }
        }
        
        Schema::create('asset_transactions', function (Blueprint $table) use ($useIntegerForAsset, $useIntegerForEmployee) {
            $table->id();
            $table->string('transaction_type'); // assign, return, system_maintenance
            
            // Use the appropriate type based on assets.id
            if ($useIntegerForAsset) {
                $table->unsignedInteger('asset_id');
            } else {
                $table->unsignedBigInteger('asset_id');
            }
            
            // Use the appropriate type based on employees.id
            if ($useIntegerForEmployee) {
                $table->unsignedInteger('employee_id')->nullable();
            } else {
                $table->unsignedBigInteger('employee_id')->nullable();
            }
            
            $table->string('project_name')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('return_date')->nullable();
            $table->date('receive_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->string('assigned_to')->nullable();
            $table->string('repair_type')->nullable();
            $table->text('remarks')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
        
        // Add foreign key constraints separately after table creation
        // This allows us to catch errors properly
        if (Schema::hasTable('assets')) {
            try {
                Schema::table('asset_transactions', function (Blueprint $table) {
                    $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // Foreign key might fail due to type incompatibility, continue without it
            }
        }
        
        if (Schema::hasTable('employees')) {
            try {
                Schema::table('asset_transactions', function (Blueprint $table) {
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
        Schema::dropIfExists('asset_transactions');
    }
};
