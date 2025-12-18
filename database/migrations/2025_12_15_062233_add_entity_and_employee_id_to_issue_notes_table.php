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
        Schema::table('issue_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('issue_notes', 'entity')) {
                $table->string('entity')->nullable()->after('department');
            }
            if (!Schema::hasColumn('issue_notes', 'employee_id')) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('id');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('issue_notes', function (Blueprint $table) {
            if (Schema::hasColumn('issue_notes', 'employee_id')) {
                $table->dropForeign(['employee_id']);
                $table->dropColumn('employee_id');
            }
            if (Schema::hasColumn('issue_notes', 'entity')) {
                $table->dropColumn('entity');
            }
        });
    }
};
