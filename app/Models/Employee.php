<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $fillable = ['employee_id', 'name', 'email', 'phone', 'entity_name', 'department_name'];

 public function assets()
{
    return $this->hasManyThrough(Asset::class, AssetTransaction::class, 'employee_id', 'id', 'id', 'asset_id');
}


public function assetTransactions()
{
    return $this->hasMany(\App\Models\AssetTransaction::class);
}
}
