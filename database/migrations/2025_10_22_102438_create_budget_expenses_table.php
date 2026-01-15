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
        if (Schema::hasTable('budget_expenses')) {
            return; // Table already exists, skip creation
        }

        // Check id type of entity_budgets table to match foreign key
        $useIntegerForBudgetId = false;
        if (Schema::hasTable('entity_budgets')) {
            $budgetIdType = \DB::select("SHOW COLUMNS FROM entity_budgets WHERE Field = 'id'");
            if (!empty($budgetIdType) && str_contains(strtolower($budgetIdType[0]->Type), 'int') && !str_contains(strtolower($budgetIdType[0]->Type), 'bigint')) {
                $useIntegerForBudgetId = true;
            }
        }

        Schema::create('budget_expenses', function (Blueprint $table) use ($useIntegerForBudgetId) {
            $table->id();
            
            // Foreign key to entity_budgets table
            if ($useIntegerForBudgetId) {
                $table->unsignedInteger('entity_budget_id');
            } else {
                $table->unsignedBigInteger('entity_budget_id');
            }
            
            $table->decimal('expense_amount', 15, 2);
            $table->date('expense_date');
            $table->text('description')->nullable();
            $table->timestamps();
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_expenses');
    }
};
