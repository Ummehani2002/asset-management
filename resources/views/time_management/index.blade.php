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
        <div class="card-header">
            <h5 style="color: white; margin: 0;">
                <i class="bi bi-file-earmark-bar-graph me-2"></i>Today's Work — {{ today()->format('l, M j, Y') }}
            </h5>
        </div>
        <div class="card-body">
            @php
                $activeToday = collect($todaySummaries ?? [])
                    ->filter(fn ($summary) => ($summary['total_hours'] ?? 0) > 0)
                    ->sortBy('employee_name')
                    ->values();
                $maxEmployeeHours = max(8, (float) $activeToday->max('total_hours'));
            @endphp

            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="small text-muted">Employees Worked</div>
                        <div class="fs-4 fw-bold text-primary">{{ $todayTotals['active_count'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="small text-muted">Team Hours</div>
                        <div class="fs-4 fw-bold text-success">{{ \App\Models\TimeManagement::formatDuration($todayTotals['total_hours'] ?? 0) }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="small text-muted">Total Visits</div>
                        <div class="fs-4 fw-bold">{{ collect($todaySummaries ?? [])->sum('job_count') }}</div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="border rounded p-3 h-100 bg-light">
                        <div class="small text-muted">Overtime</div>
                        <div class="fs-4 fw-bold text-danger">{{ \App\Models\TimeManagement::formatDuration($todayTotals['overtime_hours'] ?? 0) }}</div>
                    </div>
                </div>
            </div>

            @forelse($activeToday as $summary)
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
                    <div class="progress" style="height: 22px;">
                        <div class="progress-bar bg-success" style="width: {{ $regularWidth }}%"></div>
                        @if($overtimeWidth > 0)
                            <div class="progress-bar bg-danger" style="width: {{ $overtimeWidth }}%"></div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-3">No completed work logged for today yet.</div>
            @endforelse

            <div class="d-flex gap-3 small text-muted border-top pt-3 mt-3 mb-4">
                <span><span class="badge bg-success">&nbsp;</span> Regular hours</span>
                <span><span class="badge bg-danger">&nbsp;</span> Overtime</span>
                <span>{{ $todayTotals['active_count'] ?? 0 }} of {{ $todayTotals['employee_count'] ?? 0 }} employee(s) worked today.</span>
            </div>

            <h6 class="mb-3"><i class="bi bi-funnel me-2"></i>Filter and download report</h6>
            <form method="GET" action="{{ route('time.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Employee Name</label>
                    <select name="user_id" class="form-control">
                        <option value="">All Employees</option>
                        @foreach($teamMembers as $member)
                            <option value="{{ $member->id }}" {{ (string) request('user_id') === (string) $member->id ? 'selected' : '' }}>
                                {{ $member->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date</label>
                    <input type="date" name="summary_date" class="form-control" value="{{ $summaryDate ?? today()->format('Y-m-d') }}" required>
                </div>
                <div class="col-md-5">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i> View
                    </button>
                    <a href="{{ route('time.index') }}" class="btn btn-secondary me-2">Clear</a>
                    <div class="dropdown d-inline-block">
                        <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i> Get Report
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('time.export.daily', array_filter(['summary_date' => $summaryDate ?? today()->format('Y-m-d'), 'user_id' => request('user_id'), 'status' => request('status'), 'format' => 'pdf'])) }}">
                                    <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>PDF
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="{{ route('time.export.daily', array_filter(['summary_date' => $summaryDate ?? today()->format('Y-m-d'), 'user_id' => request('user_id'), 'status' => request('status'), 'format' => 'csv'])) }}">
                                    <i class="bi bi-file-earmark-excel me-2 text-success"></i>Excel (CSV)
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @else
    <div class="master-table-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('time.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="summary_date" class="form-control" value="{{ $summaryDate ?? today()->format('Y-m-d') }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('time.index') }}" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="master-table-card">
        <div class="card-header">
            <h5 style="color: white; margin: 0;">
                <i class="bi bi-list-ul me-2"></i>Work Logs for {{ \Carbon\Carbon::parse($summaryDate ?? today())->format('M j, Y') }} ({{ $tasks->count() }})
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
                            $statusLabel = $task->displayStatusLabel();
                            $canStop = $isRunning && $task->isOwnedBy(auth()->user());
                            $canContinue = ! $isRunning && $task->canContinueVisit() && $task->isOwnedBy(auth()->user());
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
                                @php
                                    $ticketTotal = $task->ticketTotalHours();
                                    $visitCount = $task->workTicket?->visitCount() ?? 1;
                                @endphp
                                @if($isRunning)
                                    <span class="text-warning fw-semibold"
                                          data-elapsed-start="{{ $task->start_time?->toIso8601String() }}">…</span>
                                    <div class="small text-muted">this visit (running)</div>
                                @endif
                                <strong>{{ \App\Models\TimeManagement::formatDuration($ticketTotal) }}</strong>
                                <div class="small text-muted">
                                    {{ $visitCount }} visit{{ $visitCount === 1 ? '' : 's' }} total
                                    @unless($isRunning)
                                        · last visit {{ \App\Models\TimeManagement::formatDuration($task->duration_hours ?? 0) }}
                                    @endunless
                                </div>
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
                                @elseif($statusLabel === 'Continue Visit')
                                    <span class="badge bg-info text-dark">Continue Visit</span>
                                @elseif($statusLabel === 'Completed')
                                    <span class="badge bg-success">Completed</span>
                                @else
                                    <span class="badge bg-secondary">{{ $statusLabel }}</span>
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
                                @if($canContinue)
                                    <a href="{{ route('time.create', ['work_ticket_id' => $task->work_ticket_id]) }}" class="btn btn-sm btn-primary">
                                        <i class="bi bi-play-circle"></i> Continue Visit
                                    </a>
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
                                @endunless
                                <form action="{{ route('time.destroy', $task->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this work log?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Remove visit">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
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
