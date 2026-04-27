<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('issue_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('issue_notes', 'received_by_employee_name')) {
                $table->string('received_by_employee_name')->nullable()->after('software_installed');
            }
            if (!Schema::hasColumn('issue_notes', 'received_by_user_signature')) {
                $table->string('received_by_user_signature')->nullable()->after('received_by_employee_name');
            }
            if (!Schema::hasColumn('issue_notes', 'returned_by_employee_name')) {
                $table->string('returned_by_employee_name')->nullable()->after('received_by_user_signature');
            }
            if (!Schema::hasColumn('issue_notes', 'returned_by_user_signature')) {
                $table->string('returned_by_user_signature')->nullable()->after('returned_by_employee_name');
            }
            if (!Schema::hasColumn('issue_notes', 'data_backup')) {
                $table->string('data_backup')->nullable()->after('returned_by_user_signature');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('issue_notes')) {
            return;
        }

        Schema::table('issue_notes', function (Blueprint $table) {
            if (Schema::hasColumn('issue_notes', 'data_backup')) {
                $table->dropColumn('data_backup');
            }
            if (Schema::hasColumn('issue_notes', 'returned_by_user_signature')) {
                $table->dropColumn('returned_by_user_signature');
            }
            if (Schema::hasColumn('issue_notes', 'returned_by_employee_name')) {
                $table->dropColumn('returned_by_employee_name');
            }
            if (Schema::hasColumn('issue_notes', 'received_by_user_signature')) {
                $table->dropColumn('received_by_user_signature');
            }
            if (Schema::hasColumn('issue_notes', 'received_by_employee_name')) {
                $table->dropColumn('received_by_employee_name');
            }
        });
    }
};
