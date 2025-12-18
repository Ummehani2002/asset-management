<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetExpense extends Model
{
    protected $fillable = [
        'entity_budget_id',
        'expense_amount',
        'expense_date',
        'description',
        
    ];

    public function entityBudget()
    {
        return $this->belongsTo(EntityBudget::class);
    }
}