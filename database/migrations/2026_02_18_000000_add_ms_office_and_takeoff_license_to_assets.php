<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assets')) {
            return;
        }
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'ms_office_license_key')) {
                $table->string('ms_office_license_key')->nullable()->after('os_license_key');
            }
            if (!Schema::hasColumn('assets', 'on_screen_takeoff_key')) {
                $table->string('on_screen_takeoff_key')->nullable()->after('ms_office_license_key');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('assets')) {
            return;
        }
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'ms_office_license_key')) {
                $table->dropColumn('ms_office_license_key');
            }
            if (Schema::hasColumn('assets', 'on_screen_takeoff_key')) {
                $table->dropColumn('on_screen_takeoff_key');
            }
        });
    }
};
