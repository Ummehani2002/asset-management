<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EntityBudget;
use App\Models\Employee;
use App\Models\BudgetExpense;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class EntityBudgetController extends Controller
{
    /**
     * Standard cost heads with default expense type and category (all Overhead).
     * Used for Entity Budget and Budget Expense dropdowns.
     */
    public static function getCostHeadsList(): array
    {
        return [
            ['name' => 'Network', 'expense_type' => 'Capex Software', 'category' => 'Overhead'],
            ['name' => 'Multifunction Printers & Plotters', 'expense_type' => 'Maintenance', 'category' => 'Overhead'],
            ['name' => 'New Laptops, Desktops & Workstations', 'expense_type' => 'Capex Software', 'category' => 'Overhead'],
            ['name' => 'CAD Softwares', 'expense_type' => 'Subscription', 'category' => 'Overhead'],
            ['name' => 'Office 365', 'expense_type' => 'Capex Software', 'category' => 'Overhead'],
            ['name' => 'Candy', 'expense_type' => 'Subscription', 'category' => 'Overhead'],
            ['name' => 'Consumables', 'expense_type' => 'Maintenance', 'category' => 'Overhead'],
            ['name' => 'Computer Service', 'expense_type' => 'Maintenance', 'category' => 'Overhead'],
            ['name' => 'Email Security Services', 'expense_type' => 'Subscription', 'category' => 'Overhead'],
            ['name' => 'On Screen Take OFF', 'expense_type' => 'Capex Software', 'category' => 'Overhead'],
            ['name' => 'Primavera', 'expense_type' => 'Subscription', 'category' => 'Overhead'],
            ['name' => 'MS Office', 'expense_type' => 'Subscription', 'category' => 'Overhead'],
            ['name' => 'Power BI Report', 'expense_type' => 'Subscription', 'category' => 'Overhead'],
            ['name' => 'Operating System', 'expense_type' => 'Capex Software', 'category' => 'Overhead'],
            ['name' => 'Servers & Network Equipment', 'expense_type' => 'Capex Hardware', 'category' => 'Overhead'],
            ['name' => 'Desktops & Monitors', 'expense_type' => 'Capex Hardware', 'category' => 'Overhead'],
            ['name' => 'Laptops & Workstations', 'expense_type' => 'Capex Hardware', 'category' => 'Overhead'],
            ['name' => 'Crowd Strike Antivirus', 'expense_type' => 'Subscription', 'category' => 'Overhead'],
            ['name' => 'DMS', 'expense_type' => 'Maintenance', 'category' => 'Overhead'],
            ['name' => 'Firewall & Hosting Security', 'expense_type' => 'Subscription', 'category' => 'Overhead'],
            ['name' => 'Sonic Wall – Security Services', 'expense_type' => 'Subscription', 'category' => 'Overhead'],
            ['name' => 'Baracuda Email Backup', 'expense_type' => 'Subscription', 'category' => 'Overhead'],
            ['name' => 'Power BI Licenses', 'expense_type' => 'Subscription', 'category' => 'Overhead'],
            ['name' => 'Domain Renewal', 'expense_type' => 'Maintenance', 'category' => 'Overhead'],
            ['name' => 'Hosting', 'expense_type' => 'Subscription', 'category' => 'Overhead'],
            ['name' => 'Check Point Email Security Services', 'expense_type' => 'Subscription', 'category' => 'Overhead'],
        ];
    }

    public function create(Request $request)
    {
        try {
            // Initialize default values
            $entities = collect([]);
            $costHeadsList = self::getCostHeadsList();
            $costHeads = array_column($costHeadsList, 'name');
            $expenseTypes = ['Maintenance', 'Capex Software', 'Capex Hardware', 'Subscription'];
            $budgets = collect([]);

            // Test database connection first
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                Log::error('EntityBudget create: Database connection failed: ' . $e->getMessage());
                return view('entity_budget.create', compact('entities', 'costHeads', 'costHeadsList', 'expenseTypes', 'budgets'))
                    ->with('error', 'Database connection failed. Please check your database credentials in Laravel Cloud environment variables.');
            }

            // Check if required tables exist
            try {
                $hasEmployees = Schema::hasTable('employees');
                $hasEntityBudgets = Schema::hasTable('entity_budgets');
            } catch (\Exception $e) {
                Log::error('EntityBudget create: Schema check failed: ' . $e->getMessage());
                return view('entity_budget.create', compact('entities', 'costHeads', 'costHeadsList', 'expenseTypes', 'budgets'))
                    ->with('error', 'Unable to check database tables. Please verify database connection.');
            }
            
            // Get unique entities - one employee per unique entity_name
            if ($hasEmployees) {
                try {
                    // First get all distinct entity names
                    $uniqueEntityNames = Employee::whereNotNull('entity_name')
                        ->where('entity_name', '!=', '')
                        ->distinct()
                        ->pluck('entity_name')
                        ->toArray();
                    
                    // Then get the first employee for each unique entity_name
                    $entities = collect($uniqueEntityNames)->map(function($entityName) {
                        return Employee::where('entity_name', $entityName)->first();
                    })->filter()->values();
                    
                    // Ensure it's a collection
                    if (!$entities instanceof \Illuminate\Support\Collection) {
                        $entities = collect($entities);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::error('EntityBudget create: Entities query error: ' . $e->getMessage());
                } catch (\Exception $e) {
                    Log::warning('Error loading entities: ' . $e->getMessage());
                }
            }
            
            // Filter budgets by entity and expense type (year = current year; All = no filter)
            $selectedYear = (int) date('Y');
            $yearColumn = 'budget_' . $selectedYear;

            if ($hasEntityBudgets) {
                try {
                    $query = EntityBudget::with(['employee', 'expenses']);

                    if (Schema::hasColumn('entity_budgets', $yearColumn)) {
                        $query->whereNotNull($yearColumn);
                    }

                    if ($request->filled('entity_id') && $hasEmployees) {
                        $selectedEntity = Employee::find($request->entity_id);
                        if ($selectedEntity) {
                            $employeeIds = Employee::where('entity_name', $selectedEntity->entity_name)
                                ->pluck('id')
                                ->toArray();
                            if (!empty($employeeIds)) {
                                $query->whereIn('employee_id', $employeeIds);
                            } else {
                                $query->whereRaw('1 = 0');
                            }
                        } else {
                            $query->whereRaw('1 = 0');
                        }
                    }

                    if ($request->filled('expense_type')) {
                        $query->where('expense_type', $request->expense_type);
                    }

                    $budgets = $query->get();

                    if (!$budgets instanceof \Illuminate\Support\Collection) {
                        $budgets = collect($budgets);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::error('EntityBudget create: Budgets query error: ' . $e->getMessage());
                    $budgets = collect([]);
                } catch (\Exception $e) {
                    Log::warning('Error loading budgets: ' . $e->getMessage());
                    $budgets = collect([]);
                }
            }

            $currentYear = (int)date('Y');
            $availableYears = range($currentYear, $currentYear + 5);

            $hasAllTables = $hasEmployees && $hasEntityBudgets;
            return view('entity_budget.create', compact('entities', 'costHeads', 'costHeadsList', 'expenseTypes', 'budgets', 'availableYears', 'selectedYear'))
                ->with('warning', $hasAllTables ? null : 'Database tables not found. Please run migrations: php artisan migrate --force');
        } catch (\Throwable $e) {
            Log::error('EntityBudget create fatal error: ' . $e->getMessage());
            Log::error('Error class: ' . get_class($e));
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('File: ' . $e->getFile() . ':' . $e->getLine());
            
            // Return with default values
            $entities = collect([]);
            $costHeadsList = self::getCostHeadsList();
            $costHeads = array_column($costHeadsList, 'name');
            $expenseTypes = ['Maintenance', 'Capex Software', 'Capex Hardware', 'Subscription'];
            $budgets = collect([]);
            return view('entity_budget.create', compact('entities', 'costHeads', 'costHeadsList', 'expenseTypes', 'budgets'))
                ->with('error', 'An error occurred. Please check Laravel Cloud logs for details.');
        }
    }

    public function export(Request $request)
    {
        $query = EntityBudget::with(['employee', 'expenses']);
        
        $selectedYear = $request->get('year', date('Y'));
        $yearColumn = 'budget_' . $selectedYear;
        
        if (Schema::hasColumn('entity_budgets', $yearColumn)) {
            $query->whereNotNull($yearColumn);
        }
        
        $entityName = 'All Entities';
        
        if ($request->filled('entity_id')) {
            $selectedEntity = Employee::find($request->entity_id);
            if ($selectedEntity) {
                $employeeIds = Employee::where('entity_name', $selectedEntity->entity_name)
                    ->pluck('id')
                    ->toArray();
                if (!empty($employeeIds)) {
                    $query->whereIn('employee_id', $employeeIds);
                }
                $entityName = $selectedEntity->entity_name;
            }
        }

        if ($request->filled('expense_type')) {
            $query->where('expense_type', $request->expense_type);
        }

        $budgets = $query->get();
        
        $format = $request->get('format', 'pdf');

        if ($format === 'excel' || $format === 'csv') {
            return $this->exportExcel($budgets, $entityName, $selectedYear);
        } else {
            return $this->exportPdf($budgets, $entityName, $selectedYear);
        }
    }

    private function exportPdf($budgets, $entityName, $year = null)
    {
        $year = $year ?? date('Y');
        $pdf = \PDF::loadView('entity_budget.export-pdf', compact('budgets', 'entityName', 'year'));
        return $pdf->download('entity-budget-' . str_replace(' ', '-', $entityName) . '-' . $year . '-' . date('Y-m-d') . '.pdf');
    }

    private function exportExcel($budgets, $entityName, $year = null)
    {
        $year = $year ?? date('Y');
        $yearColumn = 'budget_' . $year;
        $filename = 'entity-budget-' . str_replace(' ', '-', $entityName) . '-' . $year . '-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($budgets, $yearColumn, $year) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                '#', 'Entity', 'Expense Type', 'Budget ' . $year, 'Total Expenses', 'Available Balance'
            ]);

            // Data
            foreach ($budgets as $index => $budget) {
                $budgetAmount = $budget->$yearColumn ?? 0;
                $totalExpenses = $budget->expenses->sum('expense_amount');
                $availableBalance = $budgetAmount - $totalExpenses;
                
                fputcsv($file, [
                    $index + 1,
                    $budget->employee->entity_name ?? 'N/A',
                    $budget->expense_type ?? 'N/A',
                    number_format($budgetAmount, 2),
                    number_format($totalExpenses, 2),
                    number_format($availableBalance, 2),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function store(Request $request)
    {
        try {
            if (!Schema::hasTable('entity_budgets')) {
                Log::error('entity_budgets table does not exist');
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Database table not found. Please run migrations: php artisan migrate --force']);
            }

            $validated = $request->validate([
                'entity_id' => 'required|exists:employees,id',
                'expense_type' => 'required|string',
                'cost_head' => 'required|string|max:255',
                'budget_year' => 'required|integer|min:2020|max:2100',
                'budget_amount' => 'required|numeric|min:0'
            ]);
            
            $yearColumn = 'budget_' . $validated['budget_year'];
            
            if (!Schema::hasColumn('entity_budgets', $yearColumn)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Budget column for year ' . $validated['budget_year'] . ' does not exist. Please run migrations.']);
            }
            
            // Budget is maintained by entity + expense type + cost head. Update existing or create.
            $budget = EntityBudget::where('employee_id', $validated['entity_id'])
                ->where('expense_type', $validated['expense_type'])
                ->where('cost_head', $validated['cost_head'])
                ->first();
            
            if ($budget) {
                $budget->update([$yearColumn => $validated['budget_amount']]);
            } else {
                $budget = EntityBudget::create([
                    'employee_id' => $validated['entity_id'],
                    'cost_head' => $validated['cost_head'],
                    'expense_type' => $validated['expense_type'],
                    'category' => $request->get('category', 'Overhead'),
                    $yearColumn => $validated['budget_amount'],
                ]);
            }

            // Redirect back to create page with entity_id and year filter to show the newly created budget
            return redirect()->route('entity_budget.create', [
                'entity_id' => $validated['entity_id'],
                'year' => $validated['budget_year']
            ])
                ->with('success', 'Budget created successfully')
                ->with('saved_budget_id', $budget->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('EntityBudget store database error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Database error occurred. Please ensure migrations are run: php artisan migrate --force']);
        } catch (\Exception $e) {
            Log::error('EntityBudget store error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while saving the budget. Please try again.']);
        }
    }

    public function downloadForm($id)
    {
        $budget = EntityBudget::with(['employee', 'expenses'])->findOrFail($id);
        $currentYear = date('Y');
        $yearColumn = 'budget_' . $currentYear;
        $budgetAmount = $budget->$yearColumn ?? 0;

        $pdf = \PDF::loadView('entity_budget.download-form', compact('budget', 'budgetAmount', 'currentYear'));
        return $pdf->download('budget-' . $budget->id . '-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Show the saved budget form as HTML and trigger print dialog (used after save).
     */
    public function printForm($id)
    {
        $budget = EntityBudget::with(['employee', 'expenses'])->findOrFail($id);
        $currentYear = date('Y');
        $yearColumn = 'budget_' . $currentYear;
        $budgetAmount = $budget->$yearColumn ?? 0;
        $autoPrint = true;

        return response()->view('entity_budget.download-form', compact('budget', 'budgetAmount', 'currentYear', 'autoPrint'));
    }

    /**
     * Build transaction history rows with amount, cumulative spent, and balance after (per entity_budget).
     */
    private function buildTransactionHistoryRows($expensesQuery, int $year): \Illuminate\Support\Collection
    {
        $yearColumn = 'budget_' . $year;
        $expenses = $expensesQuery->orderBy('expense_date', 'asc')->orderBy('id', 'asc')->get();
        $cumulativeByBudget = [];
        $rows = [];
        foreach ($expenses as $e) {
            $budgetId = $e->entity_budget_id;
            $budget = $e->entityBudget;
            $budgetAmount = ($budget && Schema::hasColumn('entity_budgets', $yearColumn)) ? ($budget->$yearColumn ?? 0) : 0;
            $amount = $e->expense_amount ?? 0;
            $cumulativeByBudget[$budgetId] = ($cumulativeByBudget[$budgetId] ?? 0) + $amount;
            $cumulativeSpent = $cumulativeByBudget[$budgetId];
            $balanceAfter = $budgetAmount - $cumulativeSpent;
            $rows[] = (object) [
                'expense' => $e,
                'budget_amount' => $budgetAmount,
                'amount' => $amount,
                'cumulative_spent' => $cumulativeSpent,
                'balance_after' => $balanceAfter,
            ];
        }
        return collect($rows)->reverse()->values(); // show newest first in UI
    }

    /**
     * Transaction history: select entity and year, show list of expenses with print/download.
     */
    public function transactionHistory(Request $request)
    {
        $entities = collect([]);
        if (Schema::hasTable('employees')) {
            $uniqueEntityNames = Employee::whereNotNull('entity_name')->where('entity_name', '!=', '')->distinct()->pluck('entity_name')->toArray();
            $entities = collect($uniqueEntityNames)->map(fn ($n) => Employee::where('entity_name', $n)->first())->filter()->values();
        }
        $currentYear = (int) date('Y');
        $availableYears = array_reverse(range($currentYear - 5, $currentYear + 2));
        $selectedEntityId = $request->filled('entity_id') ? (int) $request->entity_id : null;
        $selectedYear = $request->filled('year') ? (int) $request->year : $currentYear;
        $expenseRows = collect([]);
        $entityName = null;

        if ($selectedEntityId) {
            $selectedEmployee = Employee::find($selectedEntityId);
            if ($selectedEmployee) {
                $entityName = $selectedEmployee->entity_name;
                $employeeIds = Employee::where('entity_name', $entityName)->pluck('id')->toArray();
                $budgetIds = EntityBudget::whereIn('employee_id', $employeeIds)->pluck('id')->toArray();
                if (!empty($budgetIds)) {
                    $query = BudgetExpense::with('entityBudget.employee')
                        ->whereIn('entity_budget_id', $budgetIds)
                        ->whereYear('expense_date', $selectedYear);
                    $expenseRows = $this->buildTransactionHistoryRows($query, $selectedYear);
                }
            }
        }

        return view('entity_budget.transaction_history', compact('entities', 'availableYears', 'selectedEntityId', 'selectedYear', 'expenseRows', 'entityName'));
    }

    /**
     * Print view for transaction history (entity + year).
     */
    public function transactionHistoryPrint(Request $request)
    {
        $entityId = $request->get('entity_id');
        $year = (int) $request->get('year', date('Y'));
        if (!$entityId) {
            return redirect()->route('entity_budget.transaction-history')->with('error', 'Select entity and year.');
        }
        $entityName = 'Unknown';
        $expenseRows = collect([]);
        $selectedEmployee = Employee::find($entityId);
        if ($selectedEmployee) {
            $entityName = $selectedEmployee->entity_name;
            $employeeIds = Employee::where('entity_name', $entityName)->pluck('id')->toArray();
            $budgetIds = EntityBudget::whereIn('employee_id', $employeeIds)->pluck('id')->toArray();
            if (!empty($budgetIds)) {
                $query = BudgetExpense::with('entityBudget.employee')
                    ->whereIn('entity_budget_id', $budgetIds)
                    ->whereYear('expense_date', $year);
                $expenseRows = $this->buildTransactionHistoryRows($query, $year);
            }
        }
        return response()->view('entity_budget.transaction_history_print', compact('expenseRows', 'entityName', 'year'));
    }

    /**
     * Download transaction history as PDF/CSV.
     */
    public function transactionHistoryDownload(Request $request)
    {
        $entityId = $request->get('entity_id');
        $year = (int) $request->get('year', date('Y'));
        $format = $request->get('format', 'pdf');
        if (!$entityId) {
            return redirect()->route('entity_budget.transaction-history')->with('error', 'Select entity and year.');
        }
        $entityName = 'Unknown';
        $expenseRows = collect([]);
        $selectedEmployee = Employee::find($entityId);
        if ($selectedEmployee) {
            $entityName = $selectedEmployee->entity_name;
            $employeeIds = Employee::where('entity_name', $entityName)->pluck('id')->toArray();
            $budgetIds = EntityBudget::whereIn('employee_id', $employeeIds)->pluck('id')->toArray();
            if (!empty($budgetIds)) {
                $query = BudgetExpense::with('entityBudget.employee')
                    ->whereIn('entity_budget_id', $budgetIds)
                    ->whereYear('expense_date', $year);
                $expenseRows = $this->buildTransactionHistoryRows($query, $year);
            }
        }
        $filename = 'transaction-history-' . preg_replace('/[^a-z0-9-]/i', '-', $entityName) . '-' . $year . '-' . date('Y-m-d');
        if ($format === 'csv') {
            $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"'];
            $callback = function () use ($expenseRows) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Date', 'Entity', 'Cost Head', 'Expense Type', 'Amount', 'Spent (cumulative)', 'Balance After', 'Description']);
                foreach ($expenseRows as $row) {
                    $e = $row->expense;
                    fputcsv($file, [
                        $e->expense_date ? \Carbon\Carbon::parse($e->expense_date)->format('Y-m-d') : '',
                        $e->entityBudget && $e->entityBudget->employee ? $e->entityBudget->employee->entity_name : 'N/A',
                        $e->cost_head ?? '',
                        $e->entityBudget->expense_type ?? '',
                        number_format($row->amount, 2),
                        number_format($row->cumulative_spent, 2),
                        number_format($row->balance_after, 2),
                        $e->description ?? '',
                    ]);
                }
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }
        $pdf = \PDF::loadView('entity_budget.transaction_history_print', compact('expenseRows', 'entityName', 'year'));
        return $pdf->download($filename . '.pdf');
    }
}