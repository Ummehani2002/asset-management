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
                'expense_amount' => 'required|numeric|min:0', // amount before VAT
                'expense_date' => 'required|date',
                'description' => 'nullable|string',
                'is_contracting' => 'nullable|in:0,1'
            ]);

            $amountBeforeVat = (float) $validated['expense_amount'];
            $isContracting = (bool) ($request->get('is_contracting', 0));
            $vatPercent = $isContracting ? 15 : 5;
            $vatAmount = round($amountBeforeVat * $vatPercent / 100, 2);
            $totalWithVat = $amountBeforeVat + $vatAmount;

            // Check available balance (all expenses under this budget) – deduct total including VAT
            $budget = EntityBudget::find($validated['entity_budget_id']);
            $totalExpenses = BudgetExpense::where('entity_budget_id', $budget->id)
                ->sum('expense_amount');

            $currentYear = (int) date('Y');
            $yearColumn = 'budget_' . $currentYear;
            $budgetAmount = Schema::hasColumn('entity_budgets', $yearColumn) ? ($budget->$yearColumn ?? 0) : 0;
            if (($totalExpenses + $totalWithVat) > $budgetAmount) {
                throw new \Exception('Insufficient budget balance. Total including VAT (' . number_format($totalWithVat, 2) . ') exceeds available balance.');
            }

            $expense = BudgetExpense::create([
                'entity_budget_id' => $validated['entity_budget_id'],
                'cost_head' => $validated['cost_head'] ?? null,
                'expense_amount' => $totalWithVat, // stored total (amount + VAT) for balance deduction
                'expense_date' => $validated['expense_date'],
                'description' => $validated['description'] ?? null,
                'is_contracting' => $isContracting,
                'amount_before_vat' => $amountBeforeVat,
                'vat_percent' => $vatPercent,
                'vat_amount' => $vatAmount,
            ]);

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

    /**
     * Show edit form for an existing budget expense.
     */
    public function edit($id)
    {
        $expense = BudgetExpense::with('entityBudget.employee')->findOrFail($id);
        $budget = $expense->entityBudget;
        if (!$budget || !$budget->employee) {
            abort(404, 'Budget or employee not found');
        }

        $entities = collect([]);
        $costHeads = [];
        $expenseTypes = ['Maintenance', 'Capex Software', 'Capex Hardware', 'Subscription'];
        $costHeadsWithTypes = [];

        if (Schema::hasTable('employees')) {
            $uniqueEntityNames = Employee::whereNotNull('entity_name')
                ->where('entity_name', '!=', '')
                ->distinct()
                ->pluck('entity_name')
                ->toArray();
            $entities = collect($uniqueEntityNames)->map(function ($entityName) {
                return Employee::where('entity_name', $entityName)->first();
            })->filter()->values();
        }

        $costHeadsList = \App\Http\Controllers\EntityBudgetController::getCostHeadsList();
        foreach ($costHeadsList as $item) {
            $costHeadsWithTypes[$item['name']] = $item['expense_type'];
        }
        $predefinedNames = array_column($costHeadsList, 'name');
        if (Schema::hasTable('entity_budgets')) {
            $fromDb = EntityBudget::distinct()->pluck('cost_head')->filter()->values()->toArray();
            $costHeads = array_values(array_unique(array_merge($predefinedNames, $fromDb)));
            sort($costHeads);
        } else {
            $costHeads = $predefinedNames;
        }

        $amountBeforeVat = $expense->amount_before_vat !== null
            ? (float) $expense->amount_before_vat
            : (float) $expense->expense_amount - (float) ($expense->vat_amount ?? 0);

        $currentYear = (int) date('Y');
        $yearColumn = 'budget_' . $currentYear;
        $budgetAmount = Schema::hasColumn('entity_budgets', $yearColumn) ? ($budget->$yearColumn ?? 0) : 0;
        $totalExpensesAll = BudgetExpense::where('entity_budget_id', $budget->id)->sum('expense_amount');
        $availableBalance = $budgetAmount - $totalExpensesAll;

        return view('budget_expenses.edit', compact('expense', 'entities', 'costHeads', 'costHeadsWithTypes', 'expenseTypes', 'budget', 'amountBeforeVat', 'budgetAmount', 'totalExpensesAll', 'availableBalance'));
    }

    /**
     * Update an existing budget expense. Recomputes VAT and balance check excluding current expense.
     */
    public function update(Request $request, $id)
    {
        $expense = BudgetExpense::with('entityBudget')->findOrFail($id);
        $budget = $expense->entityBudget;

        $validated = $request->validate([
            'entity_budget_id' => 'required|exists:entity_budgets,id',
            'cost_head' => 'nullable|string',
            'expense_amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
            'is_contracting' => 'nullable|in:0,1'
        ]);

        if ((int) $validated['entity_budget_id'] !== (int) $budget->id) {
            return response()->json(['success' => false, 'message' => 'Budget mismatch'], 422);
        }

        $amountBeforeVat = (float) $validated['expense_amount'];
        $isContracting = (bool) ($request->get('is_contracting', 0));
        $vatPercent = $isContracting ? 15 : 5;
        $vatAmount = round($amountBeforeVat * $vatPercent / 100, 2);
        $totalWithVat = $amountBeforeVat + $vatAmount;

        $currentYear = (int) date('Y');
        $yearColumn = 'budget_' . $currentYear;
        $budgetAmount = Schema::hasColumn('entity_budgets', $yearColumn) ? ($budget->$yearColumn ?? 0) : 0;
        $totalExpensesAll = BudgetExpense::where('entity_budget_id', $budget->id)->sum('expense_amount');
        $totalWithoutThis = $totalExpensesAll - (float) $expense->expense_amount;
        if (($totalWithoutThis + $totalWithVat) > $budgetAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient budget balance. Total including VAT (' . number_format($totalWithVat, 2) . ') would exceed available balance.'
            ], 422);
        }

        $expense->update([
            'cost_head' => $validated['cost_head'] ?? null,
            'expense_amount' => $totalWithVat,
            'expense_date' => $validated['expense_date'],
            'description' => $validated['description'] ?? null,
            'is_contracting' => $isContracting,
            'amount_before_vat' => $amountBeforeVat,
            'vat_percent' => $vatPercent,
            'vat_amount' => $vatAmount,
        ]);

        if ($request->wantsJson()) {
            $printUrl = route('budget-expenses.print', $expense->id);
            return response()->json(['success' => true, 'message' => 'Expense updated.', 'print_url' => $printUrl]);
        }
        return redirect()->route('budget-expenses.edit', $expense->id)->with('success', 'Expense updated.')->with('print_url', route('budget-expenses.print', $expense->id));
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

            // Get recent expenses (most recent first) - limit to 5 for quick view
            $expensesQuery = BudgetExpense::where('entity_budget_id', $budget->id);
            if ($request->filled('cost_head')) {
                $expensesQuery->whereRaw('LOWER(cost_head) = LOWER(?)', [$request->cost_head]);
            }
            $expenses = $expensesQuery->orderBy('expense_date', 'desc')->orderBy('id', 'desc')->limit(5)->get();

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
                    'id' => $expense->id,
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
                'total_expenses' => number_format($totalExpensesAll, 2),
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
     * Full expense history for selected entity + cost head + expense type (all expenses, no limit).
     */
    public function expenseHistory(Request $request)
    {
        $entities = collect([]);
        if (Schema::hasTable('employees')) {
            $uniqueEntityNames = Employee::whereNotNull('entity_name')->where('entity_name', '!=', '')->distinct()->pluck('entity_name')->toArray();
            $entities = collect($uniqueEntityNames)->map(fn ($n) => Employee::where('entity_name', $n)->first())->filter()->values();
        }
        $expenseTypes = ['Maintenance', 'Capex Software', 'Capex Hardware', 'Subscription'];
        $costHeadsWithTypes = \App\Http\Controllers\EntityBudgetController::getCostHeadsList();
        $costHeadsByType = collect($costHeadsWithTypes)->groupBy('expense_type')->map(fn ($g) => $g->pluck('name')->toArray())->toArray();

        $expenses = collect([]);
        $entityName = null;
        $costHead = null;
        $expenseType = null;
        $budgetAmount = 0;
        $totalExpensesAll = 0;

        if ($request->filled('entity_id') && $request->filled('cost_head') && $request->filled('expense_type')) {
            $selectedEmployee = Employee::find($request->entity_id);
            if ($selectedEmployee) {
                $employeeIds = Employee::where('entity_name', $selectedEmployee->entity_name)->pluck('id')->toArray();
                $budget = EntityBudget::with('employee')
                    ->whereIn('employee_id', $employeeIds)
                    ->where('expense_type', $request->expense_type)
                    ->orderByRaw('CASE WHEN cost_head IS NULL THEN 0 ELSE 1 END')
                    ->first();
                if ($budget) {
                    $entityName = $budget->employee->entity_name ?? 'N/A';
                    $costHead = $request->cost_head;
                    $expenseType = $budget->expense_type;
                    $currentYear = (int) date('Y');
                    $yearColumn = 'budget_' . $currentYear;
                    $budgetAmount = Schema::hasColumn('entity_budgets', $yearColumn) ? ($budget->$yearColumn ?? 0) : 0;
                    $expensesQuery = BudgetExpense::where('entity_budget_id', $budget->id)
                        ->whereRaw('LOWER(cost_head) = LOWER(?)', [$request->cost_head]);
                    $allExpenses = $expensesQuery->orderBy('expense_date', 'desc')->orderBy('id', 'desc')->get();
                    $totalExpensesAll = BudgetExpense::where('entity_budget_id', $budget->id)->sum('expense_amount');
                    $entityName = $budget->employee->entity_name ?? 'N/A';
                    $costHeadLabel = ucfirst($request->cost_head);
                    $expenses = $allExpenses->map(function ($expense) use ($budget, $budgetAmount, $entityName, $costHeadLabel) {
                        $balanceAfter = $budgetAmount - BudgetExpense::where('entity_budget_id', $budget->id)
                            ->where('created_at', '<=', $expense->created_at)
                            ->sum('expense_amount');
                        return [
                            'id' => $expense->id,
                            'expense_date' => date('Y-m-d', strtotime($expense->expense_date)),
                            'expense_amount' => number_format($expense->expense_amount, 2),
                            'description' => $expense->description ?: '-',
                            'entity_name' => $entityName,
                            'cost_head' => $expense->cost_head ? ucfirst($expense->cost_head) : $costHeadLabel,
                            'expense_type' => $budget->expense_type,
                            'balance_after' => number_format($balanceAfter, 2),
                        ];
                    });
                }
            }
        }

        return view('budget_expenses.history', compact('entities', 'expenseTypes', 'costHeadsByType', 'expenses', 'entityName', 'costHead', 'expenseType', 'budgetAmount', 'totalExpensesAll'));
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
            $row = [
                'expense_date' => date('Y-m-d', strtotime($e->expense_date)),
                'entity_name' => $budget->employee->entity_name ?? 'N/A',
                'cost_head' => $e->cost_head ? ucfirst($e->cost_head) : ($budget->cost_head ? ucfirst($budget->cost_head) : '—'),
                'expense_type' => $budget->expense_type,
                'expense_amount' => number_format($e->expense_amount, 2),
                'description' => $e->description ?: '-',
                'balance_after' => number_format($balanceAfter, 2),
            ];
            if ($e->amount_before_vat !== null && $e->vat_percent !== null && $e->vat_amount !== null) {
                $row['amount_before_vat'] = number_format($e->amount_before_vat, 2);
                $row['vat_percent'] = $e->vat_percent;
                $row['vat_amount'] = number_format($e->vat_amount, 2);
            }
            return $row;
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