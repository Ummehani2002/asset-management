<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Brand extends Model
{
    protected $fillable = ['name', 'asset_category_id'];

public function features()
{
    return $this->hasMany(CategoryFeature::class, 'brand_id');
}

public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }
}
