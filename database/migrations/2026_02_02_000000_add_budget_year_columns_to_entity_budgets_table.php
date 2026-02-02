<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds budget columns for years 2026-2030 to support Entity Budget Management.
     */
    public function up(): void
    {
        if (!Schema::hasTable('entity_budgets')) {
            return;
        }

        $years = [2026, 2027, 2028, 2029, 2030];

        $lastColumn = 'budget_2025';
        foreach ($years as $year) {
            $column = 'budget_' . $year;
            if (!Schema::hasColumn('entity_budgets', $column)) {
                Schema::table('entity_budgets', function (Blueprint $table) use ($column, $lastColumn) {
                    $table->decimal($column, 15, 2)->nullable()->after($lastColumn);
                });
                $lastColumn = $column;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('entity_budgets')) {
            return;
        }

        $years = [2026, 2027, 2028, 2029, 2030];
        $columns = array_map(fn ($y) => 'budget_' . $y, $years);

        Schema::table('entity_budgets', function (Blueprint $table) use ($columns) {
            foreach ($columns as $column) {
                if (Schema::hasColumn('entity_budgets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
