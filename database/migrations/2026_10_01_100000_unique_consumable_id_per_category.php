<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('it_consumables')) {
            return;
        }

        Schema::table('it_consumables', function (Blueprint $table) {
            try {
                $table->dropUnique(['id_no']);
            } catch (\Throwable $e) {
                // Index name may differ by driver; ignore if already dropped.
            }
        });

        if (Schema::hasColumn('it_consumables', 'asset_category_id')) {
            Schema::table('it_consumables', function (Blueprint $table) {
                $table->unique(['id_no', 'asset_category_id'], 'it_consumables_id_no_category_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('it_consumables')) {
            return;
        }

        Schema::table('it_consumables', function (Blueprint $table) {
            try {
                $table->dropUnique('it_consumables_id_no_category_unique');
            } catch (\Throwable $e) {
                // ignore
            }
        });

        Schema::table('it_consumables', function (Blueprint $table) {
            $table->unique('id_no');
        });
    }
};
