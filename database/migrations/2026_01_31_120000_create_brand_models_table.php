<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('brand_models')) {
            return;
        }

        $useIntegerForBrand = false;
        if (Schema::hasTable('brands')) {
            $t = \DB::select("SHOW COLUMNS FROM brands WHERE Field = 'id'");
            if (!empty($t) && str_contains(strtolower($t[0]->Type), 'int') && !str_contains(strtolower($t[0]->Type), 'bigint')) {
                $useIntegerForBrand = true;
            }
        }

        Schema::create('brand_models', function (Blueprint $table) use ($useIntegerForBrand) {
            $table->id();
            if ($useIntegerForBrand) {
                $table->unsignedInteger('brand_id');
            } else {
                $table->unsignedBigInteger('brand_id');
            }
            $table->string('model_number');
            $table->timestamps();
        });

        if (Schema::hasTable('brands')) {
            try {
                Schema::table('brand_models', function (Blueprint $table) {
                    $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // ignore
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_models');
    }
};
