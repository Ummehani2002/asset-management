<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove location_id (string code) from locations; keep only entity, country, name.
     * Convert assets.location_id from string (Location.location_id) to FK (locations.id).
     */
    public function up(): void
    {
        // 1. Convert assets.location_id from string to FK (locations.id)
        if (Schema::hasTable('assets') && Schema::hasColumn('assets', 'location_id')) {
            // Add new column for location FK
            Schema::table('assets', function (Blueprint $table) {
                $table->unsignedBigInteger('location_fk')->nullable()->after('asset_id');
            });
            // Backfill: set location_fk = locations.id where locations.location_id = assets.location_id
            if (Schema::hasTable('locations') && Schema::hasColumn('locations', 'location_id')) {
                $assets = DB::table('assets')->whereNotNull('location_id')->get(['id', 'location_id']);
                foreach ($assets as $asset) {
                    $loc = DB::table('locations')->where('location_id', $asset->location_id)->first();
                    if ($loc) {
                        DB::table('assets')->where('id', $asset->id)->update(['location_fk' => $loc->id]);
                    }
                }
            }
            Schema::table('assets', function (Blueprint $table) {
                $table->dropColumn('location_id');
            });
            Schema::table('assets', function (Blueprint $table) {
                $table->renameColumn('location_fk', 'location_id');
            });
        }

        // 2. Drop location_id column from locations table (unique index is dropped with column)
        if (Schema::hasTable('locations') && Schema::hasColumn('locations', 'location_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->dropColumn('location_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore locations.location_id (string, unique)
        if (Schema::hasTable('locations') && !Schema::hasColumn('locations', 'location_id')) {
            Schema::table('locations', function (Blueprint $table) {
                $table->string('location_id')->nullable()->after('id');
            });
            // Optionally backfill from id, e.g. 'LOC' . id
            DB::table('locations')->orderBy('id')->each(function ($loc) {
                DB::table('locations')->where('id', $loc->id)->update(['location_id' => 'LOC' . $loc->id]);
            });
            Schema::table('locations', function (Blueprint $table) {
                $table->string('location_id')->nullable(false)->change();
                $table->unique('location_id');
            });
        }

        // Restore assets.location_id as string
        if (Schema::hasTable('assets') && Schema::hasColumn('assets', 'location_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->string('location_id_old')->nullable()->after('asset_id');
            });
            Schema::table('assets', function (Blueprint $table) {
                $table->renameColumn('location_id', 'location_fk');
            });
            $assets = DB::table('assets')->whereNotNull('location_fk')->get(['id', 'location_fk']);
            foreach ($assets as $asset) {
                $loc = DB::table('locations')->find($asset->location_fk);
                if ($loc && isset($loc->location_id)) {
                    DB::table('assets')->where('id', $asset->id)->update(['location_id_old' => $loc->location_id]);
                }
            }
            Schema::table('assets', function (Blueprint $table) {
                $table->dropColumn('location_fk');
            });
            Schema::table('assets', function (Blueprint $table) {
                $table->renameColumn('location_id_old', 'location_id');
            });
        }
    }
};
