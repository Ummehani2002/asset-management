<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $useIntegerForAsset = false;
        if (Schema::hasTable('assets')) {
            $t = \DB::select("SHOW COLUMNS FROM assets WHERE Field = 'id'");
            $type = isset($t[0]) ? (($t[0]->Type ?? $t[0]->type ?? '') . '') : '';
            $typeLower = strtolower($type);
            if ($typeLower !== '' && str_contains($typeLower, 'int') && !str_contains($typeLower, 'bigint')) {
                $useIntegerForAsset = true;
            }
        }

        if (!Schema::hasTable('maintenance_approval_requests')) {
        Schema::create('maintenance_approval_requests', function (Blueprint $table) use ($useIntegerForAsset) {
            $table->id();
            $table->{$useIntegerForAsset ? 'unsignedInteger' : 'unsignedBigInteger'}('asset_id');
            $table->unsignedBigInteger('requested_by_user_id');
            $table->unsignedBigInteger('assigned_to_employee_id')->comment('Asset manager who must approve');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected
            $table->text('request_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });
        }

        try {
            Schema::table('maintenance_approval_requests', function (Blueprint $table) {
                $table->foreign('asset_id')->references('id')->on('assets')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Column type may not match assets.id; ignore
        }
        try {
            Schema::table('maintenance_approval_requests', function (Blueprint $table) {
                $table->foreign('requested_by_user_id')->references('id')->on('users')->onDelete('cascade');
            });
        } catch (\Exception $e) {
        }
        try {
            Schema::table('maintenance_approval_requests', function (Blueprint $table) {
                $table->foreign('assigned_to_employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        } catch (\Exception $e) {
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_approval_requests');
    }
};
