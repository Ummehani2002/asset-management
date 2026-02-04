<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'vendor_name')) {
                $table->string('vendor_name')->nullable()->after('po_number');
            }
            if (!Schema::hasColumn('assets', 'value')) {
                $table->decimal('value', 12, 2)->nullable()->after('vendor_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'vendor_name')) {
                $table->dropColumn('vendor_name');
            }
            if (Schema::hasColumn('assets', 'value')) {
                $table->dropColumn('value');
            }
        });
    }
};
