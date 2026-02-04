<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill asset.location_id from the latest assign transaction for assigned assets.
     * This fixes assets where location was not previously saved to the asset record.
     */
    public function up(): void
    {
        if (!\Schema::hasTable('assets') || !\Schema::hasTable('asset_transactions') || !\Schema::hasTable('locations')) {
            return;
        }

        // Skip if assets table doesn't have location_id column
        if (!\Schema::hasColumn('assets', 'location_id')) {
            return;
        }

        $assignedAssets = DB::table('assets')
            ->where('status', 'assigned')
            ->pluck('id');

        foreach ($assignedAssets as $assetId) {
            $latestAssign = DB::table('asset_transactions')
                ->where('asset_id', $assetId)
                ->where('transaction_type', 'assign')
                ->whereNotNull('location_id')
                ->orderByDesc('created_at')
                ->first();

            if ($latestAssign && $latestAssign->location_id) {
                $location = DB::table('locations')->find($latestAssign->location_id);
                if ($location && isset($location->location_id)) {
                    DB::table('assets')
                        ->where('id', $assetId)
                        ->update(['location_id' => $location->location_id]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback - we don't want to clear location data
    }
};
