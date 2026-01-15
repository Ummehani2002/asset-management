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
        if (Schema::hasTable('category_feature_values')) {
            return; // Table already exists, skip creation
        }

        // Check id types of referenced tables to match foreign keys
        $useIntegerForAssetId = false;
        if (Schema::hasTable('assets')) {
            $assetIdType = \DB::select("SHOW COLUMNS FROM assets WHERE Field = 'id'");
            if (!empty($assetIdType) && str_contains(strtolower($assetIdType[0]->Type), 'int') && !str_contains(strtolower($assetIdType[0]->Type), 'bigint')) {
                $useIntegerForAssetId = true;
            }
        }

        $useIntegerForFeatureId = false;
        if (Schema::hasTable('category_features')) {
            $featureIdType = \DB::select("SHOW COLUMNS FROM category_features WHERE Field = 'id'");
            if (!empty($featureIdType) && str_contains(strtolower($featureIdType[0]->Type), 'int') && !str_contains(strtolower($featureIdType[0]->Type), 'bigint')) {
                $useIntegerForFeatureId = true;
            }
        }

        Schema::create('category_feature_values', function (Blueprint $table) use ($useIntegerForAssetId, $useIntegerForFeatureId) {
            $table->id();
            
            // Foreign key to assets table
            if ($useIntegerForAssetId) {
                $table->unsignedInteger('asset_id');
            } else {
                $table->unsignedBigInteger('asset_id');
            }
            
            // Foreign key to category_features table
            if ($useIntegerForFeatureId) {
                $table->unsignedInteger('category_feature_id');
            } else {
                $table->unsignedBigInteger('category_feature_id');
            }
            
            $table->text('feature_value')->nullable();
            $table->timestamps();
        });

        // Add foreign key constraints if tables exist
        if (Schema::hasTable('assets')) {
            try {
                Schema::table('category_feature_values', function (Blueprint $table) {
                    $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // Foreign key might fail due to type incompatibility, continue without it
            }
        }

        if (Schema::hasTable('category_features')) {
            try {
                Schema::table('category_feature_values', function (Blueprint $table) {
                    $table->foreign('category_feature_id')->references('id')->on('category_features')->onDelete('cascade');
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
        // Drop foreign keys first if they exist
        if (Schema::hasTable('category_feature_values')) {
            try {
                $foreignKeys = \DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'category_feature_values'
                ");
                
                foreach ($foreignKeys as $fk) {
                    \DB::statement("ALTER TABLE category_feature_values DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {
                // Foreign keys might not exist, continue
            }
        }
        
        Schema::dropIfExists('category_feature_values');
    }
};
