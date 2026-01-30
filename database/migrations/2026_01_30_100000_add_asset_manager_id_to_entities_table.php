<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('entities')) {
            return;
        }
        if (Schema::hasColumn('entities', 'asset_manager_id')) {
            return;
        }
        Schema::table('entities', function (Blueprint $table) {
            $table->unsignedBigInteger('asset_manager_id')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('entities') && Schema::hasColumn('entities', 'asset_manager_id')) {
            Schema::table('entities', function (Blueprint $table) {
                $table->dropColumn('asset_manager_id');
            });
        }
    }
};
