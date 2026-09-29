<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItConsumableIssue extends Model
{
    protected $fillable = [
        'it_consumable_id',
        'employee_id',
        'issue_to_name',
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

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
