<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EntityBudget extends Model
{
    protected $fillable = [
        'employee_id',
        'cost_head',
        'expense_type',
        'category',
        'budget_2024',
        'budget_2025',
        'budget_2026',
        'budget_2027',
        'budget_2028',
        'budget_2029',
        'budget_2030',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
      public function expenses()
    {
        return $this->hasMany(BudgetExpense::class, 'entity_budget_id');
    }
}