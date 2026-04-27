<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_consumables', function (Blueprint $table) {
            if (!Schema::hasColumn('it_consumables', 'tkt_ref_no')) {
                $table->string('tkt_ref_no', 100)->nullable()->after('id_no');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('it_consumables')) {
            return;
        }

        Schema::table('it_consumables', function (Blueprint $table) {
            if (Schema::hasColumn('it_consumables', 'tkt_ref_no')) {
                $table->dropColumn('tkt_ref_no');
            }
        });
    }
};
