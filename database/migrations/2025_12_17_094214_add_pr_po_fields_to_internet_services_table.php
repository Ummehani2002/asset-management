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
        Schema::table('internet_services', function (Blueprint $table) {
            $table->string('pr_number')->nullable()->after('cost');
            $table->string('po_number')->nullable()->after('pr_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internet_services', function (Blueprint $table) {
            $table->dropColumn(['pr_number', 'po_number']);
        });
    }
};
