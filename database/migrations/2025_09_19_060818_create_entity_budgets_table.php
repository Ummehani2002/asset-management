<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 
public function up()
{
    Schema::create('entity_budgets', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('employee_id'); // Entity
        $table->string('cost_head');
        $table->string('expense_type');
        $table->string('category');
        $table->decimal('budget_2024', 15, 2)->nullable();
        $table->decimal('actual_2024', 15, 2)->nullable();
        $table->decimal('budget_2025', 15, 2)->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_budgets');
    }
};
