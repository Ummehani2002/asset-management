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
        if (Schema::hasTable('locations')) {
            return; // Table already exists, skip creation
        }

        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('location_id')->unique();
            $table->string('location_name');
            $table->string('location_category')->nullable();
            $table->string('location_entity');
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('locations')) {
            return;
        }
        
        // Drop foreign keys from dependent tables first
        if (Schema::hasTable('asset_transactions')) {
            try {
                $foreignKeys = \DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'asset_transactions' 
                    AND COLUMN_NAME = 'location_id'
                    AND REFERENCED_TABLE_NAME = 'locations'
                ");
                
                foreach ($foreignKeys as $fk) {
                    try {
                        \DB::statement("ALTER TABLE asset_transactions DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                    } catch (\Exception $e) {
                        // Foreign key might not exist, continue
                    }
                }
            } catch (\Exception $e) {
                // Continue
            }
        }
        
        Schema::dropIfExists('locations');
    }
};
