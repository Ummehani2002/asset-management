<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TimeManagement;
use App\Models\User;
use App\Models\WorkTicket;
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
                    'isAdmin' => Auth::user()?->isTimeManagementAdmin() ?? false,
                    'teamMembers' => collect(),
                    'dailySummaries' => [],
                    'summaryDate' => today()->format('Y-m-d'),
                    'dailySummaryTotals' => ['total_hours' => 0, 'overtime_hours' => 0, 'employee_count' => 0, 'active_count' => 0],
                ])->with('warning', 'Database tables not found. Please run migrations: php artisan migrate --force');
            }

            $user = Auth::user();
            $isAdmin = $user->isTimeManagementAdmin();

            $query = TimeManagement::query()
                ->with('workTicket')
                ->orderByDesc('job_card_date')
                ->orderByDesc('start_time');

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
                $query->where(function ($q) use ($request) {
                    $q->where('status', $request->status)
                        ->orWhereHas('workTicket', fn ($ticketQuery) => $ticketQuery->where('status', $request->status));
                });
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
                $tasks = TimeManagement::with('workTicket')
                    ->whereIn('id', $tasks->pluck('id'))
                    ->orderByDesc('job_card_date')
                    ->orderByDesc('start_time')
                    ->get();
            }

            $teamMembers = $isAdmin
                ? User::orderBy('name')->get(['id', 'name'])
                : collect();

            $summaryDate = $request->input('summary_date', today()->format('Y-m-d'));
            $dailySummaries = $isAdmin
                ? TimeManagement::getAdminDailySummaries(
                    $summaryDate,
                    $request->filled('user_id') ? (int) $request->user_id : null,
                    $teamMembers
                )
                : [];
            $dailySummaryTotals = $isAdmin
                ? TimeManagement::summarizeDailyTotals($dailySummaries)
                : ['total_hours' => 0, 'overtime_hours' => 0, 'employee_count' => 0, 'active_count' => 0];

            return view('time_management.index', compact('tasks', 'isAdmin', 'teamMembers', 'dailySummaries', 'summaryDate', 'dailySummaryTotals'));
        } catch (\Exception $e) {
            Log::error('TimeManagement index error: ' . $e->getMessage());

            return view('time_management.index', [
                'tasks' => collect(),
                'isAdmin' => Auth::user()?->isTimeManagementAdmin() ?? false,
                'teamMembers' => collect(),
                'dailySummaries' => [],
                'summaryDate' => today()->format('Y-m-d'),
                'dailySummaryTotals' => ['total_hours' => 0, 'overtime_hours' => 0, 'employee_count' => 0, 'active_count' => 0],
            ])->with('warning', 'Unable to load work logs.');
        }
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        $query = TimeManagement::query()->orderByDesc('job_card_date')->orderByDesc('start_time');

        if (! $user->isTimeManagementAdmin()) {
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

    public function create(Request $request)
    {
        $user = Auth::user();
        $todayTotals = TimeManagement::getDailyTotals($user->id, $user->employee_id, date('Y-m-d'));
        $isAdmin = $user->isTimeManagementAdmin();
        $runningLog = TimeManagement::findRunningForUser($user);
        $openTickets = $isAdmin ? collect() : WorkTicket::openTicketsForUser($user);
        $continueTicket = null;

        if (! $isAdmin && $request->filled('work_ticket_id')) {
            $continueTicket = $openTickets->firstWhere('id', (int) $request->work_ticket_id);
        }

        return view('time_management.create', [
            'employeeName' => $user->name,
            'defaultCategory' => TimeManagement::DEFAULT_CATEGORY,
            'todayTotals' => $todayTotals,
            'isAdmin' => $isAdmin,
            'runningLog' => $runningLog,
            'openTickets' => $openTickets,
            'continueTicket' => $continueTicket,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $logType = $request->input('log_type', 'new');

        $rules = [
            'log_type' => 'required|in:new,continue',
            'work_ticket_id' => 'nullable|integer',
            'action_taken' => 'nullable|string',
            'remarks' => 'nullable|string',
        ];

        if ($logType === 'continue') {
            $rules['work_ticket_id'] = 'required|integer|exists:work_tickets,id';
        } else {
            $rules = array_merge($rules, [
                'ticket_number' => 'required|string|max:50',
                'category' => 'required|in:End User Support,Infrastructure,Network,Hardware,Software,Other',
                'task_description' => 'required|string|max:50',
                'site_location' => 'required|string|max:255',
            ]);
        }

        $validated = $request->validate($rules);

        $employee = $this->resolveEmployeeForUser($user);
        $now = now();
        $workDate = $now->toDateString();
        $workTicket = null;

        if ($logType === 'continue') {
            $workTicket = WorkTicket::findOrFail($validated['work_ticket_id']);

            if (! $workTicket->belongsToUser($user)) {
                abort(403, 'You are not allowed to add a visit to this ticket.');
            }

            if (! $workTicket->isOpen()) {
                return back()->withInput()->withErrors([
                    'work_ticket_id' => 'This ticket is completed and cannot accept another visit.',
                ]);
            }

            $ticketNumber = $workTicket->ticket_number;
            $category = $workTicket->category;
            $taskDescription = $workTicket->task_description;
            $siteLocation = $workTicket->site_location;
        } else {
            $ticketNumber = trim($validated['ticket_number']);
            $category = $validated['category'];
            $taskDescription = $validated['task_description'];
            $siteLocation = $validated['site_location'];

            if ($existingTicket = WorkTicket::findOpenForUser($user, $ticketNumber)) {
                return back()->withInput()->withErrors([
                    'ticket_number' => 'This ticket is already open. Select Continue Ticket to add another visit.',
                ]);
            }

            if (Schema::hasTable('work_tickets')) {
            $workTicket = WorkTicket::create([
                'ticket_number' => $ticketNumber,
                'user_id' => $user->id,
                'employee_id' => $employee?->id ?? $user->employee_id,
                'employee_name' => $user->name,
                'category' => $category,
                    'task_description' => $taskDescription,
                    'site_location' => $siteLocation,
                'status' => 'pending',
                'completed_at' => null,
            ]);
            }
        }

        $payload = [
            'ticket_number' => $ticketNumber,
            'category' => $category,
            'task_description' => $taskDescription,
            'site_location' => $siteLocation,
            'user_id' => $user->id,
            'employee_id' => $employee?->id ?? $user->employee_id,
            'employee_name' => $user->name,
            'job_card_date' => $workDate,
            'standard_man_hours' => TimeManagement::DAILY_STANDARD_HOURS,
            'start_time' => $now,
            'end_time' => null,
            'duration_hours' => 0,
            'overtime_hours' => 0,
            'action_taken' => $validated['action_taken'] ?? null,
            'status' => 'pending',
            'remarks' => $validated['remarks'] ?? null,
        ];

        if ($workTicket && Schema::hasColumn('time_managements', 'work_ticket_id')) {
            $payload['work_ticket_id'] = $workTicket->id;
        }

        TimeManagement::create($payload);

        return $this->workLogRedirect($request, true)
            ->with('success', ($logType === 'continue' ? 'New visit started on ticket ' : 'Work started on ticket ').$ticketNumber.'.');
    }

    public function stop(Request $request, $id)
    {
        $record = TimeManagement::with('workTicket')->findOrFail($id);
        $this->authorizeRecord($record);

        if (! $record->isRunning()) {
            return $this->workLogRedirect($request, true)
                ->with('warning', 'This work log is already stopped.');
        }

        $end = now();
        $start = Carbon::parse($record->start_time);

        if ($end->lessThanOrEqualTo($start)) {
            $end = $start->copy()->addMinute();
        }

        $duration = TimeManagement::calculateDurationHours($start, $end);
        $workDate = optional($record->job_card_date)->format('Y-m-d')
            ?? $start->toDateString();

        $record->fill([
            'end_time' => $end,
            'duration_hours' => $duration,
            'status' => 'completed',
            'job_card_date' => $workDate,
            'standard_man_hours' => TimeManagement::DAILY_STANDARD_HOURS,
        ]);
        $record->save();

        $completeTicket = $request->boolean('complete_ticket');
        if ($record->workTicket && $completeTicket) {
            $record->workTicket->markCompleted();
        }

        TimeManagement::recalculateDailyOvertime(
            $record->employee_id,
            $record->user_id,
            $workDate
        );

        $message = $completeTicket
            ? 'Visit stopped and ticket completed.'
            : 'Visit stopped. Ticket remains open for another visit.';

        return $this->workLogRedirect($request, true)
            ->with('success', $message.' Time worked: '.TimeManagement::formatDuration($duration).'.');
    }

    public function edit($id)
    {
        $record = TimeManagement::with('workTicket')->findOrFail($id);
        $this->authorizeRecord($record);

        $user = Auth::user();
        $date = optional($record->job_card_date)->format('Y-m-d') ?? date('Y-m-d');
        $todayTotals = TimeManagement::getDailyTotals($user->id, $user->employee_id, $date, $record->id);

        return view('time_management.edit', [
            'record' => $record,
            'todayTotals' => $todayTotals,
            'isAdmin' => $user->isTimeManagementAdmin(),
            'openTickets' => $user->isTimeManagementAdmin() ? collect() : WorkTicket::openTicketsForUser($user),
        ]);
    }

    public function showTicket($id)
    {
        $ticket = WorkTicket::with(['visits' => function ($q) {
            $q->orderByDesc('job_card_date')->orderByDesc('start_time');
        }])->findOrFail($id);

        if (! $ticket->isOwnedBy(Auth::user())) {
            abort(403);
        }

        return view('time_management.ticket', [
            'ticket' => $ticket,
            'isAdmin' => Auth::user()->isTimeManagementAdmin(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $record = TimeManagement::findOrFail($id);
        $this->authorizeRecord($record);

        $rules = [
            'job_card_date' => 'required|date',
            'start_time_hour' => 'required|date_format:H:i',
            'end_time_hour' => 'nullable|date_format:H:i',
            'action_taken' => 'nullable|string',
            'status' => 'required|in:pending,completed',
            'remarks' => 'nullable|string',
        ];

        if (! $record->work_ticket_id) {
            $rules = array_merge($rules, [
                'ticket_number' => 'nullable|string|max:50',
                'category' => 'nullable|string|max:100',
                'task_description' => 'required|string|max:50',
                'site_location' => 'required|string|max:255',
            ]);
        }

        $validated = $request->validate($rules);

        $start = Carbon::parse($validated['job_card_date'] . ' ' . $validated['start_time_hour']);
        $end = null;
        $duration = 0.0;

        if (! empty($validated['end_time_hour'])) {
            $end = Carbon::parse($validated['job_card_date'] . ' ' . $validated['end_time_hour']);
            if ($end->lessThanOrEqualTo($start)) {
                return back()->withInput()->withErrors(['end_time_hour' => 'End time must be after start time.']);
            }
            $duration = TimeManagement::calculateDurationHours($start, $end);
        } elseif ($validated['status'] === 'completed') {
            return back()->withInput()->withErrors(['end_time_hour' => 'End time is required to mark completed, or use the Stop button.']);
        }

        $oldEmployeeId = $record->employee_id;
        $oldDate = $record->job_card_date?->format('Y-m-d');

        $visitStatus = ($end === null) ? 'pending' : $validated['status'];
        $record->fill([
            'job_card_date' => $validated['job_card_date'],
            'start_time' => $start,
            'end_time' => $end,
            'duration_hours' => $duration,
            'action_taken' => $validated['action_taken'] ?? null,
            'status' => $visitStatus,
            'remarks' => $validated['remarks'] ?? null,
            'standard_man_hours' => TimeManagement::DAILY_STANDARD_HOURS,
        ]);

        if (! $record->work_ticket_id) {
            $record->fill([
                'ticket_number' => $validated['ticket_number'] ?? $record->ticket_number,
                'category' => $validated['category'] ?? $record->category,
                'task_description' => $validated['task_description'],
                'site_location' => $validated['site_location'],
            ]);
        }

        $record->save();

        if ($record->workTicket) {
            if ($visitStatus === 'completed') {
                $record->workTicket->markCompleted();
            } elseif ($record->workTicket->status === 'completed') {
                $record->workTicket->update([
                    'status' => 'pending',
                    'completed_at' => null,
                ]);
            }
        }

        if ($end !== null) {
            TimeManagement::recalculateDailyOvertime(
                $record->employee_id,
                $record->user_id,
                $validated['job_card_date']
            );
        }

        if ($oldDate && $oldDate !== $validated['job_card_date']) {
            TimeManagement::recalculateDailyOvertime($oldEmployeeId, $record->user_id, $oldDate);
        }

        return $this->workLogRedirect($request, true)->with('success', 'Work log updated successfully.');
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

        if (! Schema::hasTable('employees') || empty($user->email)) {
            return null;
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

    private function workLogRedirect(Request $request, bool $toJobsList = false)
    {
        if ($request->input('_from_app')) {
            return redirect()->route($toJobsList ? 'worklog.index' : 'worklog.create');
        }

        return redirect()->route('time.index');
    }
}
