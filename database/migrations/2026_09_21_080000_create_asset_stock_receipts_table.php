<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asset_stock_receipts')) {
            return;
        }

        Schema::create('asset_stock_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_category_id');
            $table->unsignedInteger('quantity');
            $table->date('received_date');
            $table->string('remarks')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index('asset_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_stock_receipts');
    }
};
