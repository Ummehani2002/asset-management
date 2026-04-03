<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assets')) {
            return;
        }

        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'model_number')) {
                $table->string('model_number')->nullable()->after('brand_id');
            }
        });

        // Best-effort backfill for existing assets from saved feature values.
        // It looks for a feature whose name contains "model" (e.g. "Model No").
        $rows = DB::table('assets as a')
            ->join('category_feature_values as cfv', 'cfv.asset_id', '=', 'a.id')
            ->join('category_features as cf', 'cf.id', '=', 'cfv.category_feature_id')
            ->whereNull('a.model_number')
            ->whereNotNull('cfv.feature_value')
            ->whereRaw('LOWER(cf.feature_name) LIKE ?', ['%model%'])
            ->select('a.id as asset_id', 'cfv.feature_value')
            ->orderBy('cfv.id')
            ->get();

        $seen = [];
        foreach ($rows as $row) {
            $assetId = (int) $row->asset_id;
            if (isset($seen[$assetId])) {
                continue;
            }

            $value = trim((string) $row->feature_value);
            if ($value === '') {
                continue;
            }

            DB::table('assets')
                ->where('id', $assetId)
                ->whereNull('model_number')
                ->update(['model_number' => $value]);

            $seen[$assetId] = true;
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('assets')) {
            return;
        }

        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'model_number')) {
                $table->dropColumn('model_number');
            }
        });
    }
};
