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
            if (!Schema::hasColumn('internet_services', 'pr_number')) {
                // Only use 'after' if the column exists, otherwise add at the end
                if (Schema::hasColumn('internet_services', 'cost')) {
                    $table->string('pr_number')->nullable()->after('cost');
                } else {
                    $table->string('pr_number')->nullable();
                }
            }
            if (!Schema::hasColumn('internet_services', 'po_number')) {
                // Only use 'after' if pr_number exists, otherwise add at the end
                if (Schema::hasColumn('internet_services', 'pr_number')) {
                    $table->string('po_number')->nullable()->after('pr_number');
                } else {
                    $table->string('po_number')->nullable();
                }
            }
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
            if (Schema::hasColumn('internet_services', 'pr_number')) {
                $columnsToDrop[] = 'pr_number';
            }
            if (Schema::hasColumn('internet_services', 'po_number')) {
                $columnsToDrop[] = 'po_number';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
