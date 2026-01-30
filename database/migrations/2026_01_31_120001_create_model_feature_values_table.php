<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('model_feature_values')) {
            return;
        }

        $useIntegerForModel = false;
        $useIntegerForFeature = false;
        if (Schema::hasTable('brand_models')) {
            $t = \DB::select("SHOW COLUMNS FROM brand_models WHERE Field = 'id'");
            if (!empty($t) && str_contains(strtolower($t[0]->Type), 'int') && !str_contains(strtolower($t[0]->Type), 'bigint')) {
                $useIntegerForModel = true;
            }
        }
        if (Schema::hasTable('category_features')) {
            $t = \DB::select("SHOW COLUMNS FROM category_features WHERE Field = 'id'");
            if (!empty($t) && str_contains(strtolower($t[0]->Type), 'int') && !str_contains(strtolower($t[0]->Type), 'bigint')) {
                $useIntegerForFeature = true;
            }
        }

        Schema::create('model_feature_values', function (Blueprint $table) use ($useIntegerForModel, $useIntegerForFeature) {
            $table->id();
            if ($useIntegerForModel) {
                $table->unsignedInteger('brand_model_id');
            } else {
                $table->unsignedBigInteger('brand_model_id');
            }
            if ($useIntegerForFeature) {
                $table->unsignedInteger('category_feature_id');
            } else {
                $table->unsignedBigInteger('category_feature_id');
            }
            $table->text('feature_value')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('brand_models')) {
            try {
                Schema::table('model_feature_values', function (Blueprint $table) {
                    $table->foreign('brand_model_id')->references('id')->on('brand_models')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // ignore
            }
        }
        if (Schema::hasTable('category_features')) {
            try {
                Schema::table('model_feature_values', function (Blueprint $table) {
                    $table->foreign('category_feature_id')->references('id')->on('category_features')->onDelete('cascade');
                });
            } catch (\Exception $e) {
                // ignore
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('model_feature_values');
    }
};
