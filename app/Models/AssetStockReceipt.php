<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetStockReceipt extends Model
{
    protected $fillable = [
        'asset_category_id',
        'quantity',
        'received_date',
        'remarks',
        'user_id',
    ];

    protected $casts = [
        'received_date' => 'date',
        'quantity' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
