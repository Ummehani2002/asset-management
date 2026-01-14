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
        if (Schema::hasTable('brands')) {
            return; // Table already exists, skip creation
        }

        // Check if asset_categories table exists and get its id type
        $useIntegerForCategoryId = false;
        if (Schema::hasTable('asset_categories')) {
            $categoryIdType = \DB::select("SHOW COLUMNS FROM asset_categories WHERE Field = 'id'");
            if (!empty($categoryIdType) && str_contains(strtolower($categoryIdType[0]->Type), 'int') && !str_contains(strtolower($categoryIdType[0]->Type), 'bigint')) {
                $useIntegerForCategoryId = true;
            }
        }

        Schema::create('brands', function (Blueprint $table) use ($useIntegerForCategoryId) {
            $table->id();
            
            if ($useIntegerForCategoryId) {
                $table->unsignedInteger('asset_category_id');
            } else {
                $table->unsignedBigInteger('asset_category_id');
            }
            
            $table->string('name');
            $table->timestamps();
        });
        
        // Add foreign key after table creation
        if (Schema::hasTable('asset_categories')) {
            try {
                Schema::table('brands', function (Blueprint $table) {
                    $table->foreign('asset_category_id')->references('id')->on('asset_categories')->onDelete('cascade');
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
        if (!Schema::hasTable('brands')) {
            return;
        }
        
        // Drop foreign keys from dependent tables first
        if (Schema::hasTable('category_features')) {
            try {
                $foreignKeys = \DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'category_features' 
                    AND COLUMN_NAME = 'brand_id'
                    AND REFERENCED_TABLE_NAME = 'brands'
                ");
                
                foreach ($foreignKeys as $fk) {
                    try {
                        \DB::statement("ALTER TABLE category_features DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                    } catch (\Exception $e) {
                        // Foreign key might not exist, continue
                    }
                }
            } catch (\Exception $e) {
                // Continue
            }
        }
        
        // Drop foreign key from brands table
        try {
            $foreignKeys = \DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'brands' 
                AND COLUMN_NAME = 'asset_category_id'
                AND REFERENCED_TABLE_NAME = 'asset_categories'
            ");
            
            foreach ($foreignKeys as $fk) {
                try {
                    \DB::statement("ALTER TABLE brands DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                } catch (\Exception $e) {
                    // Foreign key might not exist, continue
                }
            }
        } catch (\Exception $e) {
            // Continue
        }
        
        Schema::dropIfExists('brands');
    }
};
