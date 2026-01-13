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
        Schema::create('time_managements', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->onDelete('set null');
            $table->string('employee_name')->nullable();
            $table->string('project_name')->nullable();
            $table->date('job_card_date')->nullable();
            $table->decimal('standard_man_hours', 8, 2)->nullable();
            $table->datetime('start_time')->nullable();
            $table->datetime('end_time')->nullable();
            $table->decimal('duration_hours', 8, 2)->nullable();
            $table->string('status')->nullable();
            $table->integer('delayed_days')->nullable();
            $table->text('delay_reason')->nullable();
            $table->decimal('performance_percent', 5, 2)->nullable();
            // last_delay_email_sent_at added in 2025_12_16_053345
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_managements');
    }
};
