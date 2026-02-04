<?php

namespace App\Http\Controllers;

use App\Models\InternetService;
use App\Models\Project;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class InternetServiceController extends Controller
{
    // Display all internet services with search/filter
    public function index(Request $request)
    {
        try {
            if (!Schema::hasTable('internet_services')) {
                Log::warning('internet_services table does not exist');
                $internetServices = collect([]);
                return view('internet-services.index', compact('internetServices'))
                    ->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
            }

            $query = InternetService::with(['project', 'personInCharge', 'projectManager']);

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('project_name', 'like', "%{$search}%")
                      ->orWhere('account_number', 'like', "%{$search}%")
                      ->orWhere('entity', 'like', "%{$search}%")
                      ->orWhere('person_in_charge', 'like', "%{$search}%");
                });
            }

            // Service type filter
            if ($request->filled('service_type')) {
                $query->where('service_type', $request->service_type);
            }

            // Status filter
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Transaction type filter
            if ($request->filled('transaction_type')) {
                $query->where('transaction_type', $request->transaction_type);
            }

            $internetServices = $query->latest()->get();
            
            return view('internet-services.index', compact('internetServices'));
        } catch (\Exception $e) {
            Log::error('InternetService index error: ' . $e->getMessage());
            $internetServices = collect([]);
            return view('internet-services.index', compact('internetServices'))
                ->with('warning', 'Unable to load internet services. Please ensure migrations are run: php artisan migrate --force');
        }
    }


    // Show create form
    public function create()
    {
        try {
            $hasProjects = Schema::hasTable('projects');
            $hasEmployees = Schema::hasTable('employees');
            
            $projects = collect([]);
            $employees = collect([]);
            
            if ($hasProjects) {
                try {
                    $projects = Project::select('id','project_id','project_name','entity')->orderBy('project_id')->get();
                } catch (\Exception $e) {
                    Log::warning('Error loading projects for internet service create: ' . $e->getMessage());
                }
            }
            
            if ($hasEmployees) {
                try {
                    $employees = Employee::orderBy('name')->get();
                } catch (\Exception $e) {
                    Log::warning('Error loading employees for internet service create: ' . $e->getMessage());
                }
            }
            
            $hasAllTables = $hasProjects && $hasEmployees;
            return view('internet-services.create', compact('projects', 'employees'))
                ->with('warning', $hasAllTables ? null : 'Database tables not found. Please run migrations: php artisan migrate --force');
        } catch (\Exception $e) {
            Log::error('InternetService create error: ' . $e->getMessage());
            $projects = collect([]);
            $employees = collect([]);
            return view('internet-services.create', compact('projects', 'employees'))
                ->with('warning', 'Unable to load form data. Please ensure migrations are run: php artisan migrate --force');
        }
    }


    // Store a new internet service
    public function store(Request $request)
    {
        try {
            if (!Schema::hasTable('internet_services')) {
                Log::error('internet_services table does not exist');
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Database table not found. Please run migrations: php artisan migrate --force']);
            }

            $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'service_type' => 'required|in:datacard,fixed,service',
            'transaction_type' => 'nullable|in:assign,return',
            'account_number' => 'nullable|string|max:100',
            'service_start_date' => 'required|date',
            'service_end_date' => 'nullable|date|after:service_start_date',

            // Employee Master ID
            'person_in_charge_id' => 'required|exists:employees,id',
            'project_manager_id' => 'nullable|exists:employees,id',
            'document_controller_id' => 'nullable|exists:employees,id',
            
            'pm_contact_number' => 'nullable|string|max:255',
            'document_controller_number' => 'nullable|string|max:255',
            'mrc' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'pr_number' => 'nullable|string|max:255',
            'po_number' => 'nullable|string|max:255',
            'status' => 'required|in:active,suspend,closed'
        ]);

        // Fetch Project
        $project = Project::findOrFail($validated['project_id']);

        // Fetch Employees
        $emp = Employee::findOrFail($validated['person_in_charge_id']);
        
        // Auto-fill PM data if selected
        if (!empty($validated['project_manager_id'])) {
            $pm = Employee::find($validated['project_manager_id']);
            if ($pm) {
                $validated['project_manager'] = $pm->name ?? $pm->entity_name;
                if (empty($validated['pm_contact_number'])) {
                    $validated['pm_contact_number'] = $pm->phone ?? 'N/A';
                }
            }
        }
        
        // Auto-fill Document Controller data if selected
        if (!empty($validated['document_controller_id'])) {
            $dm = Employee::find($validated['document_controller_id']);
            if ($dm) {
                $validated['document_controller'] = $dm->name ?? $dm->entity_name;
                if (empty($validated['document_controller_number'])) {
                    $validated['document_controller_number'] = $dm->phone ?? 'N/A';
                }
            }
        }

            // Auto-fill data (use entity from form if provided, otherwise from project)
            if (empty($validated['entity'])) {
                $validated['entity'] = $project->entity;
            }
            $validated['project_name'] = $project->project_name;
            $validated['person_in_charge'] = $emp->name ?? $emp->entity_name;
            $validated['contact_details'] = $emp->phone ?? 'N/A';
            
            // Set transaction type to assign for new services
            if (empty($validated['transaction_type'])) {
                $validated['transaction_type'] = 'assign';
            }

            Log::info('Creating internet service with data:', $validated);

            $internetService = InternetService::create($validated);

            Log::info('Internet service created successfully. ID: ' . $internetService->id);

            // Verify the service was actually saved
            $savedService = InternetService::find($internetService->id);
            if (!$savedService) {
                Log::error('Internet service was not saved to database!');
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Failed to save internet service. Please try again.']);
            }

            return redirect()->route('internet-services.create')
                ->with('success', 'Internet service created successfully.')
                ->with('saved_service_id', $internetService->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('InternetService store database error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Database error occurred. Please ensure migrations are run: php artisan migrate --force']);
        } catch (\Exception $e) {
            Log::error('InternetService store error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while saving the internet service. Please try again.']);
        }
    }


    // Edit page
    public function edit(InternetService $internetService)
    {
        $projects = Project::select('id','project_id','project_name','entity')->orderBy('project_id')->get();
        $employees = Employee::orderBy('name')->get();
        $allServices = InternetService::with('project')->orderBy('project_name')->get();

        return view('internet-services.edit', compact('internetService', 'projects', 'employees', 'allServices'));
    }
    
    // Get service details via AJAX
    public function getServiceDetails($id)
    {
        $service = InternetService::with('project')->findOrFail($id);
        
        return response()->json([
            'id' => $service->id,
            'project_id' => $service->project_id,
            'service_type' => $service->service_type,
            'transaction_type' => $service->transaction_type,
            'pr_number' => $service->pr_number,
            'po_number' => $service->po_number,
            'account_number' => $service->account_number,
            'mrc' => $service->mrc,
            'service_start_date' => $service->service_start_date ? $service->service_start_date->format('Y-m-d') : null,
            'service_end_date' => $service->service_end_date ? $service->service_end_date->format('Y-m-d') : null,
            'cost' => $service->cost,
            'person_in_charge_id' => $service->person_in_charge_id,
            'project_manager' => $service->project_manager,
            'pm_contact_number' => $service->pm_contact_number,
            'document_controller' => $service->document_controller,
            'document_controller_number' => $service->document_controller_number,
            'status' => $service->status,
        ]);
    }


    // Update record
    public function update(Request $request, InternetService $internetService)
    {
        $validated = $request->validate([
            'entity' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'service_type' => 'required|in:datacard,fixed,service',
            'transaction_type' => 'nullable|in:assign,return',
            'account_number' => 'nullable|string|max:100',
            'service_start_date' => 'required|date',
            'service_end_date' => 'nullable|date|after:service_start_date',

            // Employee IDs
            'person_in_charge_id' => 'required|exists:employees,id',
            'project_manager_id' => 'nullable|exists:employees,id',
            'document_controller_id' => 'nullable|exists:employees,id',

            'pm_contact_number' => 'nullable|string|max:255',
            'document_controller_number' => 'nullable|string|max:255',
            'mrc' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,suspend,closed'
        ]);

        // Fetch Project
        $project = Project::findOrFail($validated['project_id']);

        // Fetch Employees
        $emp = Employee::findOrFail($validated['person_in_charge_id']);
        
        // Auto-fill PM data if selected
        if (!empty($validated['project_manager_id'])) {
            $pm = Employee::find($validated['project_manager_id']);
            if ($pm) {
                $validated['project_manager'] = $pm->name ?? $pm->entity_name;
                if (empty($validated['pm_contact_number'])) {
                    $validated['pm_contact_number'] = $pm->phone ?? 'N/A';
                }
            }
        }
        
        // Auto-fill Document Controller data if selected
        if (!empty($validated['document_controller_id'])) {
            $dm = Employee::find($validated['document_controller_id']);
            if ($dm) {
                $validated['document_controller'] = $dm->name ?? $dm->entity_name;
                if (empty($validated['document_controller_number'])) {
                    $validated['document_controller_number'] = $dm->phone ?? 'N/A';
                }
            }
        }

        // Auto fill (use entity from form if provided, otherwise from project)
        if (empty($validated['entity'])) {
            $validated['entity'] = $project->entity;
        }
        $validated['project_name'] = $project->project_name;
        $validated['person_in_charge'] = $emp->name ?? $emp->entity_name;
        $validated['contact_details'] = $emp->phone ?? 'N/A';
        
        // Cost = MRC (per month) × number of months. 30 days = 1 month, 60 = 2 months, etc.
        if (!empty($validated['service_end_date']) && isset($validated['mrc']) && (float)$validated['mrc'] > 0) {
            $startDate = new \DateTime($validated['service_start_date']);
            $endDate = new \DateTime($validated['service_end_date']);
            $diffDays = $startDate->diff($endDate)->days + 1; // +1 to include both start and end days
            $months = $diffDays / 30; // 30 days = 1 month
            $validated['cost'] = round((float)$validated['mrc'] * $months, 2);
        }

        $internetService->update($validated);

        return redirect()->route('internet-services.index')
            ->with('success', 'Internet service updated successfully.');
    }


    // Return service (show form)
    public function return(InternetService $internetService)
    {
        if ($internetService->service_end_date) {
            return redirect()->route('internet-services.index')
                ->with('error', 'This service has already been returned.');
        }
        
        return view('internet-services.return', compact('internetService'));
    }
    
    // Process return
    public function processReturn(Request $request, InternetService $internetService)
    {
        $validated = $request->validate([
            'service_end_date' => 'required|date|after:service_start_date',
        ]);
        
        if ($internetService->service_end_date) {
            return redirect()->route('internet-services.index')
                ->with('error', 'This service has already been returned.');
        }
        
        // Cost = MRC × number of months. 30 days = 1 month, 60 = 2 months (1 month = MRC, 2 months = double)
        $mrc = (float)($internetService->mrc ?? 0);
        $startDate = $internetService->service_start_date;
        $endDate = new \DateTime($validated['service_end_date']);
        $diffDays = $startDate->diff($endDate)->days + 1;
        $months = $diffDays / 30; // 30 days = 1 month
        $cost = $mrc * $months;
        
        // Update service
        $internetService->update([
            'service_end_date' => $validated['service_end_date'],
            'cost' => round($cost, 2),
            'status' => 'closed',
            'transaction_type' => 'return',
        ]);
        
        return redirect()->route('internet-services.index')
            ->with('success', 'Service returned successfully. Cost calculated: ' . number_format($cost, 2))
            ->with('returned_service_id', $internetService->id);
    }

    // Delete
    public function destroy(InternetService $internetService)
    {
        $internetService->delete();

        return redirect()->route('internet-services.index')
            ->with('success', 'Internet service deleted successfully.');
    }

    // Export to PDF or Excel
    public function export(Request $request)
    {
        $query = InternetService::with(['project', 'personInCharge', 'projectManager']);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('project_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('entity', 'like', "%{$search}%")
                  ->orWhere('person_in_charge', 'like', "%{$search}%");
            });
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        $internetServices = $query->latest()->get();
        $format = $request->get('format', 'pdf');

        if ($format === 'excel' || $format === 'csv') {
            return $this->exportExcel($internetServices);
        } else {
            return $this->exportPdf($internetServices);
        }
    }

    private function exportPdf($internetServices)
    {
        $pdf = \PDF::loadView('internet-services.export-pdf', compact('internetServices'));
        return $pdf->download('internet-services-report-' . date('Y-m-d') . '.pdf');
    }

    private function exportExcel($internetServices)
    {
        $filename = 'internet-services-report-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($internetServices) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                '#', 'Project', 'Entity', 'Service Type', 'Transaction Type', 
                'Account Number', 'Start Date', 'End Date', 'Person in Charge', 
                'PM Contact', 'Document Controller', 'Document Controller Number',
                'MRC', 'Cost', 'PR Number', 'PO Number', 'Status'
            ]);

            // Data
            foreach ($internetServices as $index => $service) {
                fputcsv($file, [
                    $index + 1,
                    $service->project_name ?? 'N/A',
                    $service->entity ?? 'N/A',
                    ucfirst($service->service_type ?? 'N/A'),
                    ucfirst($service->transaction_type ?? 'N/A'),
                    $service->account_number ?? 'N/A',
                    $service->service_start_date ? $service->service_start_date->format('Y-m-d') : 'N/A',
                    $service->service_end_date ? $service->service_end_date->format('Y-m-d') : 'N/A',
                    $service->person_in_charge ?? 'N/A',
                    $service->pm_contact_number ?? 'N/A',
                    $service->document_controller ?? 'N/A',
                    $service->document_controller_number ?? 'N/A',
                    $service->mrc ?? '0.00',
                    $service->cost ?? '0.00',
                    $service->pr_number ?? 'N/A',
                    $service->po_number ?? 'N/A',
                    ucfirst($service->status ?? 'N/A'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function downloadForm($id)
    {
        $internetService = InternetService::with(['project', 'personInCharge', 'projectManager'])->findOrFail($id);
        $pdf = \PDF::loadView('internet-services.download-form', compact('internetService'));
        return $pdf->download('internet-service-' . $internetService->id . '-' . date('Y-m-d') . '.pdf');
    }
}
