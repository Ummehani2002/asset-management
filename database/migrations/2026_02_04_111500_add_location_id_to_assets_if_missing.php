<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add location_id to assets table if it doesn't exist.
     * Some deployments may have assets table created before location_id was in the schema.
     */
    public function up(): void
    {
        if (Schema::hasTable('assets') && !Schema::hasColumn('assets', 'location_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->string('location_id')->nullable()->after('asset_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('assets') && Schema::hasColumn('assets', 'location_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropColumn('location_id');
            });
        }
    }
};
