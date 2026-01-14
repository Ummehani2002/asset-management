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
        if (Schema::hasTable('assets')) {
            return; // Table already exists, skip creation
        }

        // Check id types of referenced tables
        $useIntegerForCategoryId = false;
        if (Schema::hasTable('asset_categories')) {
            $categoryIdType = \DB::select("SHOW COLUMNS FROM asset_categories WHERE Field = 'id'");
            if (!empty($categoryIdType) && str_contains(strtolower($categoryIdType[0]->Type), 'int') && !str_contains(strtolower($categoryIdType[0]->Type), 'bigint')) {
                $useIntegerForCategoryId = true;
            }
        }

        $useIntegerForBrandId = false;
        if (Schema::hasTable('brands')) {
            $brandIdType = \DB::select("SHOW COLUMNS FROM brands WHERE Field = 'id'");
            if (!empty($brandIdType) && str_contains(strtolower($brandIdType[0]->Type), 'int') && !str_contains(strtolower($brandIdType[0]->Type), 'bigint')) {
                $useIntegerForBrandId = true;
            }
        }

        Schema::create('assets', function (Blueprint $table) use ($useIntegerForCategoryId, $useIntegerForBrandId) {
            $table->id();
            $table->string('asset_id')->unique();
            $table->string('location_id')->nullable();
            
            if ($useIntegerForCategoryId) {
                $table->unsignedInteger('asset_category_id');
            } else {
                $table->unsignedBigInteger('asset_category_id');
            }
            
            if ($useIntegerForBrandId) {
                $table->unsignedInteger('brand_id');
            } else {
                $table->unsignedBigInteger('brand_id');
            }
            
            $table->date('purchase_date')->nullable();
            $table->date('warranty_start')->nullable();
            $table->integer('warranty_years')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('po_number')->nullable();
            $table->string('serial_number');
            $table->string('invoice_path')->nullable();
            $table->string('status')->default('available'); // available, assigned, under_maintenance
            $table->timestamps();
        });
        
        // Add foreign keys after table creation
        if (Schema::hasTable('asset_categories')) {
            try {
                Schema::table('assets', function (Blueprint $table) {
                    $table->foreign('asset_category_id')->references('id')->on('asset_categories')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // Foreign key might fail due to type incompatibility, continue without it
            }
        }

        if (Schema::hasTable('brands')) {
            try {
                Schema::table('assets', function (Blueprint $table) {
                    $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
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
        if (!Schema::hasTable('assets')) {
            return;
        }
        
        // Drop foreign keys from dependent tables first
        $tablesToCheck = ['asset_transactions', 'category_feature_values'];
        foreach ($tablesToCheck as $tableName) {
            if (Schema::hasTable($tableName)) {
                try {
                    $foreignKeys = \DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = ? 
                        AND COLUMN_NAME = 'asset_id'
                        AND REFERENCED_TABLE_NAME = 'assets'
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
        
        // Drop foreign keys from assets table
        try {
            $foreignKeys = \DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'assets' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            foreach ($foreignKeys as $fk) {
                try {
                    \DB::statement("ALTER TABLE assets DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue
                }
            }
        } catch (\Exception $e) {
            // Continue
        }
        
        Schema::dropIfExists('assets');
    }
};
