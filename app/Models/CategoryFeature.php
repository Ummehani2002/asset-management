<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class CategoryFeature extends Model
{
    protected $fillable = [
        'asset_category_id',
        'brand_id',
        'feature_name',
        'sub_fields',
    ];

    protected $casts = [
        'sub_fields' => 'array',
    ];

    
    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
}
