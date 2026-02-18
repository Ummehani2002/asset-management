<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetExpense extends Model
{
    protected $fillable = [
        'entity_budget_id',
        'cost_head',
        'expense_amount',
        'expense_date',
        'description',
        'is_contracting',
        'amount_before_vat',
        'vat_percent',
        'vat_amount',
    ];

    protected $casts = [
        'is_contracting' => 'boolean',
    ];

    public function entityBudget()
    {
        return $this->belongsTo(EntityBudget::class);
    }
}