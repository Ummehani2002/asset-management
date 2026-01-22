@extends('layouts.app')
@section('content')
<div class="container">
    <h2><i class="bi bi-check-circle me-2"></i>Complete Job Card / Close Ticket</h2>

    <div class="card mb-3">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Task Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Ticket Number:</strong> <span class="badge bg-primary">{{ $record->ticket_number }}</span></p>
                    <p><strong>Employee:</strong> {{ $record->employee_name }}</p>
                    <p><strong>Project:</strong> {{ $record->project_name }}</p>
                    <p><strong>Job Card Date:</strong> {{ \Carbon\Carbon::parse($record->job_card_date)->format('Y-m-d') }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Start Time:</strong> {{ $record->start_time ? $record->start_time->setTimezone('Asia/Dubai')->format('Y-m-d H:i') . ' (Dubai Time)' : 'N/A' }}</p>
                    <p><strong>Standard Hours:</strong> <span class="badge bg-secondary">{{ $record->standard_man_hours }} hours</span></p>
                    @php
                        $now = \Carbon\Carbon::now('Asia/Dubai');
                        $start = \Carbon\Carbon::parse($record->start_time)->setTimezone('Asia/Dubai');
                        $currentHours = round($now->diffInMinutes($start) / 60, 2);
                        $currentPerformance = 0;
                        if ($record->standard_man_hours > 0 && $currentHours > 0) {
                            $currentPerformance = ($record->standard_man_hours / $currentHours) * 100;
                            $currentPerformance = max(0, min(200, round($currentPerformance, 2)));
                        }
                        $hoursOver = max(0, $currentHours - $record->standard_man_hours);
                    @endphp
                    <p><strong>Current Duration:</strong> <span class="badge {{ $currentHours > $record->standard_man_hours ? 'bg-danger' : 'bg-success' }}">{{ $currentHours }} hours</span></p>
                    @if($currentHours > $record->standard_man_hours)
                        <p><strong>Hours Over Standard:</strong> <span class="text-danger fw-bold">{{ $hoursOver }} hours</span></p>
                    @endif
                    <p><strong>Current Performance:</strong> 
                        <span class="badge {{ $currentPerformance >= 100 ? 'bg-success' : ($currentPerformance >= 50 ? 'bg-warning' : 'bg-danger') }}">
                            {{ $currentPerformance }}%
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('time.update', $record->id) }}" method="POST" autocomplete="off">
        @csrf

        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Complete Task</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Note:</strong> When you click "Close Ticket", the end time will be automatically set to the current time ({{ \Carbon\Carbon::now('Asia/Dubai')->format('Y-m-d H:i') }} Dubai Time), and the performance will be calculated automatically based on standard hours vs actual hours.
                </div>

                <div class="mb-3">
                    <label class="form-label">Delay Reason (if task was delayed)</label>
                    <textarea name="delay_reason" class="form-control" rows="3" 
                              placeholder="Optional: Explain why the task took longer than expected..."></textarea>
                    <small class="text-muted">This will help track performance and identify improvement areas.</small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Close Ticket & Mark as Completed
                    </button>
                    <button type="button" class="btn btn-secondary btn-lg" onclick="resetForm(this)">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <a href="{{ route('time.index') }}" class="btn btn-secondary btn-lg">
                        <i class="bi bi-arrow-left me-2"></i>Back to List
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
