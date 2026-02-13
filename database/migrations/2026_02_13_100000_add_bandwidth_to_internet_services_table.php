<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add bandwidth column to internet_services (used by app but missing in production).
     */
    public function up(): void
    {
        if (!Schema::hasTable('internet_services')) {
            return;
        }
        if (Schema::hasColumn('internet_services', 'bandwidth')) {
            return;
        }
        Schema::table('internet_services', function (Blueprint $table) {
            $table->string('bandwidth', 100)->nullable()->after('service_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('internet_services') && Schema::hasColumn('internet_services', 'bandwidth')) {
            Schema::table('internet_services', function (Blueprint $table) {
                $table->dropColumn('bandwidth');
            });
        }
    }
};
