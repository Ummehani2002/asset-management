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
            if (!Schema::hasColumn('internet_services', 'person_in_charge_id')) {
                $table->unsignedBigInteger('person_in_charge_id')->nullable()->after('service_end_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internet_services', function (Blueprint $table) {
            if (Schema::hasColumn('internet_services', 'person_in_charge_id')) {
                $table->dropColumn('person_in_charge_id');
            }
        });
    }
};
