<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('maintenance_assignments')) {
            return;
        }

        $useIntegerForAsset = false;
        if (Schema::hasTable('assets')) {
            $t = \DB::select("SHOW COLUMNS FROM assets WHERE Field = 'id'");
            if (!empty($t) && str_contains(strtolower($t[0]->Type ?? ''), 'int') && !str_contains(strtolower($t[0]->Type ?? ''), 'bigint')) {
                $useIntegerForAsset = true;
            }
        }

        Schema::create('maintenance_assignments', function (Blueprint $table) use ($useIntegerForAsset) {
            $table->id();
            $table->unsignedBigInteger('asset_transaction_id');
            $table->{$useIntegerForAsset ? 'unsignedInteger' : 'unsignedBigInteger'}('asset_id');
            $table->unsignedBigInteger('assigned_by_employee_id');
            $table->unsignedBigInteger('assigned_to_employee_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });

        try {
            Schema::table('maintenance_assignments', function (Blueprint $table) {
                $table->foreign('asset_transaction_id')->references('id')->on('asset_transactions')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('maintenance_assignments', function (Blueprint $table) {
                $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('maintenance_assignments', function (Blueprint $table) {
                $table->foreign('assigned_by_employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
        try {
            Schema::table('maintenance_assignments', function (Blueprint $table) {
                $table->foreign('assigned_to_employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        } catch (\Exception $e) {}
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_assignments');
    }
};
