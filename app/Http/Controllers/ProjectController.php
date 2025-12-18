<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Employee;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
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
        $employees = Employee::select('id','name','entity_name')->get();
        $entities = Employee::select('entity_name')->distinct()->pluck('entity_name');
        return view('projects.create', compact('employees','entities'));
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'project_id'      => 'required|string|max:100|unique:projects,project_id',
            'project_name'    => 'required|string|max:255',
            'entity'          => 'nullable|string|max:255',
            'project_manager' => 'nullable|string|max:255',
            'pc_secretary'    => 'nullable|string|max:255',
            // optional: accept manager_id/pc_secretary_id but store names
            'project_manager_id' => 'nullable|exists:employees,id',
            'pc_secretary_id'    => 'nullable|exists:employees,id',
        ]);

        // if ids provided, convert to names
        if ($request->filled('project_manager_id')) {
            $mgr = Employee::find($request->input('project_manager_id'));
            if ($mgr) $v['project_manager'] = $mgr->name ?? $mgr->entity_name;
        }

        if ($request->filled('pc_secretary_id')) {
            $sec = Employee::find($request->input('pc_secretary_id'));
            if ($sec) $v['pc_secretary'] = $sec->name ?? $sec->entity_name;
        }

        Project::create([
            'project_id' => $v['project_id'],
            'project_name' => $v['project_name'],
            'entity' => $v['entity'] ?? null,
            'project_manager' => $v['project_manager'] ?? null,
            'pc_secretary' => $v['pc_secretary'] ?? null,
        ]);

        return redirect()->route('projects.create')->with('success', 'Project saved successfully.');
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
        $entities = Employee::select('entity_name')->distinct()->pluck('entity_name');
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
