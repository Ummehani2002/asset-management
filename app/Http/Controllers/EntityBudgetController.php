<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EntityBudget;
use App\Models\Employee;

class EntityBudgetController extends Controller
{
    public function create(Request $request)
    {
        // Get unique entities - one employee per unique entity_name
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
        
        $costHeads = ['Overhead', 'AMC', 'Software'];
        $expenseTypes = ['Maintenance', 'Capex Software', 'Subscription'];
        
        // Filter budgets by entity if selected
        $query = EntityBudget::with(['employee', 'expenses']);
        if ($request->filled('entity_id')) {
            // If filtering by entity, get all budgets for employees with that entity_name
            $selectedEntity = Employee::find($request->entity_id);
            if ($selectedEntity) {
                $query->whereHas('employee', function($q) use ($selectedEntity) {
                    $q->where('entity_name', $selectedEntity->entity_name);
                });
            }
        }
        $budgets = $query->get();
        
        return view('entity_budget.create', compact('entities', 'costHeads', 'expenseTypes', 'budgets'));
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

        return redirect()->back()->with('success', 'Budget created successfully');
    }
}