<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 64)->index(); // e.g. login, logout, create, update, delete, view
            $table->string('description')->nullable(); // human-readable: "Created asset ABC123"
            $table->string('subject_type', 128)->nullable()->index(); // e.g. App\Models\Asset
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable(); // extra data (sanitized)
            $table->string('url', 1024)->nullable();
            $table->string('method', 16)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
