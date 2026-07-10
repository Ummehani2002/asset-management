@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-ticket-detailed me-2"></i>Ticket {{ $ticket->ticket_number }}</h2>
            <p class="text-muted mb-0">{{ $ticket->site_location }} · {{ $ticket->employee_name }}</p>
        </div>
        <a href="{{ route('time.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left me-2"></i>Back</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="master-table-card h-100">
                <div class="card-body">
                    <small class="text-muted d-block">Total Time</small>
                    <strong class="fs-4">{{ \App\Models\TimeManagement::formatDuration($ticket->totalDurationHours()) }}</strong>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="master-table-card h-100">
                <div class="card-body">
                    <small class="text-muted d-block">Visits</small>
                    <strong class="fs-4">{{ $ticket->visitCount() }}</strong>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="master-table-card h-100">
                <div class="card-body">
                    <small class="text-muted d-block">Status</small>
                    <span class="badge {{ $ticket->status === 'completed' ? 'bg-success' : 'bg-warning text-dark' }} fs-6">
                        {{ ucfirst($ticket->status) }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="master-table-card h-100">
                <div class="card-body">
                    <small class="text-muted d-block">Task</small>
                    <strong>{{ $ticket->task_description }}</strong>
                </div>
            </div>
        </div>
    </div>

    @if($ticket->isOpen())
    <div class="mb-3">
        <a href="{{ route('time.create', ['work_ticket_id' => $ticket->id]) }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Log Another Visit
        </a>
    </div>
    @endif

    <div class="master-table-card">
        <div class="card-header">
            <h5 style="color: white; margin: 0;"><i class="bi bi-list-check me-2"></i>All Visits</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Time Spent</th>
                            <th>Action Taken</th>
                            <th>Remarks</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ticket->visits as $index => $visit)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $visit->job_card_date?->format('Y-m-d') }}</td>
                            <td>{{ $visit->start_time?->format('H:i') }}</td>
                            <td>{{ $visit->end_time?->format('H:i') }}</td>
                            <td><strong>{{ \App\Models\TimeManagement::formatDuration($visit->duration_hours ?? 0) }}</strong></td>
                            <td>{{ $visit->action_taken ?? '-' }}</td>
                            <td>{{ $visit->remarks ?? '-' }}</td>
                            <td>
                                <a href="{{ route('time.edit', $visit->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No visits logged yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
