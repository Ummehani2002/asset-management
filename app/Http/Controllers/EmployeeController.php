<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\Asset;
use App\Imports\EmployeesImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{

    public function index(Request $request)
    {
        try {
            // Check if employees table exists
            if (!Schema::hasTable('employees')) {
                Log::warning('employees table does not exist');
                $employees = collect([]); // Empty collection
                return view('employees.index', compact('employees'))
                    ->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
            }

            $query = Employee::query();

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('employee_id', 'LIKE', "%{$search}%")
                      ->orWhere('entity_name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
                });
            }

            $employees = $query->orderBy('id', 'desc')->get();
            return view('employees.index', compact('employees'));
        } catch (\Exception $e) {
            Log::error('Employee index error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Return empty list instead of crashing
            $employees = collect([]);
            return view('employees.index', compact('employees'))
                ->with('warning', 'Unable to load employees. Please ensure migrations are run: php artisan migrate --force');
        }
    }
   public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id'    => 'required|unique:employees,employee_id|max:20',
            'name'           => 'nullable|string|max:100',
            'email'          => 'nullable|email|max:100',
            'phone'          => 'nullable|string|max:20',
            'entity_name'    => 'required|string|max:100',
            'department_name'=> 'required|string|max:100',
        ]);

        Employee::create($data);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Employee added successfully.');
    }
public function edit($id)
{
    $employee = Employee::findOrFail($id);
    return view('employees.edit', compact('employee'));
}


    public function update(Request $request, $id)
{
    $request->validate([
        'name' => 'required|string|max:255',
        
    ]);

    $employee = Employee::findOrFail($id);
    $employee->name = $request->input('name');
    $employee->save();
    return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
}


public function destroy($id)
{
    $employee = Employee::findOrFail($id);

    if ($employee->assetTransactions()->count() > 0) {
        return redirect()->route('employees.index')->with('error', 'Cannot delete employee with assigned asset transactions.');
    }

    $employee->delete();

    return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
}
public function search(Request $request)
{
    $query = $request->get('q', '');
    $employees = \App\Models\Employee::where('name', 'LIKE', "%{$query}%")
        ->orWhere('employee_id', 'LIKE', "%{$query}%")
        ->limit(10)
        ->get(['id', 'name', 'employee_id', 'email']);

    return response()->json($employees);
}
public function getDetails($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        return response()->json([
            'id' => $employee->id,
            'name' => $employee->name ?? $employee->entity_name,
            'department' => $employee->department ?? 'N/A',  
            'location' => $employee->location ?? 'N/A',
            'email' => $employee->email ?? 'N/A',
            'phone' => $employee->phone ?? 'N/A',
        ]);
    }
public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv'
    ]);

    try {
        Excel::import(new EmployeesImport, $request->file('file'));
        return back()->with('success', 'Employees imported successfully!');
    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
        return back()->withErrors($e->failures());
    }
}

public function showImportForm()
{
    return view('employees.import');
}
   public function autocomplete(Request $request)
    {
        $query = trim($request->get('query', ''));
        
        if(empty($query)) {
            return response()->json([]);
        }

        // Search by name (starts with first, then contains) or employee_id
        $employees = Employee::where(function($q) use ($query) {
                $q->where('name', 'LIKE', "{$query}%")  // Starts with (priority)
                  ->orWhere('name', 'LIKE', "%{$query}%") // Contains
                  ->orWhere('entity_name', 'LIKE', "{$query}%") // Entity name starts with
                  ->orWhere('entity_name', 'LIKE', "%{$query}%") // Entity name contains
                  ->orWhere('employee_id', 'LIKE', "{$query}%"); // Employee ID starts with
            })
            ->orderBy('name', 'asc')
            ->take(15)
            ->get(['id', 'name', 'entity_name', 'employee_id', 'email']);

        // Sort results: names starting with query first
        $employees = $employees->sortBy(function($employee) use ($query) {
            $name = strtolower($employee->name ?? $employee->entity_name ?? '');
            $queryLower = strtolower($query);
            
            if(strpos($name, $queryLower) === 0) return 1; // Starts with
            return 2; // Contains
        })->values();

        return response()->json($employees);
    }

    public function export(Request $request)
    {
        // Always export ALL employees, not filtered
        $employees = Employee::orderBy('id', 'desc')->get();
        $format = $request->get('format', 'pdf');

        if ($format === 'csv') {
            return $this->exportCsv($employees);
        } else {
            return $this->exportPdf($employees);
        }
    }

    private function exportPdf($employees)
    {
        $pdf = \PDF::loadView('employees.export-pdf', compact('employees'));
        return $pdf->download('employees-report-' . date('Y-m-d') . '.pdf');
    }

    private function exportCsv($employees)
    {
        $filename = 'employees-report-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($employees) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, ['#', 'Employee ID', 'Name', 'Email', 'Phone', 'Entity', 'Department', 'Created At']);

            // Data
            foreach ($employees as $index => $employee) {
                fputcsv($file, [
                    $index + 1,
                    $employee->employee_id,
                    $employee->name ?? 'N/A',
                    $employee->email ?? 'N/A',
                    $employee->phone ?? 'N/A',
                    $employee->entity_name ?? 'N/A',
                    $employee->department_name ?? 'N/A',
                    $employee->created_at ? $employee->created_at->format('Y-m-d') : 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

}
