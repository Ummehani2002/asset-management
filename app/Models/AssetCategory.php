<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    protected $fillable = ['category_name'];

  
    public function assets()
{
    return $this->hasMany(Asset::class, 'asset_category_id');
}
public function brands()
{
    return $this->hasMany(Brand::class);
}
}


