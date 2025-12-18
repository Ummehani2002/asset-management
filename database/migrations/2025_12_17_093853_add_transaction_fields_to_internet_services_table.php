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
            $table->string('transaction_type')->nullable()->after('service_type');
            $table->string('pm_contact_number')->nullable()->after('project_manager');
            $table->string('document_controller')->nullable()->after('pm_contact_number');
            $table->string('document_controller_number')->nullable()->after('document_controller');
            $table->decimal('mrc', 10, 2)->nullable()->after('document_controller_number');
            $table->decimal('cost', 10, 2)->nullable()->after('mrc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('internet_services', function (Blueprint $table) {
            $table->dropColumn([
                'transaction_type',
                'pm_contact_number',
                'document_controller',
                'document_controller_number',
                'mrc',
                'cost'
            ]);
        });
    }
};
