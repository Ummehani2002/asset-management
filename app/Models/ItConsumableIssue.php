<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItConsumableIssue extends Model
{
    protected $fillable = [
        'it_consumable_id',
        'issue_to_name',
        'tkt_ref_no',
        'quantity',
        'issue_date',
        'remarks',
    ];

    protected $casts = [
        'issue_date' => 'date',
    ];

    public function consumable()
    {
        return $this->belongsTo(ItConsumable::class, 'it_consumable_id');
    }
}
