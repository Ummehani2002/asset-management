<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('it_consumables', function (Blueprint $table) {
            $table->id();
            $table->string('id_no')->unique();
            $table->string('item_description', 500);
            $table->date('issued_date');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_consumables');
    }
};
