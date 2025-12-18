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
        Schema::table('asset_transactions', function (Blueprint $table) {
            // Add image fields for different transaction types
            if (!Schema::hasColumn('asset_transactions', 'assign_image')) {
                $table->string('assign_image')->nullable()->after('image_path');
            }
            if (!Schema::hasColumn('asset_transactions', 'return_image')) {
                $table->string('return_image')->nullable()->after('assign_image');
            }
            if (!Schema::hasColumn('asset_transactions', 'maintenance_image')) {
                $table->string('maintenance_image')->nullable()->after('return_image');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('asset_transactions', 'assign_image')) {
                $table->dropColumn('assign_image');
            }
            if (Schema::hasColumn('asset_transactions', 'return_image')) {
                $table->dropColumn('return_image');
            }
            if (Schema::hasColumn('asset_transactions', 'maintenance_image')) {
                $table->dropColumn('maintenance_image');
            }
        });
    }
};
