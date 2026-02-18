<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceApprovalRequest extends Model
{
    protected $fillable = [
        'asset_id',
        'requested_by_user_id',
        'assigned_to_employee_id',
        'status',
        'request_notes',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function requestedByUser()
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function assignedToEmployee()
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
