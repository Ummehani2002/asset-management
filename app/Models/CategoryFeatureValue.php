<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryFeatureValue extends Model
{
     protected $fillable = ['asset_id', 'category_feature_id', 'feature_value'];

    public function categoryFeature()
    {
        return $this->belongsTo(CategoryFeature::class);
    }
    public function feature()
{
    return $this->belongsTo(CategoryFeature::class, 'category_feature_id');
}

    /** Alias used in some views as $fv->value */
    public function getValueAttribute(): ?string
    {
        return $this->attributes['feature_value'] ?? null;
    }

}
