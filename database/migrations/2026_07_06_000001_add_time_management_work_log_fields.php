<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_managements', function (Blueprint $table) {
            if (! Schema::hasColumn('time_managements', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('employee_id');
            }
            if (! Schema::hasColumn('time_managements', 'category')) {
                $table->string('category')->default('End User Support')->after('ticket_number');
            }
            if (! Schema::hasColumn('time_managements', 'task_description')) {
                $table->text('task_description')->nullable()->after('category');
            }
            if (! Schema::hasColumn('time_managements', 'site_location')) {
                $table->string('site_location')->nullable()->after('task_description');
            }
            if (! Schema::hasColumn('time_managements', 'action_taken')) {
                $table->text('action_taken')->nullable()->after('duration_hours');
            }
            if (! Schema::hasColumn('time_managements', 'remarks')) {
                $table->text('remarks')->nullable()->after('action_taken');
            }
            if (! Schema::hasColumn('time_managements', 'overtime_hours')) {
                $table->decimal('overtime_hours', 8, 2)->default(0)->after('duration_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('time_managements', function (Blueprint $table) {
            $columns = ['user_id', 'category', 'task_description', 'site_location', 'action_taken', 'remarks', 'overtime_hours'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('time_managements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
