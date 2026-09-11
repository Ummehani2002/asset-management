<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Allow employees without an Employee ID.
     */
    public function up(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasColumn('employees', 'employee_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_id', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('employees') || ! Schema::hasColumn('employees', 'employee_id')) {
            return;
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_id', 20)->nullable(false)->change();
        });
    }
};
