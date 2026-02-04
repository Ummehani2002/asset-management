<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Operating System License Key, Antivirus License Version,
     * Patch Management Software, and AutoCAD License Key features
     * to all existing laptop brands.
     */
    public function up(): void
    {
        if (!Schema::hasTable('asset_categories') || !Schema::hasTable('brands') || !Schema::hasTable('category_features')) {
            return;
        }

        $laptopCategory = DB::table('asset_categories')
            ->whereRaw('LOWER(TRIM(category_name)) = ?', ['laptop'])
            ->first();

        if (!$laptopCategory) {
            return;
        }

        $laptopBrands = DB::table('brands')
            ->where('asset_category_id', $laptopCategory->id)
            ->get();

        $newFeatures = [
            'Operating System License Key',
            'Antivirus License Version',
            'Patch Management Software',
            'AutoCAD License Key',
        ];

        foreach ($laptopBrands as $brand) {
            foreach ($newFeatures as $featureName) {
                $exists = DB::table('category_features')
                    ->where('brand_id', $brand->id)
                    ->where('feature_name', $featureName)
                    ->exists();

                if (!$exists) {
                    DB::table('category_features')->insert([
                        'brand_id' => $brand->id,
                        'asset_category_id' => $laptopCategory->id,
                        'feature_name' => $featureName,
                        'sub_fields' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('category_features')) {
            return;
        }

        $featureNames = [
            'Operating System License Key',
            'Antivirus License Version',
            'Patch Management Software',
            'AutoCAD License Key',
        ];

        foreach ($featureNames as $name) {
            DB::table('category_features')->where('feature_name', $name)->delete();
        }
    }
};
