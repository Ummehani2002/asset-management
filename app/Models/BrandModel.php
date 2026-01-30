<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandModel extends Model
{
    protected $fillable = ['brand_id', 'model_number'];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function featureValues()
    {
        return $this->hasMany(ModelFeatureValue::class, 'brand_model_id');
    }
}
