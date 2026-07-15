<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\EntityHelper;
use App\Models\Entity;
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
        $expenseTypes = ['Maintenance', 'Capex Software', 'Capex Hardware', 'Subscription', 'Network'];
        
        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            Log::error('BudgetExpense create: Database connection failed: ' . $e->getMessage());
            return view('budget_expenses.create', compact('entities', 'costHeads', 'expenseTypes'))
                ->with('error', 'Database connection failed. Please check your database credentials.');
        }
        
        $entities = EntityHelper::getEntityRecords();
        
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
        $expenseTypes = ['Maintenance', 'Capex Software', 'Capex Hardware', 'Subscription', 'Network'];
        return view('budget_expenses.create', compact('entities', 'costHeads', 'costHeadsWithTypes', 'expenseTypes'))
            ->with('error', 'An error occurred. Please check logs.');
    }
}

    /**
     * Parse a human-entered date (DD-MM-YYYY or DD-MM-YY or Y-m-d) to Y-m-d.
     */
    private function parseHumanDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        $value = trim($value);
        foreach (['d-m-Y', 'd-m-y', 'Y-m-d'] as $format) {
            try {
                $d = \Carbon\Carbon::createFromFormat($format, $value);
                return $d->format('Y-m-d');
            } catch (\Exception $e) {
                // try next
            }
        }
        try {
            $d = new \DateTime($value);
            return $d->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'entity_budget_id' => 'required|exists:entity_budgets,id',
                'cost_head' => 'nullable|string',
                'is_contracting' => 'nullable|in:0,1',
                'line_items' => 'nullable|array|min:1',
                'line_items.*.expense_amount' => 'required_with:line_items|numeric|min:0',
                'line_items.*.quantity' => 'required_with:line_items|integer|min:1',
                'line_items.*.expense_date' => 'required_with:line_items|string',
                'line_items.*.description' => 'nullable|string',
                // backward compatibility (single-row old form)
                'expense_amount' => 'nullable|numeric|min:0',
                'quantity' => 'nullable|integer|min:1',
                'expense_date' => 'nullable|string',
                'description' => 'nullable|string',
            ]);

            $rawLines = collect($request->input('line_items', []));
            if ($rawLines->isEmpty()) {
                $rawLines = collect([[
                    'expense_amount' => $validated['expense_amount'] ?? null,
                    'quantity' => $validated['quantity'] ?? 1,
                    'expense_date' => $validated['expense_date'] ?? null,
                    'description' => $validated['description'] ?? null,
                ]]);
            }

            $isContracting = (bool) ($request->get('is_contracting', 0));
            $vatPercent = $isContracting ? 15 : 5;

            $lines = $rawLines->map(function ($line) use ($vatPercent) {
                $unitAmountBeforeVat = (float) ($line['expense_amount'] ?? 0);
                $quantity = max((int) ($line['quantity'] ?? 1), 1);
                $amountBeforeVat = round($unitAmountBeforeVat * $quantity, 2);
                $rawDate = $line['expense_date'] ?? null;
                $parsedDate = $this->parseHumanDate($rawDate);
                if (!$parsedDate) {
                    throw new \Exception('Invalid expense date. Please use format DD-MM-YYYY.');
                }
                $vatAmount = round($amountBeforeVat * $vatPercent / 100, 2);
                $totalWithVat = $amountBeforeVat + $vatAmount;

                return [
                    'quantity' => $quantity,
                    'parsed_date' => $parsedDate,
                    'description' => $line['description'] ?? null,
                    'amount_before_vat' => $amountBeforeVat,
                    'vat_amount' => $vatAmount,
                    'total_with_vat' => $totalWithVat,
                ];
            })->values();

            if ($lines->isEmpty()) {
                throw new \Exception('Please add at least one expense line.');
            }
            $grandTotalWithVat = round((float) $lines->sum('total_with_vat'), 2);

            // Check available balance (all expenses under this budget) – deduct total including VAT
            $budget = EntityBudget::find($validated['entity_budget_id']);
            $totalExpenses = BudgetExpense::where('entity_budget_id', $budget->id)->sum('expense_amount');

            $currentYear = (int) date('Y');
            $yearColumn = 'budget_' . $currentYear;
            $budgetAmount = Schema::hasColumn('entity_budgets', $yearColumn) ? ($budget->$yearColumn ?? 0) : 0;
            if (($totalExpenses + $grandTotalWithVat) > $budgetAmount) {
                throw new \Exception('Insufficient budget balance. Total including VAT (' . number_format($grandTotalWithVat, 2) . ') exceeds available balance.');
            }

            $savedExpenses = collect();
            $submissionGroupId = $lines->count() > 1 && Schema::hasColumn('budget_expenses', 'submission_group_id')
                ? BudgetExpense::newSubmissionGroupId()
                : null;

            DB::transaction(function () use ($validated, $lines, $isContracting, $vatPercent, $submissionGroupId, &$savedExpenses) {
                foreach ($lines as $line) {
                    $expenseData = [
                        'entity_budget_id' => $validated['entity_budget_id'],
                        'cost_head' => $validated['cost_head'] ?? null,
                        'expense_amount' => $line['total_with_vat'], // stored total (amount + VAT)
                        'expense_date' => $line['parsed_date'],
                        'description' => $line['description'],
                        'is_contracting' => $isContracting,
                        'amount_before_vat' => $line['amount_before_vat'],
                        'vat_percent' => $vatPercent,
                        'vat_amount' => $line['vat_amount'],
                    ];
                    if ($submissionGroupId !== null) {
                        $expenseData['submission_group_id'] = $submissionGroupId;
                    }
                    if (Schema::hasColumn('budget_expenses', 'quantity')) {
                        $expenseData['quantity'] = $line['quantity'];
                    }
                    $savedExpenses->push(BudgetExpense::create($expenseData));
                }
            });

            // Build fresh response payload instead of reusing getBudgetDetails (POST does not contain all its params)
            $updatedTotalExpenses = $totalExpenses + $grandTotalWithVat;
            $availableBalance = $budgetAmount - $updatedTotalExpenses;
            $firstSavedExpense = $savedExpenses->first();
            $joinedDescription = $savedExpenses->pluck('description')->filter(fn ($d) => filled($d))->implode('; ') ?: '-';
            $groupTotal = round((float) $savedExpenses->sum('expense_amount'), 2);

            $latestExpense = [
                'id' => $firstSavedExpense->id,
                'expense_date' => date('Y-m-d', strtotime($firstSavedExpense->expense_date)),
                'expense_amount' => number_format($groupTotal, 2),
                'quantity' => (int) $savedExpenses->sum(fn ($e) => (int) ($e->quantity ?? 1)),
                'description' => $joinedDescription,
                'entity_name' => $budget->employee->entity_name ?? 'N/A',
                'cost_head' => $validated['cost_head'] ?: ($budget->cost_head ?? '—'),
                'expense_type' => $budget->expense_type,
                'balance_after' => number_format($availableBalance, 2),
                'print_url' => null,
            ];

            $savedExpenseIds = $savedExpenses->pluck('id')->map(fn ($id) => (int) $id)->values();
            $idsQuery = $savedExpenseIds->implode(',');
            $printUrl = route('budget-expenses.print', $firstSavedExpense->id) . ($savedExpenseIds->count() > 1 ? ('?ids=' . $idsQuery) : '');
            $latestExpense['print_url'] = $printUrl;

            $payload = [
                'success' => true,
                'entity_budget_id' => $budget->id,
                'entity_name' => $budget->employee->entity_name ?? 'N/A',
                'cost_head' => $validated['cost_head'] ?: ($budget->cost_head ?? '—'),
                'expense_type' => $budget->expense_type,
                'budget_amount' => number_format($budgetAmount, 2),
                'total_expenses' => number_format($updatedTotalExpenses, 2),
                'available_balance' => number_format($availableBalance, 2),
                'expenses' => [$latestExpense],
                'saved_expense_id' => $firstSavedExpense->id,
                'saved_count' => $savedExpenses->count(),
                'print_url' => $printUrl,
            ];

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json($payload);
            }

            return redirect()
                ->route('budget-expenses.create')
                ->with('success', $savedExpenses->count() > 1 ? ($savedExpenses->count() . ' expense lines saved as one entry.') : 'Expense saved successfully.')
                ->with('saved_expense_id', $firstSavedExpense->id)
                ->with('saved_expense', $latestExpense)
                ->with('print_url', $printUrl);

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
        $expenseTypes = ['Maintenance', 'Capex Software', 'Capex Hardware', 'Subscription', 'Network'];
        $costHeadsWithTypes = [];

        $entities = EntityHelper::getEntityRecords();

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

        $quantity = (int) ($expense->quantity ?? 1);
        $amountBeforeVat = $expense->amount_before_vat !== null
            ? (float) $expense->amount_before_vat
            : (float) $expense->expense_amount - (float) ($expense->vat_amount ?? 0);
        $unitAmountBeforeVat = $quantity > 0 ? round($amountBeforeVat / $quantity, 2) : $amountBeforeVat;

        $currentYear = (int) date('Y');
        $yearColumn = 'budget_' . $currentYear;
        $budgetAmount = Schema::hasColumn('entity_budgets', $yearColumn) ? ($budget->$yearColumn ?? 0) : 0;
        $totalExpensesAll = BudgetExpense::where('entity_budget_id', $budget->id)->sum('expense_amount');
        $availableBalance = $budgetAmount - $totalExpensesAll;

        return view('budget_expenses.edit', compact('expense', 'entities', 'costHeads', 'costHeadsWithTypes', 'expenseTypes', 'budget', 'amountBeforeVat', 'unitAmountBeforeVat', 'quantity', 'budgetAmount', 'totalExpensesAll', 'availableBalance'));
    }

    /**
     * Update an existing budget expense. Recomputes VAT and replaces the previous amount.
     * Uses the existing (old) expense amount so the budget is not reduced twice when editing.
     */
    public function update(Request $request, $id)
    {
        $expense = BudgetExpense::with('entityBudget')->findOrFail($id);
        $expense->refresh(); // ensure we have current DB values before reading old amount
        $budget = $expense->entityBudget;

        $validated = $request->validate([
            'entity_budget_id' => 'required|exists:entity_budgets,id',
            'cost_head' => 'nullable|string',
            'expense_amount' => 'required|numeric|min:0', // unit amount before VAT
            'quantity' => 'required|integer|min:1',
            'expense_date' => 'required|string',
            'description' => 'nullable|string',
            'is_contracting' => 'nullable|in:0,1'
        ]);

        if ((int) $validated['entity_budget_id'] !== (int) $budget->id) {
            return response()->json(['success' => false, 'message' => 'Budget mismatch'], 422);
        }

        $unitAmountBeforeVat = (float) $validated['expense_amount'];
        $quantity = (int) $validated['quantity'];
        $amountBeforeVat = round($unitAmountBeforeVat * $quantity, 2);

        $rawDate = $validated['expense_date'];
        $parsedDate = $this->parseHumanDate($rawDate);
        if (!$parsedDate) {
            return response()->json(['success' => false, 'message' => 'Invalid expense date. Please use DD-MM-YYYY.'], 422);
        }
        $isContracting = (bool) ($request->get('is_contracting', 0));
        $vatPercent = $isContracting ? 15 : 5;
        $vatAmount = round($amountBeforeVat * $vatPercent / 100, 2);
        $totalWithVat = $amountBeforeVat + $vatAmount;

        $currentYear = (int) date('Y');
        $yearColumn = 'budget_' . $currentYear;
        $budgetAmount = Schema::hasColumn('entity_budgets', $yearColumn) ? ($budget->$yearColumn ?? 0) : 0;

        // Total of all expenses under this budget (includes this expense's current stored amount)
        $totalExpensesAll = BudgetExpense::where('entity_budget_id', $budget->id)->sum('expense_amount');
        // Existing amount for this expense only – we replace it, so do not deduct it twice
        $oldAmountThisExpense = (float) $expense->expense_amount;
        $totalWithoutThis = $totalExpensesAll - $oldAmountThisExpense;
        if (($totalWithoutThis + $totalWithVat) > $budgetAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient budget balance. Total including VAT (' . number_format($totalWithVat, 2) . ') would exceed available balance.'
            ], 422);
        }

        $expenseData = [
            'cost_head' => $validated['cost_head'] ?? null,
            'expense_amount' => $totalWithVat,
            'expense_date' => $parsedDate,
            'description' => $validated['description'] ?? null,
            'is_contracting' => $isContracting,
            'amount_before_vat' => $amountBeforeVat,
            'vat_percent' => $vatPercent,
            'vat_amount' => $vatAmount,
        ];
        if (Schema::hasColumn('budget_expenses', 'quantity')) {
            $expenseData['quantity'] = $quantity;
        }
        $expense->update($expenseData);

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
            
            $masterEntity = Entity::find((int) $request->entity_id);
            if (!$masterEntity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Entity not found'
                ]);
            }

            $employeeIds = EntityHelper::employeeIdsForEntityId((int) $request->entity_id);
            
            Log::info('BudgetExpense getBudgetDetails: Searching for budget', [
                'entity_id' => $request->entity_id,
                'entity_name' => $masterEntity->name,
                'cost_head' => $request->cost_head,
                'expense_type' => $request->expense_type
            ]);
            
            Log::info('BudgetExpense getBudgetDetails: Employee IDs for entity', [
                'entity_name' => $masterEntity->name,
                'employee_ids' => $employeeIds,
                'cost_head' => $request->cost_head,
                'expense_type' => $request->expense_type
            ]);
            
            if (empty($employeeIds)) {
                Log::warning('BudgetExpense getBudgetDetails: No employees found for entity: ' . $masterEntity->name);
                return response()->json([
                    'success' => false,
                    'message' => 'No employees found for selected entity'
                ]);
            }
            
            // One particular budget per (entity + expense_type); when cost_head is selected, prefer budget that matches it.
            $costHeadReq = $request->filled('cost_head') ? trim($request->cost_head) : null;
            $budgetQuery = EntityBudget::with('employee')
                ->whereIn('employee_id', $employeeIds)
                ->where('expense_type', $request->expense_type);
            if ($costHeadReq !== null && $costHeadReq !== '') {
                $budgetQuery->orderByRaw('CASE WHEN LOWER(TRIM(cost_head)) = LOWER(?) THEN 0 WHEN cost_head IS NULL THEN 1 ELSE 2 END', [$costHeadReq]);
            } else {
                $budgetQuery->orderByRaw('CASE WHEN cost_head IS NULL THEN 0 ELSE 1 END');
            }
            $budget = $budgetQuery->first();
            
            if (!$budget) {
                Log::warning('BudgetExpense getBudgetDetails: No budget found', [
                    'entity_name' => $masterEntity->name,
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

            // Get most recent expense only (for balance reference)
            $expensesQuery = BudgetExpense::where('entity_budget_id', $budget->id);
            if ($request->filled('cost_head')) {
                $expensesQuery->whereRaw('LOWER(cost_head) = LOWER(?)', [$request->cost_head]);
            }
            $expenses = $expensesQuery->orderBy('expense_date', 'desc')->orderBy('id', 'desc')->limit(1)->get();

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
        $entities = EntityHelper::getEntityRecords();
        $expenseTypes = ['Maintenance', 'Capex Software', 'Capex Hardware', 'Subscription', 'Network'];
        $costHeadsWithTypes = \App\Http\Controllers\EntityBudgetController::getCostHeadsList();
        $costHeadsByType = collect($costHeadsWithTypes)->groupBy('expense_type')->map(fn ($g) => $g->pluck('name')->toArray())->toArray();

        $expenses = collect([]);
        $entityName = null;
        $costHead = null;
        $expenseType = null;
        $budgetAmount = 0;
        $totalExpensesAll = 0;

        if ($request->filled('entity_id') && $request->filled('cost_head') && $request->filled('expense_type')) {
            $masterEntity = Entity::find((int) $request->entity_id);
            if ($masterEntity) {
                $entityName = $masterEntity->name;
                $employeeIds = EntityHelper::employeeIdsForEntityId((int) $request->entity_id);
                $costHeadReq = $request->filled('cost_head') ? trim($request->cost_head) : null;
                $budgetQuery = EntityBudget::with('employee')
                    ->whereIn('employee_id', $employeeIds)
                    ->where('expense_type', $request->expense_type);
                if ($costHeadReq !== null && $costHeadReq !== '') {
                    $budgetQuery->orderByRaw('CASE WHEN LOWER(TRIM(cost_head)) = LOWER(?) THEN 0 WHEN cost_head IS NULL THEN 1 ELSE 2 END', [$costHeadReq]);
                } else {
                    $budgetQuery->orderByRaw('CASE WHEN cost_head IS NULL THEN 0 ELSE 1 END');
                }
                $budget = $budgetQuery->first();
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
                    $expenses = BudgetExpense::groupBySubmission($allExpenses)
                        ->sortByDesc(fn ($group) => $group->max('id'))
                        ->values()
                        ->map(function ($group) use ($budget, $budgetAmount, $entityName, $costHeadLabel) {
                            $first = $group->sortBy('id')->first();
                            $last = $group->sortBy('id')->last();
                            $ids = $group->pluck('id')->map(fn ($id) => (int) $id)->values();
                            $amount = round((float) $group->sum('expense_amount'), 2);
                            $balanceAfter = $budgetAmount - BudgetExpense::where('entity_budget_id', $budget->id)
                                ->where('created_at', '<=', $last->created_at)
                                ->sum('expense_amount');
                            $description = $group->pluck('description')->filter(fn ($d) => filled($d))->implode('; ') ?: '-';
                            $printUrl = route('budget-expenses.print', $first->id)
                                . ($ids->count() > 1 ? ('?ids=' . $ids->implode(',')) : '');

                            return [
                                'id' => $first->id,
                                'ids' => $ids->all(),
                                'expense_date' => date('Y-m-d', strtotime($first->expense_date)),
                                'expense_amount' => number_format($amount, 2),
                                'description' => $description,
                                'entity_name' => $entityName,
                                'cost_head' => $first->cost_head ? ucfirst($first->cost_head) : $costHeadLabel,
                                'expense_type' => $budget->expense_type,
                                'balance_after' => number_format($balanceAfter, 2),
                                'print_url' => $printUrl,
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
    public function printExpense(Request $request, $id)
    {
        $expense = BudgetExpense::with('entityBudget.employee')->findOrFail($id);
        $budget = $expense->entityBudget;
        $currentYear = (int) date('Y');
        $yearColumn = 'budget_' . $currentYear;
        $budgetAmount = Schema::hasColumn('entity_budgets', $yearColumn) ? ($budget->$yearColumn ?? 0) : 0;

        $expensesQuery = BudgetExpense::where('entity_budget_id', $budget->id);
        $idsParam = trim((string) $request->query('ids', ''));
        $requestedIds = collect(explode(',', $idsParam))
            ->map(fn ($v) => (int) trim($v))
            ->filter(fn ($v) => $v > 0)
            ->unique()
            ->values();

        if ($requestedIds->isNotEmpty()) {
            // Multi-line save: print only the rows created in that save.
            $expensesQuery->whereIn('id', $requestedIds->all());
        } else {
            // Single print: print only the selected expense row.
            $expensesQuery->where('id', $expense->id);
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
                'quantity' => (int) ($e->quantity ?? 1),
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

    /**
     * Delete a budget expense (and sibling lines from the same form when grouped).
     */
    public function destroy(Request $request, $id)
    {
        try {
            $expense = BudgetExpense::findOrFail($id);
            $idsParam = trim((string) $request->input('ids', ''));
            $requestedIds = collect(explode(',', $idsParam))
                ->map(fn ($v) => (int) trim($v))
                ->filter(fn ($v) => $v > 0)
                ->unique()
                ->values();

            if ($requestedIds->isNotEmpty()) {
                BudgetExpense::whereIn('id', $requestedIds->all())->delete();
                $message = $requestedIds->count() > 1
                    ? 'Expense entry (' . $requestedIds->count() . ' lines) deleted successfully.'
                    : 'Expense deleted successfully.';
            } elseif (
                Schema::hasColumn('budget_expenses', 'submission_group_id')
                && !empty($expense->submission_group_id)
            ) {
                $deleted = BudgetExpense::where('submission_group_id', $expense->submission_group_id)->delete();
                $message = $deleted > 1
                    ? 'Expense entry (' . $deleted . ' lines) deleted successfully.'
                    : 'Expense deleted successfully.';
            } else {
                $expense->delete();
                $message = 'Expense deleted successfully.';
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('BudgetExpense delete error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to delete expense.');
        }
    }
}