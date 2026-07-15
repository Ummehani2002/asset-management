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
        if (!Schema::hasColumn('budget_expenses', 'submission_group_id')) {
            Schema::table('budget_expenses', function (Blueprint $table) {
                $table->string('submission_group_id', 36)->nullable()->after('entity_budget_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('budget_expenses')) {
            return;
        }
        if (Schema::hasColumn('budget_expenses', 'submission_group_id')) {
            Schema::table('budget_expenses', function (Blueprint $table) {
                $table->dropIndex(['submission_group_id']);
                $table->dropColumn('submission_group_id');
            });
        }
    }
};
