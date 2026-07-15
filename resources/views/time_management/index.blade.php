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
                <i class="bi bi-people me-2"></i>Employee Hours — {{ \Carbon\Carbon::parse($summaryDate ?? today())->format('D, M j, Y') }}
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
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th>Visits</th>
                            <th>Hours Worked</th>
                            <th>Overtime</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailySummaries as $summary)
                        <tr class="{{ ($summary['total_hours'] ?? 0) <= 0 ? 'table-light' : '' }}">
                            <td><strong>{{ $summary['employee_name'] }}</strong></td>
                            <td>{{ $summary['job_count'] }}</td>
                            <td>
                                <strong class="{{ ($summary['total_hours'] ?? 0) > 0 ? 'text-success' : 'text-muted' }}">
                                    {{ \App\Models\TimeManagement::formatDuration($summary['total_hours']) }}
                                </strong>
                            </td>
                            <td>
                                @if(($summary['overtime_hours'] ?? 0) > 0)
                                    <span class="text-danger fw-bold">{{ \App\Models\TimeManagement::formatDuration($summary['overtime_hours']) }}</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">No employees found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if(!empty($dailySummaries))
                    <tfoot class="table-light">
                        <tr>
                            <th>Team Total</th>
                            <th>{{ collect($dailySummaries)->sum('job_count') }}</th>
                            <th><strong>{{ \App\Models\TimeManagement::formatDuration($dailySummaryTotals['total_hours'] ?? 0) }}</strong></th>
                            <th>
                                @if(($dailySummaryTotals['overtime_hours'] ?? 0) > 0)
                                    <span class="text-danger fw-bold">{{ \App\Models\TimeManagement::formatDuration($dailySummaryTotals['overtime_hours']) }}</span>
                                @else
                                    -
                                @endif
                            </th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            <div class="p-3 border-top small text-muted">
                {{ $dailySummaryTotals['active_count'] ?? 0 }} of {{ $dailySummaryTotals['employee_count'] ?? 0 }} employee(s) logged time on this date.
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

    @if(!empty($ticketSummaries))
    <div class="master-table-card mb-4">
        <div class="card-header">
            <h5 style="color: white; margin: 0;">
                <i class="bi bi-ticket-detailed me-2"></i>Tickets — Total Time by Location
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ticket</th>
                            @if($isAdmin)<th>Employee</th>@endif
                            <th>Location</th>
                            <th>Task</th>
                            <th>Visits</th>
                            <th>Total Time</th>
                            <th>First Visit</th>
                            <th>Last Visit</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ticketSummaries as $summary)
                        <tr>
                            <td><strong>{{ $summary['ticket_number'] }}</strong></td>
                            @if($isAdmin)<td>{{ $summary['employee_name'] }}</td>@endif
                            <td>{{ $summary['site_location'] }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($summary['task_description'], 40) }}</td>
                            <td>{{ $summary['visit_count'] }}</td>
                            <td><strong>{{ \App\Models\TimeManagement::formatDuration($summary['total_hours']) }}</strong></td>
                            <td>{{ $summary['first_visit'] ? \Carbon\Carbon::parse($summary['first_visit'])->format('Y-m-d') : '-' }}</td>
                            <td>{{ $summary['last_visit'] ? \Carbon\Carbon::parse($summary['last_visit'])->format('Y-m-d') : '-' }}</td>
                            <td>
                                <span class="badge {{ $summary['status'] === 'completed' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ ucfirst($summary['status']) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('time.ticket.show', $summary['id']) }}" class="btn btn-sm btn-outline-primary">View</a>
                                @unless($isAdmin)
                                    @if($summary['status'] === 'pending')
                                        <a href="{{ route('time.create', ['work_ticket_id' => $summary['id']]) }}" class="btn btn-sm btn-primary">Add Visit</a>
                                    @endif
                                @endunless
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

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
