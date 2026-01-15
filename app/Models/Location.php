<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;   // ← MISSING

class Location extends Model
{
    protected $table = 'locations';

    protected $fillable = [
        'location_id',
        'location_name',
        'location_category',
        'location_entity',
        'address',
        'notes'
    ];

public function assets()
{
    return $this->hasMany(\App\Models\Asset::class, 'location_id', 'location_id');
}

}
