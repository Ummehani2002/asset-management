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
        if (Schema::hasTable('asset_categories')) {
            return; // Table already exists, skip creation
        }

        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('asset_categories')) {
            return;
        }

        if (\DB::getDriverName() === 'mysql') {
            $tablesToCheck = ['brands', 'assets', 'category_features'];
            foreach ($tablesToCheck as $tableName) {
                if (Schema::hasTable($tableName)) {
                    try {
                        $foreignKeys = \DB::select("
                            SELECT CONSTRAINT_NAME 
                            FROM information_schema.KEY_COLUMN_USAGE 
                            WHERE TABLE_SCHEMA = DATABASE() 
                            AND TABLE_NAME = ? 
                            AND (COLUMN_NAME = 'asset_category_id' OR REFERENCED_TABLE_NAME = 'asset_categories')
                            AND REFERENCED_TABLE_NAME = 'asset_categories'
                        ", [$tableName]);
                        foreach ($foreignKeys as $fk) {
                            try {
                                \DB::statement("ALTER TABLE {$tableName} DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                            } catch (\Exception $e) {}
                        }
                    } catch (\Exception $e) {}
                }
            }
        }

        Schema::dropIfExists('asset_categories');
    }
};
