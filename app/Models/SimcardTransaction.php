<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimcardTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_type',
        'simcard_number',
        'project_id',
        'project_name',
        'entity',
        'mrc',
        'issue_date',
        'return_date',
        'pm_dc',
    ];

    // Optional: relationships
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    // Example static helpers
    public static function getAvailableSimcards()
    {
        return self::where('transaction_type', 'assign')
            ->whereNull('return_date')
            ->get();
    }

    public static function getAssignedSimcards()
    {
        return self::where('transaction_type', 'assign')
            ->whereNull('return_date')
            ->get();
    }
}
