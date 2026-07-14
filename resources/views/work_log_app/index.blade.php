@extends('layouts.work-log-app')

@section('title', $isAdmin ? 'Team Progress' : 'My Jobs')
@section('page-title', $isAdmin ? 'Team Progress' : 'My Jobs')

@section('content')
<div class="mb-3">
    @unless($isAdmin)
    <a href="{{ route('worklog.create') }}" class="btn btn-sm btn-outline-primary w-100">
        <i class="bi bi-pencil-square me-1"></i> Back to Work Log Form
    </a>
    @endunless
</div>

<div id="installBanner" class="install-banner">
    <span><i class="bi bi-download me-1"></i> Install app on your phone</span>
    <button type="button" id="installBtn" class="btn btn-sm btn-light">Install</button>
</div>

@if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning py-2">{{ session('warning') }}</div>
@endif

<div class="stat-grid">
    <div class="stat-card">
        <div class="num text-primary" style="font-size:1rem;line-height:1.3;">
            {{ $isAdmin ? \App\Models\TimeManagement::formatDuration($stats['hours_today'] ?? 0) : \App\Models\TimeManagement::formatDuration($stats['hours_today'] ?? 0) }}
        </div>
        <div class="lbl">{{ $isAdmin ? 'Team Hours Today' : 'Time Today' }}</div>
    </div>
    <div class="stat-card">
        <div class="num text-warning">{{ $stats['pending'] }}</div>
        <div class="lbl">Pending</div>
    </div>
    <div class="stat-card">
        <div class="num text-success">{{ $stats['completed'] }}</div>
        <div class="lbl">Completed</div>
    </div>
    <div class="stat-card">
        <div class="num">{{ $stats['total'] }}</div>
        <div class="lbl">Total</div>
    </div>
</div>

<form method="GET" action="{{ route('worklog.index') }}" class="mb-3">
    <div class="row g-2">
        @if($isAdmin)
        <div class="col-12">
            <label class="form-label small text-muted mb-1">Hours summary date</label>
            <input type="date" name="summary_date" class="form-control form-control-sm" value="{{ $summaryDate ?? today()->format('Y-m-d') }}" onchange="this.form.submit()">
        </div>
        <div class="col-6">
            <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Members</option>
                @foreach($teamMembers as $member)
                    <option value="{{ $member->id }}" {{ request('user_id') == $member->id ? 'selected' : '' }}>
                        {{ $member->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="col-{{ $isAdmin ? '6' : '12' }}">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
            </select>
        </div>
    </div>
</form>

@if($isAdmin)
<div class="mb-3">
    <div class="log-card" style="border-left: 4px solid #198754;">
        <div class="fw-semibold mb-2">
            <i class="bi bi-people me-1"></i> Employee Hours — {{ \Carbon\Carbon::parse($summaryDate ?? today())->format('M d, Y') }}
        </div>
        @forelse($dailySummaries as $summary)
            <div class="d-flex justify-content-between align-items-center small py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                <span>
                    <strong>{{ $summary['employee_name'] }}</strong>
                    <span class="text-muted"> · {{ $summary['job_count'] }} visit(s)</span>
                </span>
                <span>
                    <strong class="{{ ($summary['total_hours'] ?? 0) > 0 ? 'text-success' : 'text-muted' }}">
                        {{ \App\Models\TimeManagement::formatDuration($summary['total_hours']) }}
                    </strong>
                    @if(($summary['overtime_hours'] ?? 0) > 0)
                        <span class="text-danger ms-1">OT {{ \App\Models\TimeManagement::formatDuration($summary['overtime_hours']) }}</span>
                    @endif
                </span>
            </div>
        @empty
            <div class="small text-muted">No employees found.</div>
        @endforelse
        @if(!empty($dailySummaries))
        <div class="d-flex justify-content-between align-items-center small pt-2 mt-2 border-top fw-semibold">
            <span>Team Total</span>
            <span>
                {{ \App\Models\TimeManagement::formatDuration($dailySummaryTotals['total_hours'] ?? 0) }}
                @if(($dailySummaryTotals['overtime_hours'] ?? 0) > 0)
                    <span class="text-danger ms-1">OT {{ \App\Models\TimeManagement::formatDuration($dailySummaryTotals['overtime_hours']) }}</span>
                @endif
            </span>
        </div>
        <div class="small text-muted mt-1">
            {{ $dailySummaryTotals['active_count'] ?? 0 }} of {{ $dailySummaryTotals['employee_count'] ?? 0 }} employee(s) logged time.
        </div>
        @endif
    </div>
</div>
@endif

@if($isAdmin && !empty($ticketSummaries))
<div class="mb-3">
    <div class="log-card" style="border-left: 4px solid #0d6efd;">
        <div class="fw-semibold mb-2"><i class="bi bi-ticket-detailed me-1"></i> Ticket Totals by Location</div>
        @foreach($ticketSummaries as $summary)
            <div class="py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <strong>{{ $summary['ticket_number'] }}</strong>
                        <div class="small text-muted">{{ $summary['employee_name'] }} · {{ $summary['site_location'] }}</div>
                    </div>
                    <strong>{{ \App\Models\TimeManagement::formatDuration($summary['total_hours']) }}</strong>
                </div>
                <div class="small text-muted mt-1">{{ $summary['visit_count'] }} visit(s) · {{ ucfirst($summary['status']) }}</div>
                <a href="{{ route('time.ticket.show', $summary['id']) }}" class="small">View all visits</a>
            </div>
        @endforeach
    </div>
</div>
@endif

@if($tasks->isEmpty())
    <div class="text-center text-muted py-5">
        <i class="bi bi-journal-x" style="font-size: 2.5rem;"></i>
        <p class="mt-2 mb-3">No work logs yet.</p>
        @unless($isAdmin)
        <a href="{{ route('worklog.create') }}" class="btn btn-app" style="width: auto; padding: 10px 24px;">
            <i class="bi bi-plus-circle me-1"></i> New Work Log
        </a>
        @endunless
    </div>
@else
    @foreach($tasks as $task)
        @php
            $displayStatus = $task->ticketStatus();
        @endphp
        <div class="log-card {{ $displayStatus }}">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <div>
                    <strong>{{ $task->ticket_number }}</strong>
                    @if($isAdmin)
                        <small class="text-muted d-block">{{ $task->employee_name }}</small>
                    @endif
                </div>
                <span class="badge badge-status {{ $displayStatus === 'completed' ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ ucfirst($displayStatus) }}
                </span>
            </div>
            <div class="fw-semibold mb-1">{{ $task->task_description }}</div>
            <div class="small text-muted mb-2">
                <i class="bi bi-geo-alt"></i> {{ $task->site_location }}
                &nbsp;·&nbsp;
                <i class="bi bi-calendar3"></i> {{ $task->job_card_date?->format('M d, Y') }}
            </div>
            <div class="d-flex justify-content-between align-items-center small">
                <span>
                    <i class="bi bi-clock"></i>
                    {{ $task->start_time?->format('H:i') }} – {{ $task->end_time?->format('H:i') }}
                    <strong class="ms-1">{{ \App\Models\TimeManagement::formatDuration($task->duration_hours) }}</strong>
                </span>
                @if($isAdmin && ($task->overtime_hours ?? 0) > 0)
                    <span class="text-danger fw-bold">OT: {{ \App\Models\TimeManagement::formatDuration($task->overtime_hours) }}</span>
                @endif
            </div>
            <div class="mt-2 d-grid gap-2">
                @if($task->work_ticket_id)
                <a href="{{ route('time.ticket.show', $task->work_ticket_id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> View visits
                </a>
                @endif
                @unless($isAdmin)
                <a href="{{ route('worklog.edit', $task->id) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil"></i> Edit
                </a>
                @if($displayStatus !== 'completed')
                <a href="{{ route('worklog.edit', $task->id) }}?status=completed" class="btn btn-sm btn-success">
                    <i class="bi bi-check-circle"></i> Mark Completed
                </a>
                @endif
                @endunless
            </div>
        </div>
    @endforeach
@endif
@endsection
