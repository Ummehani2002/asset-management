<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TimeManagement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TimeManagementController extends Controller
{
  public function index(Request $request)
    {
        try {
            if (! Schema::hasTable('time_managements')) {
                return view('time_management.index', [
                    'tasks' => collect(),
                    'isAdmin' => Auth::user()?->isAdmin() ?? false,
                    'teamMembers' => collect(),
                ])->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
            }

            $user = Auth::user();
            $isAdmin = $user->isAdmin();

            $query = TimeManagement::query()->orderByDesc('job_card_date')->orderByDesc('start_time');

            if ($isAdmin) {
                if ($request->filled('user_id')) {
                    $query->where('user_id', $request->user_id);
                }
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                    if ($user->employee_id) {
                        $q->orWhere('employee_id', $user->employee_id);
                    }
                });
            }

            if ($request->filled('status') && in_array($request->status, ['pending', 'completed'], true)) {
                $query->where('status', $request->status);
            }

            if ($request->filled('from_date')) {
                $query->whereDate('job_card_date', '>=', $request->from_date);
            }

            if ($request->filled('to_date')) {
                $query->whereDate('job_card_date', '<=', $request->to_date);
            }

            $tasks = $query->get();

            foreach ($tasks->unique(fn ($task) => ($task->user_id ?? 0) . '|' . $task->job_card_date?->format('Y-m-d')) as $task) {
                if ($task->job_card_date) {
                    TimeManagement::recalculateDailyOvertime(
                        $task->employee_id,
                        $task->user_id,
                        $task->job_card_date->format('Y-m-d')
                    );
                }
            }

            if ($tasks->isNotEmpty()) {
                $tasks = TimeManagement::whereIn('id', $tasks->pluck('id'))
                    ->orderByDesc('job_card_date')
                    ->orderByDesc('start_time')
                    ->get();
            }

            $teamMembers = $isAdmin
                ? User::orderBy('name')->get(['id', 'name'])
                : collect();

            return view('time_management.index', compact('tasks', 'isAdmin', 'teamMembers'));
        } catch (\Exception $e) {
            Log::error('TimeManagement index error: ' . $e->getMessage());

            return view('time_management.index', [
                'tasks' => collect(),
                'isAdmin' => Auth::user()?->isAdmin() ?? false,
                'teamMembers' => collect(),
            ])->with('warning', 'Unable to load work logs.');
        }
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        $query = TimeManagement::query()->orderByDesc('job_card_date')->orderByDesc('start_time');

        if (! $user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->employee_id) {
                    $q->orWhere('employee_id', $user->employee_id);
                }
            });
        } elseif ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status') && in_array($request->status, ['pending', 'completed'], true)) {
            $query->where('status', $request->status);
        }

        $tasks = $query->get();
        $status = $request->get('status', 'all');
        $format = $request->get('format', 'pdf');

        if ($format === 'excel' || $format === 'csv') {
            return $this->exportExcel($tasks, $status);
        }

        return $this->exportPdf($tasks, $status);
    }

    private function exportPdf($tasks, $status)
    {
        $pdf = \PDF::loadView('time_management.export-pdf', compact('tasks', 'status'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('time-management-' . ($status ?? 'all') . '-' . date('Y-m-d') . '.pdf');
    }

    private function exportExcel($tasks, $status)
    {
        $filename = 'time-management-' . ($status ?? 'all') . '-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($tasks) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                '#', 'Ticket', 'Employee', 'Category', 'Task Description', 'Site/Location',
                'Work Date', 'Start Time', 'End Time', 'Time Spent (hrs)', 'Overtime (hrs)',
                'Action/Resolution', 'Status', 'Remarks',
            ]);

            foreach ($tasks as $index => $task) {
                fputcsv($file, [
                    $index + 1,
                    $task->ticket_number ?? 'N/A',
                    $task->employee_name ?? 'N/A',
                    $task->category ?? 'N/A',
                    $task->task_description ?? 'N/A',
                    $task->site_location ?? 'N/A',
                    $task->job_card_date ? $task->job_card_date->format('Y-m-d') : 'N/A',
                    $task->start_time ? $task->start_time->format('Y-m-d H:i') : 'N/A',
                    $task->end_time ? $task->end_time->format('Y-m-d H:i') : 'N/A',
                    $task->duration_hours ?? '0',
                    $task->overtime_hours ?? '0',
                    $task->action_taken ?? 'N/A',
                    ucfirst($task->status ?? 'N/A'),
                    $task->remarks ?? 'N/A',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $user = Auth::user();
        $ticketNumber = TimeManagement::generateTicketNumber();

        return view('time_management.create', [
            'ticketNumber' => $ticketNumber,
            'employeeName' => $user->name,
            'defaultCategory' => TimeManagement::DEFAULT_CATEGORY,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'ticket_number' => 'required|string|max:50',
            'category' => 'required|string|max:100',
            'task_description' => 'required|string|max:50',
            'site_location' => 'required|string|max:255',
            'job_card_date' => 'required|date',
            'start_time_hour' => 'required|date_format:H:i',
            'end_time_hour' => 'required|date_format:H:i',
            'action_taken' => 'nullable|string',
            'status' => 'required|in:pending,completed',
            'remarks' => 'nullable|string',
        ]);

        $employee = $this->resolveEmployeeForUser($user);
        $start = Carbon::parse($validated['job_card_date'] . ' ' . $validated['start_time_hour']);
        $end = Carbon::parse($validated['job_card_date'] . ' ' . $validated['end_time_hour']);

        if ($end->lessThanOrEqualTo($start)) {
            return back()->withInput()->withErrors(['end_time_hour' => 'End time must be after start time.']);
        }
        $duration = TimeManagement::calculateDurationHours($start, $end);

        $record = TimeManagement::create([
            'ticket_number' => $validated['ticket_number'],
            'category' => $validated['category'],
            'task_description' => $validated['task_description'],
            'site_location' => $validated['site_location'],
            'user_id' => $user->id,
            'employee_id' => $employee?->id,
            'employee_name' => $user->name,
            'job_card_date' => $validated['job_card_date'],
            'standard_man_hours' => TimeManagement::DAILY_STANDARD_HOURS,
            'start_time' => $start,
            'end_time' => $end,
            'duration_hours' => $duration,
            'action_taken' => $validated['action_taken'] ?? null,
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        TimeManagement::recalculateDailyOvertime(
            $employee?->id,
            $user->id,
            $validated['job_card_date']
        );

        return $this->workLogRedirect($request)->with('success', 'Work log saved successfully.');
    }

    public function edit($id)
    {
        $record = TimeManagement::findOrFail($id);
        $this->authorizeRecord($record);

        return view('time_management.edit', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = TimeManagement::findOrFail($id);
        $this->authorizeRecord($record);

        $validated = $request->validate([
            'category' => 'required|string|max:100',
            'task_description' => 'required|string|max:50',
            'site_location' => 'required|string|max:255',
            'job_card_date' => 'required|date',
            'start_time_hour' => 'required|date_format:H:i',
            'end_time_hour' => 'required|date_format:H:i',
            'action_taken' => 'nullable|string',
            'status' => 'required|in:pending,completed',
            'remarks' => 'nullable|string',
        ]);

        $start = Carbon::parse($validated['job_card_date'] . ' ' . $validated['start_time_hour']);
        $end = Carbon::parse($validated['job_card_date'] . ' ' . $validated['end_time_hour']);

        if ($end->lessThanOrEqualTo($start)) {
            return back()->withInput()->withErrors(['end_time_hour' => 'End time must be after start time.']);
        }
        $duration = TimeManagement::calculateDurationHours($start, $end);
        $oldEmployeeId = $record->employee_id;
        $oldDate = $record->job_card_date?->format('Y-m-d');

        $record->fill([
            'category' => $validated['category'],
            'task_description' => $validated['task_description'],
            'site_location' => $validated['site_location'],
            'job_card_date' => $validated['job_card_date'],
            'start_time' => $start,
            'end_time' => $end,
            'duration_hours' => $duration,
            'action_taken' => $validated['action_taken'] ?? null,
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
            'standard_man_hours' => TimeManagement::DAILY_STANDARD_HOURS,
        ]);
        $record->save();

        TimeManagement::recalculateDailyOvertime(
            $record->employee_id,
            $record->user_id,
            $validated['job_card_date']
        );

        if ($oldDate && $oldDate !== $validated['job_card_date']) {
            TimeManagement::recalculateDailyOvertime($oldEmployeeId, $record->user_id, $oldDate);
        }

        return $this->workLogRedirect($request)->with('success', 'Work log updated successfully.');
    }

    public function destroy($id)
    {
        $record = TimeManagement::findOrFail($id);
        $this->authorizeRecord($record);

        $employeeId = $record->employee_id;
        $userId = $record->user_id;
        $date = $record->job_card_date?->format('Y-m-d');

        $record->delete();

        if ($date) {
            TimeManagement::recalculateDailyOvertime($employeeId, $userId, $date);
        }

        $redirect = request()->input('_from_app')
            ? redirect()->route('worklog.index')
            : redirect()->route('time.index');

        return $redirect->with('success', 'Work log deleted successfully.');
    }

    private function resolveEmployeeForUser(User $user): ?Employee
    {
        if ($user->employee_id) {
            return Employee::find($user->employee_id);
        }

        return Employee::whereRaw('LOWER(email) = ?', [strtolower($user->email)])->first();
    }

    private function authorizeRecord(TimeManagement $record): void
    {
        $user = Auth::user();

        if (! $record->isOwnedBy($user)) {
            abort(403, 'You are not allowed to modify this work log.');
        }
    }

    private function workLogRedirect(Request $request)
    {
        return $request->input('_from_app')
            ? redirect()->route('worklog.index')
            : redirect()->route('time.index');
    }
}
