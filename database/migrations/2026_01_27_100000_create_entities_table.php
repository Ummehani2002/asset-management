<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('entities')) {
            return;
        }

        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->unsignedBigInteger('asset_manager_id')->nullable();
            $table->timestamps();

            $table->foreign('asset_manager_id')->references('id')->on('employees')->nullOnDelete();
        });

        // Seed default entities so existing dropdowns keep working
        $defaults = [
            'proscape',
            'water in motion',
            'bioscape',
            'tanseeq realty',
            'transmech',
            'timbertech',
            'ventana',
            'garden center',
        ];
        foreach ($defaults as $name) {
            if (\DB::table('entities')->where('name', $name)->exists()) {
                continue;
            }
            \DB::table('entities')->insert([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('entities');
    }
};
