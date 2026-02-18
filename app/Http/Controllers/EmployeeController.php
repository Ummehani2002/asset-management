<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\Asset;
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

        $employees = Employee::orderBy('id', 'desc')->get();
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

    public function search(Request $request)
    {
        try {
            // Check if employees table exists
            if (!Schema::hasTable('employees')) {
                Log::warning('employees table does not exist');
                $employees = collect([]);
                return view('employees.search', compact('employees'))
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
            return view('employees.search', compact('employees'));
        } catch (\Exception $e) {
            Log::error('Employee search error: ' . $e->getMessage());
            $employees = collect([]);
            return view('employees.search', compact('employees'))
                ->with('warning', 'Unable to search employees. Please try again.');
        }
    }
   public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        try {
            // Check database connection first
            try {
                $dbName = DB::connection()->getDatabaseName();
                Log::info('Connected to database: ' . $dbName);
            } catch (\Exception $e) {
                Log::error('Database connection error: ' . $e->getMessage());
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Database connection failed. Please check your database configuration.']);
            }

            // Check if employees table exists
            $tableExists = Schema::hasTable('employees');
            Log::info('Employees table exists check: ' . ($tableExists ? 'YES' : 'NO'));
            
            if (!$tableExists) {
                // Try to list all tables for debugging
                try {
                    $tables = DB::select('SHOW TABLES');
                    $tableList = array_map(function($table) {
                        return array_values((array)$table)[0];
                    }, $tables);
                    Log::warning('Available tables: ' . implode(', ', $tableList));
                } catch (\Exception $e) {
                    Log::warning('Could not list tables: ' . $e->getMessage());
                }
                
                Log::error('employees table does not exist in database: ' . DB::connection()->getDatabaseName());
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Database table not found. Please run migrations: php artisan migrate --force. Connected to database: ' . DB::connection()->getDatabaseName()]);
            }
            
            // Check if sessions table exists (for success messages)
            if (!Schema::hasTable('sessions')) {
                Log::warning('sessions table does not exist - success messages may not work');
            }

            $data = $request->validate([
                'employee_id'    => 'required|unique:employees,employee_id|max:20',
                'name'           => 'nullable|string|max:100',
                'email'          => 'nullable|email|max:100',
                'phone'          => 'nullable|string|max:20',
                'entity_name'    => 'required|string|max:100',
                'department_name'=> 'required|string|max:100',
                'designation'    => 'nullable|string|max:100',
            ]);

            Log::info('Creating employee with data:', $data);
            
            $employee = Employee::create($data);
            
            Log::info('Employee created successfully. ID: ' . $employee->id);
            
            // Verify the employee was actually saved
            $savedEmployee = Employee::find($employee->id);
            if (!$savedEmployee) {
                Log::error('Employee was not saved to database!');
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Failed to save employee. Please try again.']);
            }

            return redirect()
                ->route('employees.index')
                ->with('success', 'Employee added successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to show field-specific errors
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Employee store database error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Database error occurred. Please ensure migrations are run: php artisan migrate --force']);
        } catch (\Exception $e) {
            Log::error('Employee store error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while saving the employee. Please try again.']);
        }
    }
public function edit($id)
{
    $employee = Employee::findOrFail($id);
    return view('employees.edit', compact('employee'));
}


    public function update(Request $request, $id)
{
    try {
        $employee = Employee::findOrFail($id);

        if (Schema::hasColumn('employees', 'is_active') && $employee->is_active === false) {
            return redirect()->route('employees.index')->with('error', 'Cannot update inactive employee. Employee details are locked after returning all assets.');
        }

        $request->validate([
            'email'           => 'nullable|email|max:100',
            'phone'           => 'nullable|string|max:20',
            'entity_name'     => 'nullable|string|max:100',
            'department_name' => 'nullable|string|max:100',
            'designation'     => 'nullable|string|max:100',
        ]);

        $employee->email = $request->input('email');
        $employee->phone = $request->input('phone');
        $employee->entity_name = $request->input('entity_name') ?: null;
        $employee->department_name = $request->input('department_name') ?: null;
        if (Schema::hasColumn('employees', 'designation')) {
            $employee->designation = $request->input('designation');
        }
        $employee->save();
        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    } catch (\Illuminate\Validation\ValidationException $e) {
        throw $e;
    } catch (\Exception $e) {
        Log::error('Employee update failed', [
            'employee_id' => $id,
            'message'     => $e->getMessage(),
            'trace'       => $e->getTraceAsString(),
        ]);
        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['error' => 'Unable to update employee. Please try again or contact support.']);
    }
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
public function autocompleteSearch(Request $request)
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
public function showImportForm()
{
    $entities = \App\Helpers\EntityHelper::getEntities();
    return view('employees.import', compact('entities'));
}

public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:csv,txt',
        'default_entity' => 'nullable|string|max:100',
        'delete_existing' => 'nullable|boolean',
        'sync_entities' => 'nullable|boolean',
    ]);

    $defaultEntity = trim($request->default_entity ?? '') ?: 'N/A';
    $deleteExisting = (bool) $request->delete_existing;
    $syncEntities = (bool) $request->sync_entities;

    try {
        if ($deleteExisting) {
            $count = Employee::count();
            if (Schema::hasTable('entities') && Schema::hasColumn('entities', 'asset_manager_id')) {
                DB::table('entities')->update(['asset_manager_id' => null]);
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Employee::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            Log::info("Deleted {$count} existing employees before import.");
        }

        $file = $request->file('file');
        $result = $this->importFromCsv($file, $defaultEntity);

        $imported = is_array($result) ? $result['count'] : $result;
        $entityNames = is_array($result) ? ($result['entities'] ?? []) : [];

        if ($syncEntities && !empty($entityNames) && Schema::hasTable('entities')) {
            $this->syncEntitiesFromImport($entityNames, $deleteExisting);
        }

        return back()->with('success', "Successfully imported {$imported} employees." . ($syncEntities && !empty($entityNames) ? ' Entities updated.' : ''));
    } catch (\Exception $e) {
        Log::error('Employee import error: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        return back()->with('error', 'Import failed: ' . $e->getMessage());
    }
}

private function importFromCsv($file, $defaultEntity)
{
    $path = $file->getRealPath();
    $content = file_get_contents($path);
    if ($content === false) {
        throw new \Exception('Could not read file.');
    }
    $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
    $tempPath = sys_get_temp_dir() . '/emp_import_' . uniqid() . '.csv';
    file_put_contents($tempPath, $content);

    $handle = fopen($tempPath, 'r');
    if (!$handle) {
        @unlink($tempPath);
        throw new \Exception('Could not open file.');
    }

    $headers = [];
    $imported = 0;
    $entityNames = [];
    $rowNum = 0;

    while (($row = fgetcsv($handle)) !== false) {
        $rowNum++;
        if ($rowNum === 1) {
            $headers = array_map(function ($h) {
                return trim(preg_replace('/^\xEF\xBB\xBF/', '', $h));
            }, $row);
            continue;
        }

        $data = array_combine($headers, array_pad($row, count($headers), ''));
        if (!$data || count(array_filter($row)) === 0) continue;

        $employee = $this->mapRowToEmployee($data, $defaultEntity);
        if ($employee) {
            try {
                Employee::create($employee);
                $imported++;
                $ent = trim($employee['entity_name'] ?? '');
                if ($ent && !in_array($ent, $entityNames, true)) {
                    $entityNames[] = $ent;
                }
            } catch (\Exception $e) {
                Log::warning("Row {$rowNum} skipped: " . $e->getMessage());
            }
        }
    }
    fclose($handle);
    @unlink($tempPath);
    return ['count' => $imported, 'entities' => $entityNames];
}

private function mapRowToEmployee(array $data, $defaultEntity)
{
    $normalize = function ($key) use ($data) {
        $keys = array_keys($data);
        foreach ($keys as $k) {
            if (str_replace(' ', '', strtolower($k)) === str_replace(' ', '', strtolower($key))) {
                return trim($data[$k] ?? '');
            }
        }
        return trim($data[$key] ?? '');
    };

    $employeeId = $normalize('EmployeeID') ?: $normalize('Employee ID');
    if (empty($employeeId)) return null;

    $name = $normalize('Name');
    $email = $normalize('Email');
    $phone = $normalize('Phone');
    $designation = $normalize('Designation');
    // Normalize old designation to new one (Excel may have "Project Engineer", "Project Engineer (Civil)", etc.)
    if (preg_match('/^Project\s+Engineer\b/i', trim($designation ?? ''))) {
        $designation = 'Assistant Project Manager';
    }
    $department = $normalize('Department Name') ?: $normalize('Department');
    $entity = $normalize('Entity') ?: $normalize('Entity Name') ?: $normalize('Company');

    return [
        'employee_id' => (string) $employeeId,
        'name' => $name ?: null,
        'email' => $email ?: null,
        'phone' => $phone ?: null,
        'entity_name' => $entity ?: $defaultEntity,
        'department_name' => $department ?: 'N/A',
        'designation' => $designation ?: null,
        'is_active' => true,
    ];
}

private function syncEntitiesFromImport(array $entityNames, $replaceExisting)
{
    if (!Schema::hasTable('entities')) return;

    if ($replaceExisting) {
        \App\Models\Entity::query()->delete();
    }

    foreach ($entityNames as $name) {
        $name = trim($name);
        if (empty($name)) continue;
        if (\App\Models\Entity::where('name', $name)->exists()) continue;
        \App\Models\Entity::create(['name' => $name]);
    }
}
   public function autocomplete(Request $request)
    {
        try {
            if (!Schema::hasTable('employees')) {
                return response()->json([]);
            }

            $query = trim($request->get('query', ''));
            
            if (empty($query)) {
                return response()->json([]);
            }

            $like = '%' . addcslashes($query, '%_\\') . '%';

            $selectCols = ['id', 'name', 'entity_name', 'employee_id', 'email'];
            if (Schema::hasColumn('employees', 'department_name')) {
                $selectCols[] = 'department_name';
            }
            if (Schema::hasColumn('employees', 'designation')) {
                $selectCols[] = 'designation';
            }

            $employees = Employee::where(function($q) use ($like) {
                $q->where('name', 'LIKE', $like)
                  ->orWhere('entity_name', 'LIKE', $like)
                  ->orWhere('employee_id', 'LIKE', $like)
                  ->orWhere('email', 'LIKE', $like)
                  ->orWhere('phone', 'LIKE', $like);
                if (Schema::hasColumn('employees', 'department_name')) {
                    $q->orWhere('department_name', 'LIKE', $like);
                }
                if (Schema::hasColumn('employees', 'designation')) {
                    $q->orWhere('designation', 'LIKE', $like);
                }
            })
                ->orderBy('name', 'asc')
                ->take(25)
                ->get($selectCols);

            $queryLower = strtolower($query);
            $employees = $employees->sortBy(function($employee) use ($queryLower) {
                $name = strtolower($employee->name ?? $employee->entity_name ?? '');
                $email = strtolower($employee->email ?? '');
                $dept = strtolower($employee->department_name ?? '');
                $empId = strtolower($employee->employee_id ?? '');
                if (strpos($name, $queryLower) === 0) return 1;
                if (strpos($empId, $queryLower) === 0) return 2;
                if (strpos($email, $queryLower) === 0) return 3;
                if (strpos($name, $queryLower) !== false) return 4;
                if (strpos($dept, $queryLower) !== false) return 5;
                return 6;
            })->values();

            return response()->json($employees);
        } catch (\Throwable $e) {
            Log::error('Employee autocomplete error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
