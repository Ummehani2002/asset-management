<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetTransaction extends Model
{
    protected $fillable = [
        'transaction_type',  
        'asset_id',
        'employee_id',
        'project_name',
        'issue_date',
        'return_date',
        'receive_date',
        'delivery_date',
        'assigned_to',
        'assigned_to_type',
        'repair_type',
        'maintenance_notes',
        'remarks',
        'image_path',
        'assign_image',
        'return_image',
        'maintenance_image',
        'status',
        'location_id',
    ];
    // this is th easset image 

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

    public function maintenanceAssignments()
    {
        return $this->hasMany(MaintenanceAssignment::class);
    }
}
