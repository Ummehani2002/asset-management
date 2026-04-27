<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItConsumable extends Model
{
    protected $fillable = [
        'id_no',
        'tkt_ref_no',
        'item_description',
        'allocated_qty',
        'issued_date',
        'remarks',
    ];

    protected $casts = [
        'issued_date' => 'date',
    ];

    public function issues()
    {
        return $this->hasMany(ItConsumableIssue::class, 'it_consumable_id');
    }
}
