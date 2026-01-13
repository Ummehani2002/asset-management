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
        if (Schema::hasTable('category_features')) {
            return; // Table already exists, skip creation
        }
        
        // Check the type of asset_categories.id to match it
        $useIntegerForCategory = false;
        if (Schema::hasTable('asset_categories')) {
            $assetCategoryIdType = \DB::select("SHOW COLUMNS FROM asset_categories WHERE Field = 'id'");
            if (!empty($assetCategoryIdType) && str_contains(strtolower($assetCategoryIdType[0]->Type), 'int') && !str_contains(strtolower($assetCategoryIdType[0]->Type), 'bigint')) {
                $useIntegerForCategory = true;
            }
        }
        
        // Check the type of brands.id to match it
        $useIntegerForBrand = false;
        if (Schema::hasTable('brands')) {
            $brandIdType = \DB::select("SHOW COLUMNS FROM brands WHERE Field = 'id'");
            if (!empty($brandIdType) && str_contains(strtolower($brandIdType[0]->Type), 'int') && !str_contains(strtolower($brandIdType[0]->Type), 'bigint')) {
                $useIntegerForBrand = true;
            }
        }
        
        Schema::create('category_features', function (Blueprint $table) use ($useIntegerForCategory, $useIntegerForBrand) {
            $table->id();
            
            // Use the appropriate type based on referenced table id types
            if ($useIntegerForCategory) {
                $table->unsignedInteger('asset_category_id')->nullable();
            } else {
                $table->unsignedBigInteger('asset_category_id')->nullable();
            }
            
            if ($useIntegerForBrand) {
                $table->unsignedInteger('brand_id')->nullable();
            } else {
                $table->unsignedBigInteger('brand_id')->nullable();
            }
            
            $table->string('feature_name');
            // sub_fields added in 2025_12_17_104503
            $table->timestamps();
        });
        
        // Add foreign key constraints separately after table creation
        // This allows us to catch errors properly
        if (Schema::hasTable('asset_categories')) {
            try {
                Schema::table('category_features', function (Blueprint $table) {
                    $table->foreign('asset_category_id')->references('id')->on('asset_categories')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // Foreign key might fail due to type incompatibility, continue without it
            }
        }
        
        if (Schema::hasTable('brands')) {
            try {
                Schema::table('category_features', function (Blueprint $table) {
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
        // Drop foreign key constraints from dependent tables first
        // Check if category_feature_values table exists and has foreign key
        if (Schema::hasTable('category_feature_values')) {
            try {
                Schema::table('category_feature_values', function (Blueprint $table) {
                    // Try to drop the foreign key if it exists
                    $foreignKeys = \DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'category_feature_values' 
                        AND REFERENCED_TABLE_NAME = 'category_features'
                    ");
                    
                    foreach ($foreignKeys as $fk) {
                        \DB::statement("ALTER TABLE category_feature_values DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                    }
                });
            } catch (\Exception $e) {
                // Foreign key might not exist or already dropped, continue
            }
        }
        
        Schema::dropIfExists('category_features');
    }
};
