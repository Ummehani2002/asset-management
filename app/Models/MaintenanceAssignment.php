<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceAssignment extends Model
{
    protected $fillable = [
        'asset_transaction_id',
        'asset_id',
        'assigned_by_employee_id',
        'assigned_to_employee_id',
        'status',
        'notes',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function assetTransaction()
    {
        return $this->belongsTo(AssetTransaction::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(Employee::class, 'assigned_by_employee_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(Employee::class, 'assigned_to_employee_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
