<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\TimeManagement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\JobDelayAlertMail;
use App\Mail\TaskAssignedMail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;


class TimeManagementController extends Controller
{
    public function index()
    {
        try {
            if (!Schema::hasTable('time_managements')) {
                Log::warning('time_managements table does not exist');
                $inProgressTasks = collect([]);
                $completedTasks = collect([]);
                return view('time_management.index', compact('inProgressTasks', 'completedTasks'))
                    ->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
            }

            $inProgressTasks = TimeManagement::where('status', 'in_progress')
                ->orderBy('id', 'desc')
                ->get();
            
            $completedTasks = TimeManagement::where('status', 'completed')
                ->orderBy('id', 'desc')
                ->get();
            
            return view('time_management.index', compact('inProgressTasks', 'completedTasks'));
        } catch (\Exception $e) {
            Log::error('TimeManagement index error: ' . $e->getMessage());
            $inProgressTasks = collect([]);
            $completedTasks = collect([]);
            return view('time_management.index', compact('inProgressTasks', 'completedTasks'))
                ->with('warning', 'Unable to load tasks. Please ensure migrations are run: php artisan migrate --force');
        }
    }

    public function export(Request $request)
    {
        $status = $request->get('status'); // 'completed' or 'in_progress'
        
        if ($status === 'completed') {
            $tasks = TimeManagement::where('status', 'completed')->orderBy('id', 'desc')->get();
        } elseif ($status === 'in_progress') {
            $tasks = TimeManagement::where('status', 'in_progress')->orderBy('id', 'desc')->get();
        } else {
            $tasks = TimeManagement::orderBy('id', 'desc')->get();
        }

        $format = $request->get('format', 'pdf');

        if ($format === 'excel' || $format === 'csv') {
            return $this->exportExcel($tasks, $status);
        } else {
            return $this->exportPdf($tasks, $status);
        }
    }

    private function exportPdf($tasks, $status)
    {
        $pdf = \PDF::loadView('time_management.export-pdf', compact('tasks', 'status'));
        return $pdf->download('time-management-' . ($status ?? 'all') . '-' . date('Y-m-d') . '.pdf');
    }

    private function exportExcel($tasks, $status)
    {
        $filename = 'time-management-' . ($status ?? 'all') . '-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($tasks) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                '#', 'Ticket', 'Employee', 'Project', 'Job Date', 'Start Time', 
                'End Time', 'Duration (hrs)', 'Status', 'Performance (%)', 'Delayed (Days)', 'Delay Reason'
            ]);

            // Data
            foreach ($tasks as $index => $task) {
                fputcsv($file, [
                    $index + 1,
                    $task->ticket_number ?? 'N/A',
                    $task->employee_name ?? 'N/A',
                    $task->project_name ?? 'N/A',
                    $task->job_card_date ? $task->job_card_date->format('Y-m-d') : 'N/A',
                    $task->start_time ? $task->start_time->setTimezone('Asia/Dubai')->format('Y-m-d H:i') : 'N/A',
                    $task->end_time ? $task->end_time->setTimezone('Asia/Dubai')->format('Y-m-d H:i') : 'N/A',
                    $task->duration_hours ?? 'N/A',
                    ucfirst($task->status ?? 'N/A'),
                    $task->performance_percent ?? 'N/A',
                    $task->delayed_days ?? 'N/A',
                    $task->delay_reason ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

public function create()
{
    $employees = \App\Models\Employee::all();
    $projects = \App\Models\Project::all();
    return view('time_management.create', compact('employees', 'projects'));
}

public function store(Request $request)
{
    try {
        if (!Schema::hasTable('time_managements')) {
            Log::error('time_managements table does not exist');
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Database table not found. Please run migrations: php artisan migrate --force']);
        }

        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'project_id' => 'nullable|exists:projects,id',
            'project_name' => 'required|string',
            'job_card_date' => 'required|date',
            'standard_man_hours' => 'required|numeric|min:0',
        ]);

        $employee = \App\Models\Employee::find($request->employee_id);
        if (!$employee) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Employee not found.']);
        }
        
        // If project_id is provided, get project name from database
        $projectName = $request->project_name;
        if ($request->project_id) {
            try {
                $project = \App\Models\Project::find($request->project_id);
                if ($project) {
                    $projectName = $project->project_name;
                }
            } catch (\Exception $e) {
                Log::warning('Error finding project: ' . $e->getMessage());
            }
        }

        // ✅ Fetch the latest ticket_number instead of using count
        $lastRecord = \App\Models\TimeManagement::orderBy('id', 'desc')->first();
        $lastNumber = $lastRecord ? intval(substr($lastRecord->ticket_number, 4)) : 0;
        $newNumber = $lastNumber + 1;

        $ticketNumber = 'TCKT' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        Log::info('Creating time management record with ticket: ' . $ticketNumber);

        $timeRecord = \App\Models\TimeManagement::create([
            'ticket_number' => $ticketNumber,
            'employee_id' => $employee->id,
            'employee_name' => $employee->name,
            'project_name' => $projectName,
            'job_card_date' => $request->job_card_date,
            'standard_man_hours' => $request->standard_man_hours,
            'start_time' => \Carbon\Carbon::now('Asia/Dubai'),
            'status' => 'in_progress',
        ]);

        Log::info('Time management record created successfully. ID: ' . $timeRecord->id);

        // Verify the record was actually saved
        $savedRecord = \App\Models\TimeManagement::find($timeRecord->id);
        if (!$savedRecord) {
            Log::error('Time management record was not saved to database!');
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to save job card. Please try again.']);
        }

        // Send email to employee when task is assigned
        if ($employee->email) {
            try {
                Mail::to($employee->email)->send(new TaskAssignedMail($timeRecord));
                \Log::info('Task assignment email sent to: ' . $employee->email);
            } catch (\Exception $e) {
                \Log::error('Failed to send task assignment email: ' . $e->getMessage());
                // Don't fail the save if email fails
            }
        }

        return redirect()->route('time.index')->with('success', 'Job Card Created Successfully!');
    } catch (\Illuminate\Validation\ValidationException $e) {
        throw $e;
    } catch (\Illuminate\Database\QueryException $e) {
        Log::error('TimeManagement store database error: ' . $e->getMessage());
        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['error' => 'Database error occurred. Please ensure migrations are run: php artisan migrate --force']);
    } catch (\Exception $e) {
        Log::error('TimeManagement store error: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['error' => 'An error occurred while saving the job card. Please try again.']);
    }
}




    public function edit($id)
    {
        $record = TimeManagement::findOrFail($id);
        return view('time_management.edit', compact('record'));
    }

    public function update(Request $request, $id)
{
    $record = TimeManagement::findOrFail($id);

    // Auto set end_time = now (no manual input) - using Dubai timezone
    $endTime = Carbon::now('Asia/Dubai');
    $start = Carbon::parse($record->start_time)->setTimezone('Asia/Dubai');

    $record->end_time = $endTime;
    $record->status = 'completed';

    // Calculate duration safely
    $actualHours = max(0.01, $endTime->diffInMinutes($start) / 60); // avoid division by zero
    $record->duration_hours = round($actualHours, 2);

    // Calculate performance based on standard vs actual hours
    // Performance = (Standard Hours / Actual Hours) * 100
    // If completed faster than standard: performance > 100% (capped at 200% for very fast completion)
    // If completed slower than standard: performance < 100%
    if ($record->standard_man_hours > 0) {
        $performance = ($record->standard_man_hours / $actualHours) * 100;
        // Cap performance between 0% and 200% (allow for exceptional performance)
        $performance = max(0, min(200, round($performance, 2)));
    } else {
        $performance = 0;
    }

    // Store delay reason if provided
    $record->performance_percent = $performance;
    $record->delay_reason = $request->delay_reason ?? null;
    
    // Note: We're tracking performance based on hours, not days
    // delayed_days is kept for backward compatibility but not actively used
    $expectedEndDate = Carbon::parse($record->job_card_date)->addDay();
    $record->delayed_days = max(0, $endTime->diffInDays($expectedEndDate, false));

    $record->save();
    
    // Don't send delay email when completing - delay emails are only sent while task is in progress
    // The scheduled command handles sending delay alerts for in-progress tasks

    return redirect()->route('time.index')->with('success', 'Job Card Completed Successfully!');
}


    public function destroy($id)
    {
        $record = TimeManagement::findOrFail($id);
        $record->delete();
        return redirect()->route('time.index')->with('success', 'Job Card Deleted Successfully!');
    }
}
