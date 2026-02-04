<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove the 4 license features that were added to category_features.
     * User wants these as manual fields in Asset Master, not as category features.
     */
    public function up(): void
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
            $featureIds = DB::table('category_features')->where('feature_name', $name)->pluck('id');
            if ($featureIds->isNotEmpty()) {
                DB::table('category_feature_values')->whereIn('category_feature_id', $featureIds)->delete();
                DB::table('category_features')->whereIn('id', $featureIds)->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback - we don't want to re-add category features
    }
};
