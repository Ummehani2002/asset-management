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
        Schema::create('issue_notes', function (Blueprint $table) {
            $table->id();
            // employee_id, entity added in 2025_12_15_062233
            $table->string('department')->nullable();
            $table->string('location')->nullable();
            $table->string('system_code')->nullable();
            $table->string('printer_code')->nullable();
            $table->text('software_installed')->nullable();
            $table->date('issued_date')->nullable();
            // return_date, note_type, issue_note_id added in 2025_12_17_105952
            $table->json('items')->nullable();
            $table->string('user_signature')->nullable();
            $table->string('manager_signature')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issue_notes');
    }
};
