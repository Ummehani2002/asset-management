<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EntityBudget;
use App\Models\BudgetExpense;
use App\Models\Employee;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class BudgetExpenseController extends Controller
{
  public function create()
{
    try {
        // Initialize defaults
        $entities = collect([]);
        $costHeads = [];
        $expenseTypes = ['Maintenance', 'Capex Software', 'Subscription'];
        
        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            Log::error('BudgetExpense create: Database connection failed: ' . $e->getMessage());
            return view('budget_expenses.create', compact('entities', 'costHeads', 'expenseTypes'))
                ->with('error', 'Database connection failed. Please check your database credentials.');
        }
        
        // Get unique entities - one employee per unique entity_name
        if (Schema::hasTable('employees')) {
            try {
                $uniqueEntityNames = Employee::whereNotNull('entity_name')
                    ->where('entity_name', '!=', '')
                    ->distinct()
                    ->pluck('entity_name')
                    ->toArray();
                
                // Then get the first employee for each unique entity_name
                $entities = collect($uniqueEntityNames)->map(function($entityName) {
                    return Employee::where('entity_name', $entityName)->first();
                })->filter()->values();
                
                if (!$entities instanceof \Illuminate\Support\Collection) {
                    $entities = collect($entities);
                }
            } catch (\Exception $e) {
                Log::warning('Error loading entities: ' . $e->getMessage());
            }
        }
        
        // Get unique cost heads from existing entity budgets
        if (Schema::hasTable('entity_budgets')) {
            try {
                $costHeads = EntityBudget::distinct()->pluck('cost_head')->filter()->values()->toArray();
            } catch (\Exception $e) {
                Log::warning('Error loading cost heads: ' . $e->getMessage());
            }
        }
        
        return view('budget_expenses.create', compact('entities', 'costHeads', 'expenseTypes'));
    } catch (\Throwable $e) {
        Log::error('BudgetExpense create fatal error: ' . $e->getMessage());
        $entities = collect([]);
        $costHeads = [];
        $expenseTypes = ['Maintenance', 'Capex Software', 'Subscription'];
        return view('budget_expenses.create', compact('entities', 'costHeads', 'expenseTypes'))
            ->with('error', 'An error occurred. Please check logs.');
    }
}

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'entity_budget_id' => 'required|exists:entity_budgets,id',
                'expense_amount' => 'required|numeric|min:0',
                'expense_date' => 'required|date',
                'description' => 'nullable|string'
            ]);

            // Check available balance
            $budget = EntityBudget::find($validated['entity_budget_id']);
            $totalExpenses = BudgetExpense::where('entity_budget_id', $budget->id)
                ->sum('expense_amount');
            
            if (($totalExpenses + $validated['expense_amount']) > $budget->budget_2025) {
                throw new \Exception('Insufficient budget balance');
            }

            $expense = BudgetExpense::create($validated);

            // Get updated budget details
            return $this->getBudgetDetails($request);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function getBudgetDetails(Request $request)
    {
        try {
            // Validate required parameters
            if (!$request->has('entity_id') || !$request->has('cost_head') || !$request->has('expense_type')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameters: entity_id, cost_head, or expense_type'
                ]);
            }
            
            // Get the selected employee to find their entity_name
            $selectedEmployee = Employee::find($request->entity_id);
            
            if (!$selectedEmployee) {
                Log::warning('BudgetExpense getBudgetDetails: Employee not found with ID: ' . $request->entity_id);
                return response()->json([
                    'success' => false,
                    'message' => 'Employee not found'
                ]);
            }
            
            Log::info('BudgetExpense getBudgetDetails: Searching for budget', [
                'entity_id' => $request->entity_id,
                'entity_name' => $selectedEmployee->entity_name,
                'cost_head' => $request->cost_head,
                'expense_type' => $request->expense_type
            ]);
            
            // Find budget by entity_name (to include all employees with same entity)
            // But since budgets are tied to specific employees, we'll find the first budget
            // for any employee with this entity_name matching the cost_head and expense_type
            // Use case-insensitive comparison for cost_head
            $budget = EntityBudget::with('employee')
                ->whereHas('employee', function($q) use ($selectedEmployee) {
                    $q->where('entity_name', $selectedEmployee->entity_name);
                })
                ->whereRaw('LOWER(cost_head) = LOWER(?)', [$request->cost_head])
                ->where('expense_type', $request->expense_type)
                ->first();
            
            if (!$budget) {
                Log::warning('BudgetExpense getBudgetDetails: No budget found', [
                    'entity_name' => $selectedEmployee->entity_name,
                    'cost_head' => $request->cost_head,
                    'expense_type' => $request->expense_type
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'No budget found for selected criteria'
                ]);
            }

            // Budget found, get expenses
            $expenses = BudgetExpense::where('entity_budget_id', $budget->id)
                ->orderBy('expense_date', 'desc')
                ->get();
                
            $totalExpenses = $expenses->sum('expense_amount');

            $formattedExpenses = $expenses->map(function ($expense) use ($budget) {
                $balanceAfter = $budget->budget_2025 - BudgetExpense::where('entity_budget_id', $budget->id)
                    ->where('created_at', '<=', $expense->created_at)
                    ->sum('expense_amount');

                return [
                    'expense_date' => date('Y-m-d', strtotime($expense->expense_date)),
                    'expense_amount' => number_format($expense->expense_amount, 2),
                    'description' => $expense->description ?: '-',
                    'entity_name' => $budget->employee->entity_name ?? 'N/A',
                    'cost_head' => ucfirst($budget->cost_head),
                    'expense_type' => $budget->expense_type,
                    'balance_after' => number_format($balanceAfter, 2)
                ];
            });

            return response()->json([
                'success' => true,
                'entity_budget_id' => $budget->id,
                'entity_name' => $budget->employee->entity_name ?? 'N/A',
                'cost_head' => ucfirst($budget->cost_head),
                'expense_type' => $budget->expense_type,
                'budget_amount' => number_format($budget->budget_2025, 2),
                'total_expenses' => number_format($totalExpenses, 2),
                'available_balance' => number_format($budget->budget_2025 - $totalExpenses, 2),
                'expenses' => $formattedExpenses
            ]);

        } catch (\Exception $e) {
            Log::error('BudgetExpense getBudgetDetails error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving budget details: ' . $e->getMessage()
            ]);
        }
    }
}