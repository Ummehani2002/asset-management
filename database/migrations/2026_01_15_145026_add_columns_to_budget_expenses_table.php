<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('budget_expenses')) {
            return; // Table doesn't exist, skip
        }

        // Check if columns already exist before adding
        if (!Schema::hasColumn('budget_expenses', 'entity_budget_id')) {
            // Check id type of entity_budgets table to match foreign key
            $useIntegerForBudgetId = false;
            if (Schema::hasTable('entity_budgets')) {
                $budgetIdType = \DB::select("SHOW COLUMNS FROM entity_budgets WHERE Field = 'id'");
                if (!empty($budgetIdType) && str_contains(strtolower($budgetIdType[0]->Type), 'int') && !str_contains(strtolower($budgetIdType[0]->Type), 'bigint')) {
                    $useIntegerForBudgetId = true;
                }
            }

            Schema::table('budget_expenses', function (Blueprint $table) use ($useIntegerForBudgetId) {
                // Foreign key to entity_budgets table
                if ($useIntegerForBudgetId) {
                    $table->unsignedInteger('entity_budget_id')->after('id');
                } else {
                    $table->unsignedBigInteger('entity_budget_id')->after('id');
                }
                
                $table->decimal('expense_amount', 15, 2)->after('entity_budget_id');
                $table->date('expense_date')->after('expense_amount');
                $table->text('description')->nullable()->after('expense_date');
            });

            // Add foreign key constraint if table exists
            if (Schema::hasTable('entity_budgets')) {
                try {
                    Schema::table('budget_expenses', function (Blueprint $table) {
                        $table->foreign('entity_budget_id')->references('id')->on('entity_budgets')->onDelete('cascade');
                    });
                } catch (\Exception $e) {
                    // Foreign key might fail due to type incompatibility, continue without it
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('budget_expenses')) {
            return;
        }

        // Drop foreign key first if it exists
        if (Schema::hasColumn('budget_expenses', 'entity_budget_id')) {
            try {
                $foreignKeys = \DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'budget_expenses'
                    AND REFERENCED_TABLE_NAME = 'entity_budgets'
                ");
                
                foreach ($foreignKeys as $fk) {
                    \DB::statement("ALTER TABLE budget_expenses DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                }
            } catch (\Exception $e) {
                // Foreign keys might not exist, continue
            }

            Schema::table('budget_expenses', function (Blueprint $table) {
                $table->dropColumn(['entity_budget_id', 'expense_amount', 'expense_date', 'description']);
            });
        }
    }
};
