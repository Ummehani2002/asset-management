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

<div class="form-card">
    @include('work_log_app._form', [
        'action' => route('time.store'),
        'ticketNumber' => $ticketNumber,
        'employeeName' => $employeeName,
        'record' => null,
        'todayTotals' => $todayTotals ?? ['total_hours' => 0, 'job_count' => 0],
        'isAdmin' => $isAdmin ?? false,
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
