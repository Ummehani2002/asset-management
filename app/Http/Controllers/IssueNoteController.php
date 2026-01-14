<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\IssueNote;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class IssueNoteController extends Controller
{
    public function create()
    {
        try {
            $hasEmployees = Schema::hasTable('employees');
            $employees = collect([]);
            
            if ($hasEmployees) {
                try {
                    $employees = Employee::all();
                } catch (\Exception $e) {
                    Log::warning('Error loading employees for issue note create: ' . $e->getMessage());
                }
            }
            
            return view('issue-note.create', compact('employees'))
                ->with('warning', $hasEmployees ? null : 'Database tables not found. Please run migrations: php artisan migrate --force');
        } catch (\Exception $e) {
            Log::error('IssueNote create error: ' . $e->getMessage());
            $employees = collect([]);
            return view('issue-note.create', compact('employees'))
                ->with('warning', 'Unable to load form data. Please ensure migrations are run: php artisan migrate --force');
        }
    }

    public function store(Request $request)
    {
        try {
            if (!Schema::hasTable('issue_notes')) {
                Log::error('issue_notes table does not exist');
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Database table not found. Please run migrations: php artisan migrate --force']);
            }

            $validated = $request->validate([
                'employee_id' => 'nullable|exists:employees,id',
                'department' => 'nullable|string|max:255',
                'entity' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'system_code' => 'nullable|string|max:255',
                'printer_code' => 'nullable|string|max:255',
                'issued_date' => 'nullable|date',
                'software_installed' => 'nullable|string',
                'items' => 'nullable|array',
                'user_signature' => 'nullable|string',
                'manager_signature' => 'nullable|string',
            ]);

            $validated['items'] = $request->input('items', []);
            $validated['note_type'] = 'issue';
            
            // SAVE SIGNATURE FUNCTION
            try {
                $validated['user_signature'] = $this->saveSignature($request->user_signature);
                $validated['manager_signature'] = $this->saveSignature($request->manager_signature);
            } catch (\Exception $e) {
                Log::warning('Error saving signatures: ' . $e->getMessage());
            }
            
            // Save employee_id if provided
            if ($request->employee_id) {
                $validated['employee_id'] = $request->employee_id;
            }
            
            Log::info('Creating issue note with data:', array_merge($validated, ['items' => $validated['items'] ?? []]));
            
            $issueNote = IssueNote::create($validated);
            
            Log::info('Issue note created successfully. ID: ' . $issueNote->id);
            
            // Verify the note was actually saved
            $savedNote = IssueNote::find($issueNote->id);
            if (!$savedNote) {
                Log::error('Issue note was not saved to database!');
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Failed to save issue note. Please try again.']);
            }

            return redirect()->route('issue-note.create')
                ->with('success', 'Issue note saved successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('IssueNote store database error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Database error occurred. Please ensure migrations are run: php artisan migrate --force']);
        } catch (\Exception $e) {
            Log::error('IssueNote store error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while saving the issue note. Please try again.']);
        }
    }

    private function saveSignature($signature)
    {
        if (!$signature) return null;

        $folderPath = storage_path('app/public/signatures/');

        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0777, true);
        }

        $image_parts = explode(";base64,", $signature);
        $image_base64 = base64_decode($image_parts[1]);

        $fileName = 'signature_' . uniqid() . '.png';
        $filePath = $folderPath . $fileName;

        file_put_contents($filePath, $image_base64);

        return 'signatures/' . $fileName;
    }

    public function createReturn()
    {
        $employees = Employee::all();
        // Get all issue notes that don't have a return note yet
        $issueNotes = IssueNote::where('note_type', 'issue')
            ->whereDoesntHave('returnNotes')
            ->with('employee')
            ->get();
        return view('issue-note.create-return', compact('employees', 'issueNotes'));
    }

    public function storeReturn(Request $request)
    {
        try {
            if (!Schema::hasTable('issue_notes')) {
                Log::error('issue_notes table does not exist');
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Database table not found. Please run migrations: php artisan migrate --force']);
            }

            $validated = $request->validate([
                'issue_note_id' => 'required|exists:issue_notes,id',
                'return_date' => 'required|date',
                'user_signature' => 'nullable|string',
                'manager_signature' => 'nullable|string',
            ]);

            // Get the original issue note
            $issueNote = IssueNote::findOrFail($validated['issue_note_id']);

            // Create return note with same data as issue note
            $returnData = [
                'employee_id' => $issueNote->employee_id,
                'department' => $issueNote->department,
                'entity' => $issueNote->entity,
                'location' => $issueNote->location,
                'system_code' => $issueNote->system_code,
                'printer_code' => $issueNote->printer_code,
                'software_installed' => $issueNote->software_installed,
                'issued_date' => $issueNote->issued_date,
                'return_date' => $validated['return_date'],
                'items' => $issueNote->items,
                'note_type' => 'return',
                'issue_note_id' => $issueNote->id,
            ];

            // SAVE SIGNATURE FUNCTION
            try {
                $returnData['user_signature'] = $this->saveSignature($request->user_signature);
                $returnData['manager_signature'] = $this->saveSignature($request->manager_signature);
            } catch (\Exception $e) {
                Log::warning('Error saving signatures: ' . $e->getMessage());
            }

            Log::info('Creating return note with data:', $returnData);
            
            $returnNote = IssueNote::create($returnData);
            
            Log::info('Return note created successfully. ID: ' . $returnNote->id);
            
            // Verify the note was actually saved
            $savedNote = IssueNote::find($returnNote->id);
            if (!$savedNote) {
                Log::error('Return note was not saved to database!');
                return redirect()
                    ->back()
                    ->withInput()
                    ->withErrors(['error' => 'Failed to save return note. Please try again.']);
            }

            return redirect()->route('issue-note.create-return')
                ->with('success', 'Return note saved successfully!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('IssueNote storeReturn database error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Database error occurred. Please ensure migrations are run: php artisan migrate --force']);
        } catch (\Exception $e) {
            Log::error('IssueNote storeReturn error: ' . $e->getMessage());
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while saving the return note. Please try again.']);
        }
    }

    public function getIssueNoteDetails($id)
    {
        $issueNote = IssueNote::with('employee')->findOrFail($id);

        return response()->json([
            'employee_id' => $issueNote->employee_id,
            'employee_name' => $issueNote->employee->name ?? $issueNote->employee->entity_name ?? 'N/A',
            'department' => $issueNote->department ?? 'N/A',
            'entity' => $issueNote->entity ?? 'N/A',
            'location' => $issueNote->location ?? 'N/A',
            'system_code' => $issueNote->system_code ?? '',
            'printer_code' => $issueNote->printer_code ?? '',
            'software_installed' => $issueNote->software_installed ?? '',
            'issued_date' => $issueNote->issued_date ? $issueNote->issued_date->format('Y-m-d') : '',
            'items' => $issueNote->items ?? [],
        ]);
    }

    public function getEmployeeDetails($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json(['error' => 'Employee not found'], 404);
        }

        // Get location from employee's latest asset transaction
        $latestTransaction = \App\Models\AssetTransaction::where('employee_id', $id)
            ->where('transaction_type', 'assign')
            ->whereNotNull('location_id')
            ->with('location')
            ->latest('issue_date')
            ->first();
        
        $location = $latestTransaction && $latestTransaction->location 
            ? $latestTransaction->location->location_name 
            : 'N/A';

        return response()->json([
            'name' => $employee->name ?? $employee->entity_name,
            'department' => $employee->department_name ?? 'N/A',
            'department_name' => $employee->department_name ?? 'N/A',
            'entity_name' => $employee->entity_name ?? 'N/A',
            'location' => $location,
        ]);
    }

    public function index(Request $request)
    {
        try {
            if (!Schema::hasTable('issue_notes')) {
                Log::warning('issue_notes table does not exist');
                $issueNotes = collect([]);
                return view('issue-note.index', compact('issueNotes'))
                    ->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
            }

            $query = IssueNote::with('employee');

            // Filter by note type
            if ($request->filled('note_type')) {
                $query->where('note_type', $request->note_type);
            }

            // Search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('department', 'like', "%{$search}%")
                      ->orWhere('entity', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%")
                      ->orWhere('system_code', 'like', "%{$search}%")
                      ->orWhere('printer_code', 'like', "%{$search}%")
                      ->orWhereHas('employee', function($empQuery) use ($search) {
                          $empQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('entity_name', 'like', "%{$search}%");
                      });
                });
            }

            $issueNotes = $query->orderBy('created_at', 'desc')->get();
            
            return view('issue-note.index', compact('issueNotes'));
        } catch (\Exception $e) {
            Log::error('IssueNote index error: ' . $e->getMessage());
            $issueNotes = collect([]);
            return view('issue-note.index', compact('issueNotes'))
                ->with('warning', 'Unable to load issue notes. Please ensure migrations are run: php artisan migrate --force');
        }
    }

    public function export(Request $request)
    {
        $query = IssueNote::with('employee');

        // Filter by note type
        if ($request->filled('note_type')) {
            $query->where('note_type', $request->note_type);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('department', 'like', "%{$search}%")
                  ->orWhere('entity', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('system_code', 'like', "%{$search}%")
                  ->orWhere('printer_code', 'like', "%{$search}%")
                  ->orWhereHas('employee', function($empQuery) use ($search) {
                      $empQuery->where('name', 'like', "%{$search}%")
                               ->orWhere('entity_name', 'like', "%{$search}%");
                  });
            });
        }

        $issueNotes = $query->orderBy('created_at', 'desc')->get();
        $format = $request->get('format', 'pdf');
        $noteType = $request->get('note_type', 'all');

        if ($format === 'excel' || $format === 'csv') {
            return $this->exportExcel($issueNotes, $noteType);
        } else {
            return $this->exportPdf($issueNotes, $noteType);
        }
    }

    private function exportPdf($issueNotes, $noteType)
    {
        $pdf = \PDF::loadView('issue-note.export-pdf', compact('issueNotes', 'noteType'));
        return $pdf->download('issue-notes-' . ($noteType !== 'all' ? $noteType : 'all') . '-' . date('Y-m-d') . '.pdf');
    }

    private function exportExcel($issueNotes, $noteType)
    {
        $filename = 'issue-notes-' . ($noteType !== 'all' ? $noteType : 'all') . '-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($issueNotes) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                '#', 'Type', 'Employee', 'Department', 'Entity', 'Location', 
                'System Code', 'Printer Code', 'Issued Date', 'Return Date', 
                'Items', 'Software Installed'
            ]);

            // Data
            foreach ($issueNotes as $index => $note) {
                $items = is_array($note->items) ? implode(', ', $note->items) : ($note->items ?? 'N/A');
                
                fputcsv($file, [
                    $index + 1,
                    ucfirst($note->note_type ?? 'N/A'),
                    $note->employee->name ?? $note->employee->entity_name ?? 'N/A',
                    $note->department ?? 'N/A',
                    $note->entity ?? 'N/A',
                    $note->location ?? 'N/A',
                    $note->system_code ?? 'N/A',
                    $note->printer_code ?? 'N/A',
                    $note->issued_date ? $note->issued_date->format('Y-m-d') : 'N/A',
                    $note->return_date ? $note->return_date->format('Y-m-d') : 'N/A',
                    $items,
                    $note->software_installed ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
