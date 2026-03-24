<?php

namespace App\Console\Commands;

use App\Models\Asset;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeduplicateAssetSerials extends Command
{
    protected $signature = 'assets:dedupe-serials
                            {--dry-run : Show what would be changed without writing to DB}
                            {--apply : Apply changes and remove duplicates}';

    protected $description = 'Merge duplicate assets by serial number and keep one canonical record per serial.';

    public function handle()
    {
        if (!Schema::hasTable('assets')) {
            $this->warn('Assets table not found.');
            return 0;
        }

        $isDryRun = (bool) $this->option('dry-run');
        $isApply = (bool) $this->option('apply');
        if (!$isDryRun && !$isApply) {
            $this->warn('Use --dry-run to preview or --apply to execute.');
            return 0;
        }

        $duplicateGroups = DB::table('assets')
            ->selectRaw('LOWER(TRIM(serial_number)) as serial_key, COUNT(*) as total')
            ->whereNotNull('serial_number')
            ->whereRaw("TRIM(serial_number) != ''")
            ->groupBy('serial_key')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicateGroups->isEmpty()) {
            $this->info('No duplicate serial numbers found.');
            return 0;
        }

        $this->info('Duplicate serial groups found: ' . $duplicateGroups->count());

        $assetTxTable = Schema::hasTable('asset_transactions');
        $featureValuesTable = Schema::hasTable('category_feature_values');
        $maintenanceAssignmentsTable = Schema::hasTable('maintenance_assignments');
        $maintenanceApprovalsTable = Schema::hasTable('maintenance_approval_requests');

        $movedReferences = 0;
        $deletedAssets = 0;

        foreach ($duplicateGroups as $group) {
            $serialKey = $group->serial_key;
            $assets = Asset::query()
                ->whereRaw('LOWER(TRIM(serial_number)) = ?', [$serialKey])
                ->orderByRaw("CASE WHEN status = 'assigned' THEN 0 ELSE 1 END")
                ->orderBy('id', 'asc')
                ->get();

            if ($assets->count() <= 1) {
                continue;
            }

            $keep = $assets->first();
            $dupes = $assets->slice(1)->values();

            $this->line("Serial {$keep->serial_number}: keep #{$keep->id} ({$keep->asset_id}), duplicates: " . $dupes->pluck('id')->implode(', '));

            if ($isDryRun) {
                continue;
            }

            DB::transaction(function () use (
                $dupes,
                $keep,
                $assetTxTable,
                $featureValuesTable,
                $maintenanceAssignmentsTable,
                $maintenanceApprovalsTable,
                &$movedReferences,
                &$deletedAssets
            ) {
                foreach ($dupes as $dup) {
                    if ($assetTxTable) {
                        $movedReferences += DB::table('asset_transactions')
                            ->where('asset_id', $dup->id)
                            ->update(['asset_id' => $keep->id]);
                    }

                    if ($featureValuesTable) {
                        $movedReferences += DB::table('category_feature_values')
                            ->where('asset_id', $dup->id)
                            ->update(['asset_id' => $keep->id]);
                    }

                    if ($maintenanceAssignmentsTable) {
                        $movedReferences += DB::table('maintenance_assignments')
                            ->where('asset_id', $dup->id)
                            ->update(['asset_id' => $keep->id]);
                    }

                    if ($maintenanceApprovalsTable) {
                        $movedReferences += DB::table('maintenance_approval_requests')
                            ->where('asset_id', $dup->id)
                            ->update(['asset_id' => $keep->id]);
                    }

                    $dup->delete();
                    $deletedAssets++;
                }
            });
        }

        if ($isDryRun) {
            $this->info('Dry run complete. No changes were written.');
            return 0;
        }

        $this->info("Done. Deleted duplicate assets: {$deletedAssets}. Moved related references: {$movedReferences}.");
        return 0;
    }
}

