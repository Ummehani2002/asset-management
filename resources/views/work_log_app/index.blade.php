@extends('layouts.work-log-app')

@section('title', 'Team Progress')
@section('page-title', 'Team Progress')
@section('show-nav')

@section('content')
<div class="mb-3">
    <a href="{{ route('worklog.create') }}" class="btn btn-sm btn-outline-primary w-100">
        <i class="bi bi-pencil-square me-1"></i> Back to Work Log Form
    </a>
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
        <div class="num text-primary">{{ $stats['today'] }}</div>
        <div class="lbl">Today</div>
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

@if($tasks->isEmpty())
    <div class="text-center text-muted py-5">
        <i class="bi bi-journal-x" style="font-size: 2.5rem;"></i>
        <p class="mt-2 mb-3">No work logs yet.</p>
        <a href="{{ route('worklog.create') }}" class="btn btn-app" style="width: auto; padding: 10px 24px;">
            <i class="bi bi-plus-circle me-1"></i> New Work Log
        </a>
    </div>
@else
    @foreach($tasks as $task)
        @php
            $displayStatus = $task->status === 'in_progress' ? 'pending' : $task->status;
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
                    <strong class="ms-1">{{ $task->duration_hours }} hrs</strong>
                </span>
                @if(($task->overtime_hours ?? 0) > 0)
                    <span class="text-danger fw-bold">OT: {{ $task->overtime_hours }}h</span>
                @endif
            </div>
            <div class="mt-2">
                <a href="{{ route('worklog.edit', $task->id) }}" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            </div>
        </div>
    @endforeach
@endif
@endsection
