<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employees')) {
            return;
        }
        if (Schema::hasColumn('employees', 'designation')) {
            return;
        }
        Schema::table('employees', function (Blueprint $table) {
            $table->string('designation', 100)->nullable()->after('department_name');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'designation')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('designation');
            });
        }
    }
};
