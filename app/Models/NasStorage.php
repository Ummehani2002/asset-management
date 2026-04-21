<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NasStorage extends Model
{
    protected $fillable = [
        'site_name',
        'location',
        'ip_address',
        'username',
        'password',
    ];
}
