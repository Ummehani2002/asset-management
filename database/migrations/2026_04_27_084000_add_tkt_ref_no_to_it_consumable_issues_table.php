<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('it_consumable_issues', function (Blueprint $table) {
            if (!Schema::hasColumn('it_consumable_issues', 'tkt_ref_no')) {
                $table->string('tkt_ref_no', 100)->nullable()->after('issue_to_name');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('it_consumable_issues')) {
            return;
        }

        Schema::table('it_consumable_issues', function (Blueprint $table) {
            if (Schema::hasColumn('it_consumable_issues', 'tkt_ref_no')) {
                $table->dropColumn('tkt_ref_no');
            }
        });
    }
};
