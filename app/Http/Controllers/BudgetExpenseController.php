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
        $expenseTypes = ['Maintenance', 'Capex Software', 'Capex Hardware', 'Subscription'];
        
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
        
        // Cost heads: predefined list (from cost heads master) + any from existing entity budgets
        $costHeadsList = \App\Http\Controllers\EntityBudgetController::getCostHeadsList();
        $predefinedNames = array_column($costHeadsList, 'name');
        $costHeadsWithTypes = [];
        foreach ($costHeadsList as $item) {
            $costHeadsWithTypes[$item['name']] = $item['expense_type'];
        }
        if (Schema::hasTable('entity_budgets')) {
            try {
                $fromDb = EntityBudget::distinct()->pluck('cost_head')->filter()->values()->toArray();
                $costHeads = array_values(array_unique(array_merge($predefinedNames, $fromDb)));
                sort($costHeads);
            } catch (\Exception $e) {
                Log::warning('Error loading cost heads: ' . $e->getMessage());
                $costHeads = $predefinedNames;
            }
        } else {
            $costHeads = $predefinedNames;
        }
        
        return view('budget_expenses.create', compact('entities', 'costHeads', 'costHeadsWithTypes', 'expenseTypes'));
    } catch (\Throwable $e) {
        Log::error('BudgetExpense create fatal error: ' . $e->getMessage());
        $entities = collect([]);
        $costHeadsList = \App\Http\Controllers\EntityBudgetController::getCostHeadsList();
        $costHeads = array_column($costHeadsList, 'name');
        $costHeadsWithTypes = [];
        foreach ($costHeadsList as $item) {
            $costHeadsWithTypes[$item['name']] = $item['expense_type'];
        }
        $expenseTypes = ['Maintenance', 'Capex Software', 'Capex Hardware', 'Subscription'];
        return view('budget_expenses.create', compact('entities', 'costHeads', 'costHeadsWithTypes', 'expenseTypes'))
            ->with('error', 'An error occurred. Please check logs.');
    }
}

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'entity_budget_id' => 'required|exists:entity_budgets,id',
                'cost_head' => 'nullable|string',
                'expense_amount' => 'required|numeric|min:0',
                'expense_date' => 'required|date',
                'description' => 'nullable|string'
            ]);

            // Check available balance (all expenses under this budget)
            $budget = EntityBudget::find($validated['entity_budget_id']);
            $totalExpenses = BudgetExpense::where('entity_budget_id', $budget->id)
                ->sum('expense_amount');
            
            $currentYear = (int) date('Y');
            $yearColumn = 'budget_' . $currentYear;
            $budgetAmount = Schema::hasColumn('entity_budgets', $yearColumn) ? ($budget->$yearColumn ?? 0) : 0;
            if (($totalExpenses + $validated['expense_amount']) > $budgetAmount) {
                throw new \Exception('Insufficient budget balance');
            }

            $expense = BudgetExpense::create($validated);

            // Get updated budget details and add print URL for the saved expense
            $response = $this->getBudgetDetails($request);
            $data = $response->getData(true);
            if (is_array($data)) {
                $data['saved_expense_id'] = $expense->id;
                $data['print_url'] = route('budget-expenses.print', $expense->id);
                return response()->json($data);
            }
            return $response;

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
            if (!$request->has('entity_id') || !$request->has('expense_type')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required parameters: entity_id or expense_type'
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
            // Get all employee IDs with this entity_name for direct query
            $employeeIds = Employee::where('entity_name', $selectedEmployee->entity_name)
                ->pluck('id')
                ->toArray();
            
            Log::info('BudgetExpense getBudgetDetails: Employee IDs for entity', [
                'entity_name' => $selectedEmployee->entity_name,
                'employee_ids' => $employeeIds,
                'cost_head' => $request->cost_head,
                'expense_type' => $request->expense_type
            ]);
            
            if (empty($employeeIds)) {
                Log::warning('BudgetExpense getBudgetDetails: No employees found for entity_name: ' . $selectedEmployee->entity_name);
                return response()->json([
                    'success' => false,
                    'message' => 'No employees found for selected entity'
                ]);
            }
            
            // Budget is maintained by entity + expense type only. Prefer row with null cost_head.
            $budget = EntityBudget::with('employee')
                ->whereIn('employee_id', $employeeIds)
                ->where('expense_type', $request->expense_type)
                ->orderByRaw('CASE WHEN cost_head IS NULL THEN 0 ELSE 1 END')
                ->first();
            
            if (!$budget) {
                Log::warning('BudgetExpense getBudgetDetails: No budget found', [
                    'entity_name' => $selectedEmployee->entity_name,
                    'expense_type' => $request->expense_type
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'No budget found for selected criteria'
                ]);
            }

            $currentYear = (int) date('Y');
            $yearColumn = 'budget_' . $currentYear;
            $budgetAmount = Schema::hasColumn('entity_budgets', $yearColumn) ? ($budget->$yearColumn ?? 0) : 0;

            // Get expenses, optionally filtered by cost head
            $expensesQuery = BudgetExpense::where('entity_budget_id', $budget->id);
            if ($request->filled('cost_head')) {
                $expensesQuery->whereRaw('LOWER(cost_head) = LOWER(?)', [$request->cost_head]);
            }
            $expenses = $expensesQuery->orderBy('expense_date', 'desc')->orderBy('id', 'desc')->get();

            // Total for this filter (this cost head only when cost_head selected)
            $totalExpensesFiltered = $expenses->sum('expense_amount');
            // Total all expenses under this budget (for available balance)
            $totalExpensesAll = BudgetExpense::where('entity_budget_id', $budget->id)->sum('expense_amount');

            $requestCostHead = $request->cost_head;
            $formattedExpenses = $expenses->map(function ($expense) use ($budget, $budgetAmount, $requestCostHead) {
                $balanceAfter = $budgetAmount - BudgetExpense::where('entity_budget_id', $budget->id)
                    ->where('created_at', '<=', $expense->created_at)
                    ->sum('expense_amount');
                $costHeadDisplay = $expense->cost_head ? ucfirst($expense->cost_head) : ($budget->cost_head ? ucfirst($budget->cost_head) : ($requestCostHead ? ucfirst($requestCostHead) : '—'));

                return [
                    'expense_date' => date('Y-m-d', strtotime($expense->expense_date)),
                    'expense_amount' => number_format($expense->expense_amount, 2),
                    'description' => $expense->description ?: '-',
                    'entity_name' => $budget->employee->entity_name ?? 'N/A',
                    'cost_head' => $costHeadDisplay,
                    'expense_type' => $budget->expense_type,
                    'balance_after' => number_format($balanceAfter, 2)
                ];
            });

            $costHeadDisplay = $budget->cost_head ? ucfirst($budget->cost_head) : ($request->cost_head ? ucfirst($request->cost_head) : '—');
            return response()->json([
                'success' => true,
                'entity_budget_id' => $budget->id,
                'entity_name' => $budget->employee->entity_name ?? 'N/A',
                'cost_head' => $costHeadDisplay,
                'expense_type' => $budget->expense_type,
                'budget_amount' => number_format($budgetAmount, 2),
                'total_expenses' => number_format($totalExpensesFiltered, 2),
                'available_balance' => number_format($budgetAmount - $totalExpensesAll, 2),
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

    /**
     * Printable view of budget expense (form summary + expenses table). Opens in new tab and triggers print.
     */
    public function printExpense($id)
    {
        $expense = BudgetExpense::with('entityBudget.employee')->findOrFail($id);
        $budget = $expense->entityBudget;
        $currentYear = (int) date('Y');
        $yearColumn = 'budget_' . $currentYear;
        $budgetAmount = Schema::hasColumn('entity_budgets', $yearColumn) ? ($budget->$yearColumn ?? 0) : 0;

        $expensesQuery = BudgetExpense::where('entity_budget_id', $budget->id);
        if (!empty($expense->cost_head)) {
            $expensesQuery->whereRaw('LOWER(cost_head) = LOWER(?)', [$expense->cost_head]);
        }
        $expenses = $expensesQuery->orderBy('expense_date', 'desc')->orderBy('id', 'desc')->get();

        $totalExpensesAll = BudgetExpense::where('entity_budget_id', $budget->id)->sum('expense_amount');
        $costHeadDisplay = $expense->cost_head ? ucfirst($expense->cost_head) : ($budget->cost_head ? ucfirst($budget->cost_head) : '—');

        $rows = $expenses->map(function ($e) use ($budget, $budgetAmount) {
            $balanceAfter = $budgetAmount - BudgetExpense::where('entity_budget_id', $budget->id)
                ->where('created_at', '<=', $e->created_at)
                ->sum('expense_amount');
            return [
                'expense_date' => date('Y-m-d', strtotime($e->expense_date)),
                'entity_name' => $budget->employee->entity_name ?? 'N/A',
                'cost_head' => $e->cost_head ? ucfirst($e->cost_head) : ($budget->cost_head ? ucfirst($budget->cost_head) : '—'),
                'expense_type' => $budget->expense_type,
                'expense_amount' => number_format($e->expense_amount, 2),
                'description' => $e->description ?: '-',
                'balance_after' => number_format($balanceAfter, 2),
            ];
        });

        $autoPrint = true;
        return response()->view('budget_expenses.print', [
            'expense' => $expense,
            'entity_name' => $budget->employee->entity_name ?? 'N/A',
            'expense_type' => $budget->expense_type,
            'cost_head' => $costHeadDisplay,
            'budget_amount' => number_format($budgetAmount, 2),
            'total_expenses' => number_format($totalExpensesAll, 2),
            'available_balance' => number_format($budgetAmount - $totalExpensesAll, 2),
            'rows' => $rows,
            'autoPrint' => $autoPrint,
        ]);
    }
}