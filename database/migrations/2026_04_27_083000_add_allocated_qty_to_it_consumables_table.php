<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_consumables', function (Blueprint $table) {
            if (!Schema::hasColumn('it_consumables', 'allocated_qty')) {
                $table->unsignedInteger('allocated_qty')->default(1)->after('item_description');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('it_consumables')) {
            return;
        }

        Schema::table('it_consumables', function (Blueprint $table) {
            if (Schema::hasColumn('it_consumables', 'allocated_qty')) {
                $table->dropColumn('allocated_qty');
            }
        });
    }
};
