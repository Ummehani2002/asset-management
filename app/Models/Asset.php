<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
  protected $table = 'assets'; 
  protected $fillable = [
        'asset_id',
         'location_id',
        'asset_category_id',
        'brand_id',
        'purchase_date',
        'warranty_start',
        'expiry_date',
        'po_number',
        'serial_number',
        'invoice_path',
        'status',
    ];
    public function category()
    {
       return $this->belongsTo(\App\Models\AssetCategory::class, 'asset_category_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
    public function project() 
    {
    return $this->belongsTo(Project::class, 'project_id'); 
    }
  public function location()
{
    return $this->belongsTo(\App\Models\Location::class, 'location_id', 'location_id');
}

    public function features()
{
    return $this->belongsToMany(CategoryFeature::class, 'asset_feature_values', 'asset_id', 'feature_id')
        ->withPivot('value');
}
    public function brand()
{
    return $this->belongsTo(Brand::class);
}
    public function transactions()
{
    return $this->hasMany(AssetTransaction::class);
}
    public function latestTransaction()
{
    return $this->hasOne(\App\Models\AssetTransaction::class)->latestOfMany();
}


public function assetCategory()
{
    return $this->belongsTo(AssetCategory::class, 'asset_category_id');
}
public function featureValues()
{
    return $this->hasMany(CategoryFeatureValue::class, 'asset_id');
}





}
