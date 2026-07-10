@extends('layouts.work-log-app')

@section('title', 'Work Log Form')
@section('page-title', 'Work Log Form')

@section('content')
@if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger py-2">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

@if(!empty($openTickets) && $openTickets->isNotEmpty())
<div class="mb-3">
    <div class="log-card" style="border-left: 4px solid #198754;">
        <div class="fw-semibold mb-2">Open Tickets</div>
        @foreach($openTickets as $ticket)
            <div class="d-flex justify-content-between align-items-center small py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                <div>
                    <strong>{{ $ticket->ticket_number }}</strong>
                    <div class="text-muted">{{ $ticket->site_location }}</div>
                </div>
                <a href="{{ route('worklog.create', ['work_ticket_id' => $ticket->id]) }}" class="btn btn-sm btn-success">Add Visit</a>
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="form-card">
    @include('work_log_app._form', [
        'action' => route('time.store'),
        'employeeName' => $employeeName,
        'record' => null,
        'todayTotals' => $todayTotals ?? ['total_hours' => 0, 'job_count' => 0],
        'isAdmin' => $isAdmin ?? false,
        'openTickets' => $openTickets ?? collect(),
        'continueTicket' => $continueTicket ?? null,
    ])
</div>

@if(!empty($todayJobs) && $todayJobs->isNotEmpty())
<div class="mt-4">
    <h6 class="fw-semibold mb-2">Today's jobs</h6>
    @foreach($todayJobs as $job)
        @php $jobStatus = $job->status === 'in_progress' ? 'pending' : $job->status; @endphp
        <div class="log-card {{ $jobStatus }} mb-2">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong class="small">{{ $job->task_description }}</strong>
                    <div class="small text-muted">
                        {{ $job->start_time?->format('H:i') }}–{{ $job->end_time?->format('H:i') }}
                        · {{ \App\Models\TimeManagement::formatDuration($job->duration_hours) }}
                    </div>
                </div>
                <span class="badge {{ $jobStatus === 'completed' ? 'bg-success' : 'bg-warning text-dark' }}">
                    {{ ucfirst($jobStatus) }}
                </span>
            </div>
            <div class="mt-2 d-grid gap-1">
                <a href="{{ route('worklog.edit', $job->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                @if($jobStatus !== 'completed')
                <a href="{{ route('worklog.edit', $job->id) }}?status=completed" class="btn btn-sm btn-success">Mark Completed</a>
                @endif
            </div>
        </div>
    @endforeach
</div>
@endif
@endsection
