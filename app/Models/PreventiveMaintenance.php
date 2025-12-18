<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreventiveMaintenance extends Model
{
    protected $fillable = [
        'asset_id',
        
        'maintenance_date',
        'maintenance_description',
        'next_maintenance_date',
    ];

    protected $casts = [
        'maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}