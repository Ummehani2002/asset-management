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
            if (!Schema::hasColumn('budget_expenses', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('expense_amount');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('budget_expenses')) {
            return;
        }

        Schema::table('budget_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('budget_expenses', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }
};

