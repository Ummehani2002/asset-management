<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('it_consumable_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('it_consumable_id');
            $table->string('issue_to_name');
            $table->unsignedInteger('quantity');
            $table->date('issue_date');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('it_consumable_id')
                ->references('id')
                ->on('it_consumables')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_consumable_issues');
    }
};
