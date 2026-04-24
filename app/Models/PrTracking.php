<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrTracking extends Model
{
    protected $fillable = [
        'requisition_date',
        'requisition_number',
        'item_requested',
        'requisition_received_date',
        'requisition_status',
        'approved_request_status',
        'forwarded_to_purchase_date',
        'comments',
        'approval_status',
        'approver_one_email',
        'approver_one_status',
        'approver_one_action_at',
        'approver_two_email',
        'approver_two_status',
        'approver_two_action_at',
        'approval_requested_at',
    ];

    protected $casts = [
        'requisition_date' => 'date',
        'requisition_received_date' => 'date',
        'forwarded_to_purchase_date' => 'date',
        'approver_one_action_at' => 'datetime',
        'approver_two_action_at' => 'datetime',
        'approval_requested_at' => 'datetime',
    ];
}

