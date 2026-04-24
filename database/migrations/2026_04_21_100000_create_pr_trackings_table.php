<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pr_trackings', function (Blueprint $table) {
            $table->id();
            $table->date('requisition_date');
            $table->string('requisition_number')->unique();
            $table->string('item_requested', 255);
            $table->date('requisition_received_date')->nullable();
            $table->string('requisition_status', 100)->nullable();
            $table->string('approved_request_status', 100)->nullable();
            $table->date('forwarded_to_purchase_date')->nullable();
            $table->text('comments')->nullable();

            $table->string('approval_status', 30)->default('draft');
            $table->string('approver_one_email', 255)->nullable();
            $table->string('approver_one_status', 30)->default('pending');
            $table->timestamp('approver_one_action_at')->nullable();
            $table->string('approver_two_email', 255)->nullable();
            $table->string('approver_two_status', 30)->default('pending');
            $table->timestamp('approver_two_action_at')->nullable();
            $table->timestamp('approval_requested_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pr_trackings');
    }
};

