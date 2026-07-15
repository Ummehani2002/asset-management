<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pr_trackings')) {
            return;
        }

        Schema::table('pr_trackings', function (Blueprint $table) {
            if (!Schema::hasColumn('pr_trackings', 'approver_three_email')) {
                $table->string('approver_three_email', 255)->nullable()->after('approver_two_action_at');
            }
            if (!Schema::hasColumn('pr_trackings', 'approver_three_status')) {
                $table->string('approver_three_status', 30)->default('pending')->after('approver_three_email');
            }
            if (!Schema::hasColumn('pr_trackings', 'approver_three_action_at')) {
                $table->timestamp('approver_three_action_at')->nullable()->after('approver_three_status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('pr_trackings')) {
            return;
        }

        Schema::table('pr_trackings', function (Blueprint $table) {
            if (Schema::hasColumn('pr_trackings', 'approver_three_action_at')) {
                $table->dropColumn('approver_three_action_at');
            }
            if (Schema::hasColumn('pr_trackings', 'approver_three_status')) {
                $table->dropColumn('approver_three_status');
            }
            if (Schema::hasColumn('pr_trackings', 'approver_three_email')) {
                $table->dropColumn('approver_three_email');
            }
        });
    }
};
