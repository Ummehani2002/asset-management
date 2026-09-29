<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('it_consumables') && ! Schema::hasColumn('it_consumables', 'asset_category_id')) {
            Schema::table('it_consumables', function (Blueprint $table) {
                $table->unsignedBigInteger('asset_category_id')->nullable()->after('id');
                $table->index('asset_category_id');
            });
        }

        if (Schema::hasTable('it_consumable_issues') && ! Schema::hasColumn('it_consumable_issues', 'employee_id')) {
            Schema::table('it_consumable_issues', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('it_consumable_id');
                $table->index('employee_id');
            });
        }

        if (Schema::hasTable('assets') && Schema::hasColumn('assets', 'serial_number')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->string('serial_number', 100)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('it_consumables') && Schema::hasColumn('it_consumables', 'asset_category_id')) {
            Schema::table('it_consumables', function (Blueprint $table) {
                $table->dropColumn('asset_category_id');
            });
        }

        if (Schema::hasTable('it_consumable_issues') && Schema::hasColumn('it_consumable_issues', 'employee_id')) {
            Schema::table('it_consumable_issues', function (Blueprint $table) {
                $table->dropColumn('employee_id');
            });
        }
    }
};
