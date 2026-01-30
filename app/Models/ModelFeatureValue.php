<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelFeatureValue extends Model
{
    protected $fillable = ['brand_model_id', 'category_feature_id', 'feature_value'];

    public function brandModel()
    {
        return $this->belongsTo(BrandModel::class, 'brand_model_id');
    }

    public function categoryFeature()
    {
        return $this->belongsTo(CategoryFeature::class, 'category_feature_id');
    }
}
