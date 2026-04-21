<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nas_storages', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->string('location');
            $table->string('ip_address');
            $table->string('username');
            $table->string('password');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nas_storages');
    }
};
