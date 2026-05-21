<?php

namespace App\Console\Commands;

use App\Models\Asset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillAssetModelFeatures extends Command
{
    protected $signature = 'assets:backfill-model-features {--dry-run : Show what would change without writing}';

    protected $description = 'Copy brand model master feature values onto assets that are missing them';

    public function handle(): int
    {
        if (!Schema::hasTable('category_feature_values') || !Schema::hasTable('model_feature_values')) {
            $this->error('Required tables are missing. Run migrations first.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $updatedAssets = 0;
        $insertedRows = 0;

        Asset::query()->orderBy('id')->chunkById(100, function ($assets) use ($dryRun, &$updatedAssets, &$insertedRows) {
            foreach ($assets as $asset) {
                $brandModel = $asset->linkedBrandModel();
                if (!$brandModel) {
                    continue;
                }

                $modelValues = \App\Models\ModelFeatureValue::where('brand_model_id', $brandModel->id)->get();
                $assetInserts = 0;

                foreach ($modelValues as $mfv) {
                    $featureId = (int) $mfv->category_feature_id;
                    $value = trim((string) ($mfv->feature_value ?? ''));
                    if ($featureId <= 0 || $value === '') {
                        continue;
                    }

                    $exists = DB::table('category_feature_values')
                        ->where('asset_id', $asset->id)
                        ->where('category_feature_id', $featureId)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    if (!$dryRun) {
                        DB::table('category_feature_values')->insert([
                            'asset_id' => $asset->id,
                            'category_feature_id' => $featureId,
                            'feature_value' => $value,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $assetInserts++;
                    $insertedRows++;
                }

                if ($assetInserts > 0 && empty($asset->model_number) && !empty($brandModel->model_number) && Schema::hasColumn('assets', 'model_number')) {
                    if (!$dryRun) {
                        $asset->update(['model_number' => $brandModel->model_number]);
                    }
                }

                if ($assetInserts > 0) {
                    $updatedAssets++;
                }
            }
        });

        $this->info(($dryRun ? '[dry-run] ' : '') . "Assets updated: {$updatedAssets}, feature rows added: {$insertedRows}");

        return self::SUCCESS;
    }
}
