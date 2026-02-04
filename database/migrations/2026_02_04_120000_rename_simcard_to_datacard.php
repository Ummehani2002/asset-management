<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('simcard_transactions')) {
            Schema::rename('simcard_transactions', 'datacard_transactions');

            Schema::table('datacard_transactions', function (Blueprint $table) {
                $table->renameColumn('simcard_number', 'datacard_number');
            });
        } elseif (!Schema::hasTable('datacard_transactions')) {
            // Fresh install: create datacard_transactions table
            Schema::create('datacard_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_type');
                $table->string('datacard_number', 100);
                $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
                $table->string('project_name')->nullable();
                $table->string('entity')->nullable();
                $table->decimal('mrc', 10, 2)->nullable();
                $table->date('issue_date')->nullable();
                $table->date('return_date')->nullable();
                $table->string('pm_dc', 100)->nullable();
                $table->timestamps();
            });
        }

        // Update internet_services: simcard -> datacard
        if (Schema::hasTable('internet_services')) {
            DB::table('internet_services')
                ->where('service_type', 'simcard')
                ->update(['service_type' => 'datacard']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('datacard_transactions')) {
            Schema::table('datacard_transactions', function (Blueprint $table) {
                $table->renameColumn('datacard_number', 'simcard_number');
            });

            Schema::rename('datacard_transactions', 'simcard_transactions');
        }

        if (Schema::hasTable('internet_services')) {
            DB::table('internet_services')
                ->where('service_type', 'datacard')
                ->update(['service_type' => 'simcard']);
        }
    }
};
