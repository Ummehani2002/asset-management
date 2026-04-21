<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItConsumable extends Model
{
    protected $fillable = [
        'id_no',
        'item_description',
        'issued_date',
        'remarks',
    ];

    protected $casts = [
        'issued_date' => 'date',
    ];
}
