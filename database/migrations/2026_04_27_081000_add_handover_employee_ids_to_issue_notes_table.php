<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issue_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('issue_notes', 'received_by_employee_id')) {
                $table->unsignedBigInteger('received_by_employee_id')->nullable()->after('received_by_employee_name');
            }
            if (!Schema::hasColumn('issue_notes', 'returned_by_employee_id')) {
                $table->unsignedBigInteger('returned_by_employee_id')->nullable()->after('returned_by_employee_name');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('issue_notes')) {
            return;
        }

        Schema::table('issue_notes', function (Blueprint $table) {
            if (Schema::hasColumn('issue_notes', 'returned_by_employee_id')) {
                $table->dropColumn('returned_by_employee_id');
            }
            if (Schema::hasColumn('issue_notes', 'received_by_employee_id')) {
                $table->dropColumn('received_by_employee_id');
            }
        });
    }
};
