<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('budget_expenses')) {
            return;
        }
        if (Schema::hasColumn('budget_expenses', 'cost_head')) {
            return;
        }
        Schema::table('budget_expenses', function (Blueprint $table) {
            $table->string('cost_head')->nullable()->after('entity_budget_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('budget_expenses') || !Schema::hasColumn('budget_expenses', 'cost_head')) {
            return;
        }
        Schema::table('budget_expenses', function (Blueprint $table) {
            $table->dropColumn('cost_head');
        });
    }
};
