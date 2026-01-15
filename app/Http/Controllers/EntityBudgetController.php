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
    public function create(Request $request)
    {
        try {
            // Initialize default values
            $entities = collect([]);
            $costHeads = ['Overhead', 'AMC', 'Software'];
            $expenseTypes = ['Maintenance', 'Capex Software', 'Subscription'];
            $budgets = collect([]);

            // Test database connection first
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                Log::error('EntityBudget create: Database connection failed: ' . $e->getMessage());
                return view('entity_budget.create', compact('entities', 'costHeads', 'expenseTypes', 'budgets'))
                    ->with('error', 'Database connection failed. Please check your database credentials in Laravel Cloud environment variables.');
            }

            // Check if required tables exist
            try {
                $hasEmployees = Schema::hasTable('employees');
                $hasEntityBudgets = Schema::hasTable('entity_budgets');
            } catch (\Exception $e) {
                Log::error('EntityBudget create: Schema check failed: ' . $e->getMessage());
                return view('entity_budget.create', compact('entities', 'costHeads', 'expenseTypes', 'budgets'))
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
            
            // Filter budgets by entity if selected
            if ($hasEntityBudgets) {
                try {
                    $query = EntityBudget::with(['employee', 'expenses']);
                    if ($request->filled('entity_id') && $hasEmployees) {
                        // If filtering by entity, get all budgets for employees with that entity_name
                        try {
                            $selectedEntity = Employee::find($request->entity_id);
                            if ($selectedEntity) {
                                $query->whereHas('employee', function($q) use ($selectedEntity) {
                                    $q->where('entity_name', $selectedEntity->entity_name);
                                });
                            }
                        } catch (\Exception $e) {
                            Log::warning('Error filtering budgets by entity: ' . $e->getMessage());
                        }
                    }
                    $budgets = $query->get();
                    
                    // Ensure it's a collection
                    if (!$budgets instanceof \Illuminate\Support\Collection) {
                        $budgets = collect($budgets);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::error('EntityBudget create: Budgets query error: ' . $e->getMessage());
                } catch (\Exception $e) {
                    Log::warning('Error loading budgets: ' . $e->getMessage());
                }
            }
            
            $hasAllTables = $hasEmployees && $hasEntityBudgets;
            return view('entity_budget.create', compact('entities', 'costHeads', 'expenseTypes', 'budgets'))
                ->with('warning', $hasAllTables ? null : 'Database tables not found. Please run migrations: php artisan migrate --force');
        } catch (\Throwable $e) {
            Log::error('EntityBudget create fatal error: ' . $e->getMessage());
            Log::error('Error class: ' . get_class($e));
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('File: ' . $e->getFile() . ':' . $e->getLine());
            
            // Return with default values
            $entities = collect([]);
            $costHeads = ['Overhead', 'AMC', 'Software'];
            $expenseTypes = ['Maintenance', 'Capex Software', 'Subscription'];
            $budgets = collect([]);
            return view('entity_budget.create', compact('entities', 'costHeads', 'expenseTypes', 'budgets'))
                ->with('error', 'An error occurred. Please check Laravel Cloud logs for details.');
        }
    }

    public function export(Request $request)
    {
        $query = EntityBudget::with(['employee', 'expenses']);
        
        if ($request->filled('entity_id')) {
            // Filter by entity_name instead of employee_id
            $selectedEntity = Employee::find($request->entity_id);
            if ($selectedEntity) {
                $query->whereHas('employee', function($q) use ($selectedEntity) {
                    $q->where('entity_name', $selectedEntity->entity_name);
                });
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
            return $this->exportExcel($budgets, $entityName);
        } else {
            return $this->exportPdf($budgets, $entityName);
        }
    }

    private function exportPdf($budgets, $entityName)
    {
        $pdf = \PDF::loadView('entity_budget.export-pdf', compact('budgets', 'entityName'));
        return $pdf->download('entity-budget-' . str_replace(' ', '-', $entityName) . '-' . date('Y-m-d') . '.pdf');
    }

    private function exportExcel($budgets, $entityName)
    {
        $filename = 'entity-budget-' . str_replace(' ', '-', $entityName) . '-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($budgets) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                '#', 'Entity', 'Cost Head', 'Expense Type', 'Budget 2025', 'Total Expenses', 'Available Balance'
            ]);

            // Data
            foreach ($budgets as $index => $budget) {
                $totalExpenses = $budget->expenses->sum('expense_amount');
                $availableBalance = $budget->budget_2025 - $totalExpenses;
                
                fputcsv($file, [
                    $index + 1,
                    $budget->employee->entity_name ?? 'N/A',
                    $budget->cost_head ?? 'N/A',
                    $budget->expense_type ?? 'N/A',
                    number_format($budget->budget_2025, 2),
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
                'budget_2025' => 'required|numeric|min:0'
            ]);
                    
            EntityBudget::create([
                'employee_id' => $validated['entity_id'],
                'cost_head' => $validated['cost_head'],
                'expense_type' => $validated['expense_type'],
                'budget_2025' => $validated['budget_2025']
            ]);

            // Redirect back to create page with entity_id filter to show the newly created budget
            return redirect()->route('entity_budget.create', ['entity_id' => $validated['entity_id']])
                ->with('success', 'Budget created successfully');
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
}