<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    protected $fillable = ['name', 'asset_manager_id'];

    public function assetManager()
    {
        return $this->belongsTo(Employee::class, 'asset_manager_id');
    }
}
