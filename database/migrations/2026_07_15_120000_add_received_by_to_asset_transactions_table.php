<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('asset_transactions') || Schema::hasColumn('asset_transactions', 'received_by')) {
            return;
        }

        Schema::table('asset_transactions', function (Blueprint $table) {
            $table->string('received_by')->nullable()->after('receive_date');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('asset_transactions') || !Schema::hasColumn('asset_transactions', 'received_by')) {
            return;
        }

        Schema::table('asset_transactions', function (Blueprint $table) {
            $table->dropColumn('received_by');
        });
    }
};
