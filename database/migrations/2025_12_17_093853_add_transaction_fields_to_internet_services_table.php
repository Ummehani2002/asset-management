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
        // Check if table exists before trying to modify it
        if (!Schema::hasTable('internet_services')) {
            return;
        }
        
        Schema::table('internet_services', function (Blueprint $table) {
            // Only drop columns if they exist
            $columnsToDrop = [];
            if (Schema::hasColumn('internet_services', 'transaction_type')) {
                $columnsToDrop[] = 'transaction_type';
            }
            if (Schema::hasColumn('internet_services', 'pm_contact_number')) {
                $columnsToDrop[] = 'pm_contact_number';
            }
            if (Schema::hasColumn('internet_services', 'document_controller')) {
                $columnsToDrop[] = 'document_controller';
            }
            if (Schema::hasColumn('internet_services', 'document_controller_number')) {
                $columnsToDrop[] = 'document_controller_number';
            }
            if (Schema::hasColumn('internet_services', 'mrc')) {
                $columnsToDrop[] = 'mrc';
            }
            if (Schema::hasColumn('internet_services', 'cost')) {
                $columnsToDrop[] = 'cost';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
