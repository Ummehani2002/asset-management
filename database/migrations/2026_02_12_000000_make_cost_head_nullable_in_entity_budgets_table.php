<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entity_budgets', function (Blueprint $table) {
            $table->string('cost_head')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('entity_budgets', function (Blueprint $table) {
            $table->string('cost_head')->nullable(false)->change();
        });
    }
};
