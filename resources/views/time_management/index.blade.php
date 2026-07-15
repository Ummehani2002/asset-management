@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2><i class="bi bi-clock-history me-2"></i>Time Management</h2>
                <p class="text-muted mb-0">Log your daily tasks and time spent.</p>
            </div>
            <a href="{{ route('time.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>New Work Log
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $myToday = \App\Models\TimeManagement::getDailyTotals(auth()->id(), auth()->user()->employee_id, today()->format('Y-m-d'));
    @endphp
    <div class="alert alert-light border mb-4">
        <strong>Today:</strong> {{ \App\Models\TimeManagement::formatDuration($myToday['total_hours']) }} logged across {{ $myToday['job_count'] }} job(s).
        <a href="{{ route('time.create') }}" class="ms-2">Log another job</a>
    </div>

    @if($isAdmin)
    <div class="master-table-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 style="color: white; margin: 0;">
                <i class="bi bi-bar-chart-fill me-2"></i>Employee Work Hours — {{ \Carbon\Carbon::parse($summaryDate ?? today())->format('D, M j, Y') }}
            </h5>
            <form method="GET" action="{{ route('time.index') }}" class="d-flex align-items-center gap-2 mb-0">
                @foreach(request()->except('summary_date') as $key => $value)
                    @if(is_scalar($value) && $value !== '')
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <input type="date" name="summary_date" class="form-control form-control-sm" value="{{ $summaryDate ?? today()->format('Y-m-d') }}" onchange="this.form.submit()">
            </form>
        </div>
        <div class="card-body">
            @php
                $activeDailySummaries = collect($dailySummaries ?? [])
                    ->filter(fn ($summary) => ($summary['total_hours'] ?? 0) > 0)
                    ->sortByDesc('total_hours')
                    ->values();
                $maxEmployeeHours = max(8, (float) $activeDailySummaries->max('total_hours'));
            @endphp

            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="small text-muted">Employees Worked</div>
                        <div class="fs-4 fw-bold text-primary">{{ $dailySummaryTotals['active_count'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="small text-muted">Team Hours</div>
                        <div class="fs-4 fw-bold text-success">{{ \App\Models\TimeManagement::formatDuration($dailySummaryTotals['total_hours'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="small text-muted">Total Visits</div>
                        <div class="fs-4 fw-bold">{{ collect($dailySummaries)->sum('job_count') }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="small text-muted">Overtime</div>
                        <div class="fs-4 fw-bold text-danger">{{ \App\Models\TimeManagement::formatDuration($dailySummaryTotals['overtime_hours'] ?? 0) }}</div>
                    </div>
                </div>
            </div>

            @forelse($activeDailySummaries as $summary)
                @php
                    $totalHours = (float) ($summary['total_hours'] ?? 0);
                    $overtimeHours = (float) ($summary['overtime_hours'] ?? 0);
                    $regularHours = max(0, $totalHours - $overtimeHours);
                    $regularWidth = min(100, ($regularHours / $maxEmployeeHours) * 100);
                    $overtimeWidth = min(100 - $regularWidth, ($overtimeHours / $maxEmployeeHours) * 100);
                @endphp
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                        <div>
                            <strong>{{ $summary['employee_name'] }}</strong>
                            <span class="small text-muted ms-2">{{ $summary['job_count'] }} visit(s)</span>
                        </div>
                        <div class="text-nowrap">
                            <strong>{{ \App\Models\TimeManagement::formatDuration($totalHours) }}</strong>
                            @if($overtimeHours > 0)
                                <span class="small text-danger ms-2">OT {{ \App\Models\TimeManagement::formatDuration($overtimeHours) }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="progress" style="height: 22px;" title="{{ $summary['employee_name'] }}: {{ \App\Models\TimeManagement::formatDuration($totalHours) }}">
                        <div class="progress-bar bg-success" style="width: {{ $regularWidth }}%" aria-label="Regular hours"></div>
                        @if($overtimeWidth > 0)
                            <div class="progress-bar bg-danger" style="width: {{ $overtimeWidth }}%" aria-label="Overtime hours"></div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-4">No completed work hours logged for this date.</div>
            @endforelse

            <div class="d-flex gap-3 small text-muted border-top pt-3 mt-3">
                <span><span class="badge bg-success">&nbsp;</span> Regular hours</span>
                <span><span class="badge bg-danger">&nbsp;</span> Overtime</span>
                <span>{{ $dailySummaryTotals['active_count'] ?? 0 }} of {{ $dailySummaryTotals['employee_count'] ?? 0 }} employee(s) worked.</span>
            </div>
        </div>
    </div>
    @endif

    <div class="master-table-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('time.index') }}" class="row g-3 align-items-end">
                @if($isAdmin)
                <input type="hidden" name="summary_date" value="{{ $summaryDate ?? today()->format('Y-m-d') }}">
                <div class="col-md-3">
                    <label class="form-label">Team Member</label>
                    <select name="user_id" class="form-control">
                        <option value="">All Members</option>
                        @foreach($teamMembers as $member)
                            <option value="{{ $member->id }}" {{ request('user_id') == $member->id ? 'selected' : '' }}>
                                {{ $member->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('time.index') }}" class="btn btn-secondary">Clear</a>
                    <div class="dropdown d-inline-block ms-2">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('time.export', array_merge(request()->query(), ['format' => 'pdf'])) }}">PDF</a></li>
                            <li><a class="dropdown-item" href="{{ route('time.export', array_merge(request()->query(), ['format' => 'csv'])) }}">CSV</a></li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="master-table-card">
        <div class="card-header">
            <h5 style="color: white; margin: 0;">
                <i class="bi bi-list-ul me-2"></i>Work Logs ({{ $tasks->count() }})
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Ticket</th>
                            @if($isAdmin)<th>Employee</th>@endif
                            <th>Category</th>
                            <th>Task</th>
                            <th>Site/Location</th>
                            <th>Date</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Visit Time</th>
                            @if($isAdmin)<th>Overtime</th>@endif
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tasks as $key => $task)
                        @php
                            $isRunning = $task->isRunning();
                            $displayStatus = $isRunning ? 'running' : $task->ticketStatus();
                            $canStop = $isRunning && $task->isOwnedBy(auth()->user());
                        @endphp
                        <tr class="{{ $isRunning ? 'table-warning' : '' }}">
                            <td>{{ $key + 1 }}</td>
                            <td>
                                <strong>{{ $task->ticket_number }}</strong>
                                @if($task->work_ticket_id)
                                    <div><a href="{{ route('time.ticket.show', $task->work_ticket_id) }}" class="small">View ticket</a></div>
                                @endif
                            </td>
                            @if($isAdmin)<td>{{ $task->employee_name }}</td>@endif
                            <td>{{ $task->category ?? 'End User Support' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($task->task_description ?? '-', 40) }}</td>
                            <td>{{ $task->site_location ?? '-' }}</td>
                            <td>{{ $task->job_card_date ? $task->job_card_date->format('Y-m-d') : '-' }}</td>
                            <td>{{ $task->start_time ? $task->start_time->format('H:i') : '-' }}</td>
                            <td>{{ $task->end_time ? $task->end_time->format('H:i') : '—' }}</td>
                            <td>
                                @if($isRunning)
                                    <span class="text-warning fw-semibold"
                                          data-elapsed-start="{{ $task->start_time?->toIso8601String() }}">…</span>
                                @else
                                    {{ \App\Models\TimeManagement::formatDuration($task->duration_hours ?? 0) }}
                                @endif
                            </td>
                            @if($isAdmin)
                            <td>
                                @if(($task->overtime_hours ?? 0) > 0)
                                    <span class="text-danger fw-bold">{{ \App\Models\TimeManagement::formatDuration($task->overtime_hours) }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            @endif
                            <td>
                                @if($isRunning)
                                    <span class="badge bg-warning text-dark">Running</span>
                                @else
                                    <span class="badge {{ $displayStatus === 'completed' ? 'bg-success' : 'bg-warning text-dark' }}">
                                        {{ ucfirst($displayStatus) }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-nowrap">
                                @if($canStop)
                                    <form action="{{ route('time.stop', $task->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="complete_ticket" value="0">
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            <i class="bi bi-stop-circle"></i> Stop Visit
                                        </button>
                                    </form>
                                    <form action="{{ route('time.stop', $task->id) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Stop this visit and complete the ticket?');">
                                        @csrf
                                        <input type="hidden" name="complete_ticket" value="1">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-check-circle"></i> Stop & Complete
                                        </button>
                                    </form>
                                @endif
                                @if($task->work_ticket_id)
                                    <a href="{{ route('time.ticket.show', $task->work_ticket_id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                @endif
                                @unless($isAdmin)
                                @unless($isRunning)
                                <a href="{{ route('time.edit', $task->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                @endunless
                                <form action="{{ route('time.destroy', $task->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this work log?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                                </form>
                                @endunless
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 13 : 11 }}" class="text-center text-muted py-4">No work logs found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('js/format-work-duration.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function tickElapsed() {
        document.querySelectorAll('[data-elapsed-start]').forEach(function (el) {
            const start = new Date(el.getAttribute('data-elapsed-start'));
            if (isNaN(start.getTime())) return;
            const hours = Math.max(0, (Date.now() - start.getTime()) / (1000 * 60 * 60));
            el.textContent = typeof formatWorkDuration === 'function' ? formatWorkDuration(hours) : hours.toFixed(2) + ' hrs';
        });
    }
    tickElapsed();
    setInterval(tickElapsed, 30000);
});
</script>
@endsection
