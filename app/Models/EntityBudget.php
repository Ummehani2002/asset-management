<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class EntityBudget extends Model
{
    protected $fillable = [
        'employee_id',
        'cost_head',
        'expense_type',
        'budget_2025',
       
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