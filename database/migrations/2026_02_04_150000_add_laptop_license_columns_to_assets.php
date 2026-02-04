<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add laptop-specific manual entry fields to assets table.
     * These are entered manually in Asset Master when adding/editing laptop assets.
     */
    public function up(): void
    {
        if (Schema::hasTable('assets')) {
            Schema::table('assets', function (Blueprint $table) {
                if (!Schema::hasColumn('assets', 'os_license_key')) {
                    $table->string('os_license_key')->nullable()->after('value');
                }
                if (!Schema::hasColumn('assets', 'antivirus_license_version')) {
                    $table->string('antivirus_license_version')->nullable()->after('os_license_key');
                }
                if (!Schema::hasColumn('assets', 'patch_management_software')) {
                    $table->string('patch_management_software')->nullable()->after('antivirus_license_version');
                }
                if (!Schema::hasColumn('assets', 'autocad_license_key')) {
                    $table->string('autocad_license_key')->nullable()->after('patch_management_software');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('assets')) {
            Schema::table('assets', function (Blueprint $table) {
                if (Schema::hasColumn('assets', 'os_license_key')) {
                    $table->dropColumn('os_license_key');
                }
                if (Schema::hasColumn('assets', 'antivirus_license_version')) {
                    $table->dropColumn('antivirus_license_version');
                }
                if (Schema::hasColumn('assets', 'patch_management_software')) {
                    $table->dropColumn('patch_management_software');
                }
                if (Schema::hasColumn('assets', 'autocad_license_key')) {
                    $table->dropColumn('autocad_license_key');
                }
            });
        }
    }
};
