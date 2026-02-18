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
        Schema::table('budget_expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('budget_expenses', 'is_contracting')) {
                $table->boolean('is_contracting')->default(false)->after('description');
            }
            if (!Schema::hasColumn('budget_expenses', 'amount_before_vat')) {
                $table->decimal('amount_before_vat', 15, 2)->nullable()->after('is_contracting');
            }
            if (!Schema::hasColumn('budget_expenses', 'vat_percent')) {
                $table->decimal('vat_percent', 5, 2)->nullable()->after('amount_before_vat');
            }
            if (!Schema::hasColumn('budget_expenses', 'vat_amount')) {
                $table->decimal('vat_amount', 15, 2)->nullable()->after('vat_percent');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('budget_expenses')) {
            return;
        }
        Schema::table('budget_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('budget_expenses', 'is_contracting')) {
                $table->dropColumn('is_contracting');
            }
            if (Schema::hasColumn('budget_expenses', 'amount_before_vat')) {
                $table->dropColumn('amount_before_vat');
            }
            if (Schema::hasColumn('budget_expenses', 'vat_percent')) {
                $table->dropColumn('vat_percent');
            }
            if (Schema::hasColumn('budget_expenses', 'vat_amount')) {
                $table->dropColumn('vat_amount');
            }
        });
    }
};
