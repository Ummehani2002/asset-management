<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EntityBudget;
use App\Models\Employee;
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
            $expenseTypes = ['Maintenance', 'Capex Software', 'Subscription'];
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
            
            // Filter budgets by entity and year if selected
            if ($hasEntityBudgets) {
                try {
                    $query = EntityBudget::with(['employee', 'expenses']);
                    
                    // Filter by year
                    $selectedYear = $request->get('year', date('Y'));
                    $yearColumn = 'budget_' . $selectedYear;
                    
                    // Only show budgets that have a value for the selected year
                    if (Schema::hasColumn('entity_budgets', $yearColumn)) {
                        $query->whereNotNull($yearColumn);
                    }
                    
                    if ($request->filled('entity_id') && $hasEmployees) {
                        // If filtering by entity, get all budgets for employees with that entity_name
                        try {
                            $selectedEntity = Employee::find($request->entity_id);
                            Log::info('EntityBudget create: Filtering by entity', [
                                'entity_id' => $request->entity_id,
                                'entity_name' => $selectedEntity ? $selectedEntity->entity_name : 'NOT FOUND'
                            ]);
                            
                            if ($selectedEntity) {
                                // Get all employee IDs with this entity_name for direct query
                                $employeeIds = Employee::where('entity_name', $selectedEntity->entity_name)
                                    ->pluck('id')
                                    ->toArray();
                                
                                Log::info('EntityBudget create: Employee IDs for entity', [
                                    'entity_name' => $selectedEntity->entity_name,
                                    'employee_ids' => $employeeIds
                                ]);
                                
                                if (!empty($employeeIds)) {
                                    // Use direct employee_id filter (more reliable than whereHas)
                                    $query->whereIn('employee_id', $employeeIds);
                                } else {
                                    Log::warning('EntityBudget create: No employees found for entity_name: ' . $selectedEntity->entity_name);
                                }
                            } else {
                                Log::warning('EntityBudget create: Selected entity not found for ID: ' . $request->entity_id);
                            }
                        } catch (\Exception $e) {
                            Log::warning('Error filtering budgets by entity: ' . $e->getMessage());
                            Log::warning('Stack trace: ' . $e->getTraceAsString());
                        }
                    } else {
                        // No filter - get all budgets
                        Log::info('EntityBudget create: Loading all budgets (no filter)');
                    }
                    
                    $budgets = $query->get();
                    
                    Log::info('EntityBudget create: Budgets loaded', [
                        'count' => $budgets->count(),
                        'entity_id_filter' => $request->entity_id ?? 'none'
                    ]);
                    
                    // Ensure it's a collection
                    if (!$budgets instanceof \Illuminate\Support\Collection) {
                        $budgets = collect($budgets);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::error('EntityBudget create: Budgets query error: ' . $e->getMessage());
                    Log::error('SQL: ' . ($e->getSql() ?? 'N/A'));
                    Log::error('Bindings: ' . json_encode($e->getBindings() ?? []));
                } catch (\Exception $e) {
                    Log::warning('Error loading budgets: ' . $e->getMessage());
                    Log::warning('Stack trace: ' . $e->getTraceAsString());
                }
            }
            
            // Get available years (current year and future years up to 5 years ahead)
            $currentYear = (int)date('Y');
            $availableYears = range($currentYear, $currentYear + 5);
            $selectedYear = $request->get('year', $currentYear);
            
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
            $expenseTypes = ['Maintenance', 'Capex Software', 'Subscription'];
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
        
        // Filter by year
        if (Schema::hasColumn('entity_budgets', $yearColumn)) {
            $query->whereNotNull($yearColumn);
        }
        
        if ($request->filled('entity_id')) {
            // Filter by entity_name instead of employee_id
            $selectedEntity = Employee::find($request->entity_id);
            if ($selectedEntity) {
                $employeeIds = Employee::where('entity_name', $selectedEntity->entity_name)
                    ->pluck('id')
                    ->toArray();
                if (!empty($employeeIds)) {
                    $query->whereIn('employee_id', $employeeIds);
                }
            }
        }
        
        $budgets = $query->get();
        $entityName = 'All Entities';
        
        if ($request->filled('entity_id')) {
            $entity = Employee::find($request->entity_id);
            $entityName = $entity ? $entity->entity_name : 'Unknown';
        }
        
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
                '#', 'Entity', 'Cost Head', 'Expense Type', 'Budget ' . $year, 'Total Expenses', 'Available Balance'
            ]);

            // Data
            foreach ($budgets as $index => $budget) {
                $budgetAmount = $budget->$yearColumn ?? 0;
                $totalExpenses = $budget->expenses->sum('expense_amount');
                $availableBalance = $budgetAmount - $totalExpenses;
                
                fputcsv($file, [
                    $index + 1,
                    $budget->employee->entity_name ?? 'N/A',
                    $budget->cost_head ?? 'N/A',
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
                'cost_head' => 'required|string',
                'expense_type' => 'required|string',
                'budget_year' => 'required|integer|min:2020|max:2100',
                'budget_amount' => 'required|numeric|min:0'
            ]);
            
            $yearColumn = 'budget_' . $validated['budget_year'];
            
            // Check if column exists, if not, we'll need to add it via migration
            if (!Schema::hasColumn('entity_budgets', $yearColumn)) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Budget column for year ' . $validated['budget_year'] . ' does not exist. Please run migrations.']);
            }
            
            $budgetData = [
                'employee_id' => $validated['entity_id'],
                'cost_head' => $validated['cost_head'],
                'expense_type' => $validated['expense_type'],
                'category' => $request->get('category'),
            ];
            
            $budgetData[$yearColumn] = $validated['budget_amount'];
                    
            $budget = EntityBudget::create($budgetData);

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
}