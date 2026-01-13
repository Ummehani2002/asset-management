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
        Schema::table('time_managements', function (Blueprint $table) {
            $table->timestamp('last_delay_email_sent_at')->nullable()->after('delay_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_managements', function (Blueprint $table) {
            if (Schema::hasColumn('time_managements', 'last_delay_email_sent_at')) {
                $table->dropColumn('last_delay_email_sent_at');
            }
        });
    }
};
