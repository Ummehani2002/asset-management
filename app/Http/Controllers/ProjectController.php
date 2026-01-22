<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Employee;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        try {
            if (!Schema::hasTable('projects')) {
                Log::warning('projects table does not exist');
                $projects = collect([]);
                return view('projects.index', compact('projects'))
                    ->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
            }

            $query = Project::query();

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('project_id', 'like', "%{$search}%")
                      ->orWhere('project_name', 'like', "%{$search}%")
                      ->orWhere('entity', 'like', "%{$search}%")
                      ->orWhere('project_manager', 'like', "%{$search}%");
                });
            }

            // Entity filter
            if ($request->filled('entity')) {
                $query->where('entity', $request->entity);
            }

            $projects = $query->orderByDesc('created_at')->get();
            return view('projects.index', compact('projects'));
        } catch (\Exception $e) {
            Log::error('Project index error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            $projects = collect([]);
            return view('projects.index', compact('projects'))
                ->with('warning', 'Unable to load projects. Please ensure migrations are run: php artisan migrate --force');
        }
    }

    public function export(Request $request)
    {
        // Export ALL projects, not filtered
        $projects = Project::orderByDesc('created_at')->get();
        $format = $request->get('format', 'pdf');

        if ($format === 'excel' || $format === 'csv') {
            return $this->exportExcel($projects);
        } else {
            return $this->exportPdf($projects);
        }
    }

    private function exportPdf($projects)
    {
        $pdf = \PDF::loadView('projects.export-pdf', compact('projects'));
        return $pdf->download('projects-report-' . date('Y-m-d') . '.pdf');
    }

    private function exportExcel($projects)
    {
        $filename = 'projects-report-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($projects) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['#', 'Project ID', 'Project Name', 'Entity', 'Project Manager', 'PC Secretary']);
            foreach ($projects as $index => $project) {
                fputcsv($file, [
                    $index + 1,
                    $project->project_id,
                    $project->project_name,
                    $project->entity ?? 'N/A',
                    $project->project_manager ?? 'N/A',
                    $project->pc_secretary ?? 'N/A',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        try {
            // Initialize default values
            $employees = collect([]);
            $entities = collect([]);

            // Test database connection first
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                Log::error('Project create: Database connection failed: ' . $e->getMessage());
                return view('projects.create', compact('employees','entities'))
                    ->with('error', 'Database connection failed. Please check your database credentials in Laravel Cloud environment variables.');
            }

            // Check if required tables exist
            try {
                $hasEmployees = Schema::hasTable('employees');
            } catch (\Exception $e) {
                Log::error('Project create: Schema check failed: ' . $e->getMessage());
                return view('projects.create', compact('employees','entities'))
                    ->with('error', 'Unable to check database tables. Please verify database connection.');
            }
            
            if ($hasEmployees) {
                try {
                    $employees = Employee::select('id','name','entity_name')->get();
                    
                    // Ensure they're collections
                    if (!$employees instanceof \Illuminate\Support\Collection) {
                        $employees = collect($employees);
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    Log::error('Project create: Employees query error: ' . $e->getMessage());
                } catch (\Exception $e) {
                    Log::warning('Error loading employees for project create: ' . $e->getMessage());
                }
            }
            
            // Use fixed list of entities
            $entities = [
                'proscape',
                'water in motion',
                'bioscape',
                'tanseeq realty',
                'transmech',
                'timbertech',
                'ventana',
                'garden center'
            ];
            
            return view('projects.create', compact('employees','entities'))
                ->with('warning', $hasEmployees ? null : 'Database tables not found. Please run migrations: php artisan migrate --force');
        } catch (\Throwable $e) {
            Log::error('Project create fatal error: ' . $e->getMessage());
            Log::error('Error class: ' . get_class($e));
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('File: ' . $e->getFile() . ':' . $e->getLine());
            
            $employees = collect([]);
            $entities = collect([]);
            return view('projects.create', compact('employees','entities'))
                ->with('error', 'An error occurred. Please check Laravel Cloud logs for details.');
        }
    }

    public function store(Request $request)
    {
        try {
            // Test database connection first
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                Log::error('Project store: Database connection failed: ' . $e->getMessage());
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Database connection failed. Please check your database credentials in Laravel Cloud environment variables.']);
            }

            // Check if required tables exist
            try {
                if (!Schema::hasTable('projects')) {
                    Log::error('projects table does not exist');
                    return redirect()
                        ->back()
                        ->withInput()
                        ->withErrors(['error' => 'Database table not found. Please run migrations: php artisan migrate --force']);
                }
            } catch (\Exception $e) {
                Log::error('Project store: Schema check failed: ' . $e->getMessage());
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Unable to check database tables. Please verify database connection.']);
            }

            // Build validation rules - make exists rules conditional
            $rules = [
                'project_id'      => 'required|string|max:100|unique:projects,project_id',
                'project_name'    => 'required|string|max:255',
                'entity'          => 'nullable|string|max:255',
                'project_manager' => 'nullable|string|max:255',
                'pc_secretary'    => 'nullable|string|max:255',
            ];

            // Only add exists rules if employees table exists
            if (Schema::hasTable('employees')) {
                $rules['project_manager_id'] = 'nullable|exists:employees,id';
                $rules['pc_secretary_id'] = 'nullable|exists:employees,id';
            }

            $v = $request->validate($rules);

            // if ids provided, convert to names
            if ($request->filled('project_manager_id')) {
                try {
                    $mgr = Employee::find($request->input('project_manager_id'));
                    if ($mgr) $v['project_manager'] = $mgr->name ?? $mgr->entity_name;
                } catch (\Exception $e) {
                    Log::warning('Error finding project manager: ' . $e->getMessage());
                }
            }

            if ($request->filled('pc_secretary_id')) {
                try {
                    $sec = Employee::find($request->input('pc_secretary_id'));
                    if ($sec) $v['pc_secretary'] = $sec->name ?? $sec->entity_name;
                } catch (\Exception $e) {
                    Log::warning('Error finding PC secretary: ' . $e->getMessage());
                }
            }

            Project::create([
                'project_id' => $v['project_id'],
                'project_name' => $v['project_name'],
                'entity' => $v['entity'] ?? null,
                'project_manager' => $v['project_manager'] ?? null,
                'pc_secretary' => $v['pc_secretary'] ?? null,
            ]);

        return redirect()->route('projects.create')->with('success', 'Project saved successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Project store database error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Database error occurred. Please ensure migrations are run: php artisan migrate --force']);
        } catch (\Exception $e) {
            Log::error('Project store error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while saving the project. Please try again.']);
        }
    }

    public function show($id)
    {
        $project = Project::findOrFail($id);
        return view('projects.show', compact('project'));
    }

    public function edit($id)
    {
        $project = Project::findOrFail($id);
        $employees = Employee::select('id','name','entity_name')->get();
        // Use fixed list of entities
        $entities = [
            'proscape',
            'water in motion',
            'bioscape',
            'tanseeq realty',
            'transmech',
            'timbertech',
            'ventana',
            'garden center'
        ];
        return view('projects.edit', compact('project','employees','entities'));
    }

    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $v = $request->validate([
            'project_id'      => 'required|string|max:100|unique:projects,project_id,' . $project->id,
            'project_name'    => 'required|string|max:255',
            'entity'          => 'nullable|string|max:255',
            'project_manager' => 'nullable|string|max:255',
            'pc_secretary'    => 'nullable|string|max:255',
            'project_manager_id' => 'nullable|exists:employees,id',
            'pc_secretary_id'    => 'nullable|exists:employees,id',
        ]);

        if ($request->filled('project_manager_id')) {
            $mgr = Employee::find($request->input('project_manager_id'));
            if ($mgr) $v['project_manager'] = $mgr->name ?? $mgr->entity_name;
        }

        if ($request->filled('pc_secretary_id')) {
            $sec = Employee::find($request->input('pc_secretary_id'));
            if ($sec) $v['pc_secretary'] = $sec->name ?? $sec->entity_name;
        }

        $project->update([
            'project_id' => $v['project_id'],
            'project_name' => $v['project_name'],
            'entity' => $v['entity'] ?? null,
            'project_manager' => $v['project_manager'] ?? null,
            'pc_secretary' => $v['pc_secretary'] ?? null,
        ]);

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Project deleted.');
    }
    
}
