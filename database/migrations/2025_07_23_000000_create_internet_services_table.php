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
        Schema::create('internet_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects')->onDelete('set null');
            $table->string('project_name')->nullable();
            $table->string('entity')->nullable();
            $table->string('service_type')->nullable();
            $table->string('account_number')->nullable();
            $table->date('service_start_date')->nullable();
            $table->date('service_end_date')->nullable();
            $table->string('person_in_charge')->nullable(); // String field, person_in_charge_id added later
            $table->text('contact_details')->nullable();
            $table->string('project_manager')->nullable(); // String field, project_manager_id added later
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internet_services');
    }
};
