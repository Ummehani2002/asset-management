<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('work_tickets')) {
            Schema::create('work_tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ticket_number', 50);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->string('employee_name')->nullable();
                $table->string('category')->default('End User Support');
                $table->string('task_description', 255);
                $table->string('site_location', 255);
                $table->string('status')->default('pending');
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['ticket_number', 'status']);
            });
        }

        if (Schema::hasTable('time_managements') && ! Schema::hasColumn('time_managements', 'work_ticket_id')) {
            Schema::table('time_managements', function (Blueprint $table) {
                $table->unsignedBigInteger('work_ticket_id')->nullable()->after('ticket_number');
                $table->index('work_ticket_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('time_managements') && Schema::hasColumn('time_managements', 'work_ticket_id')) {
            Schema::table('time_managements', function (Blueprint $table) {
                $table->dropIndex(['work_ticket_id']);
                $table->dropColumn('work_ticket_id');
            });
        }

        Schema::dropIfExists('work_tickets');
    }
};
