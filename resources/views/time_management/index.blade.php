@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-clock-history me-2"></i>Time Management Records</h2>
                
            </div>
            <a href="{{ route('time.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add New Job
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- In Progress Tasks --}}
    <div class="master-table-card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 style="color: white; margin: 0;">
                <i class="bi bi-hourglass-split me-2"></i>In Progress Tasks ({{ $inProgressTasks->count() }})
            </h5>
            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="downloadInProgress" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Download
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="downloadInProgress">
                    <li><a class="dropdown-item" href="{{ route('time.export', ['status' => 'in_progress', 'format' => 'pdf']) }}">
                        <i class="bi bi-file-pdf me-2"></i>PDF
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('time.export', ['status' => 'in_progress', 'format' => 'csv']) }}">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV
                    </a></li>
                </ul>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Ticket</th>
                            <th>Employee</th>
                            <th>Project</th>
                            <th>Job Date</th>
                            <th>Start Time</th>
                            <th>Current Duration (hrs)</th>
                            <th>Allocated Time (hrs)</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inProgressTasks as $key => $r)
                        @php
                            $now = \Carbon\Carbon::now('Asia/Dubai');
                            $start = $r->start_time ? \Carbon\Carbon::parse($r->start_time)->setTimezone('Asia/Dubai') : null;
                            $currentHours = $start ? round($now->diffInMinutes($start) / 60, 2) : 0;
                            $isOverdue = $r->standard_man_hours > 0 && $currentHours > $r->standard_man_hours;
                            $hoursOver = $isOverdue ? round($currentHours - $r->standard_man_hours, 2) : 0;
                            $currentPerformance = 0;
                            if ($r->standard_man_hours > 0 && $currentHours > 0) {
                                $currentPerformance = ($r->standard_man_hours / $currentHours) * 100;
                                $currentPerformance = max(0, min(200, round($currentPerformance, 2)));
                            }
                        @endphp
                        <tr class="{{ $isOverdue ? 'table-danger' : '' }}">
                            <td>{{ $key + 1 }}</td>
                            <td>
                                <strong>{{ $r->ticket_number }}</strong>
                                @if($isOverdue)
                                    <span class="badge bg-danger ms-2" title="Task exceeded allocated time">
                                        <i class="bi bi-exclamation-triangle"></i> OVERDUE
                                    </span>
                                @endif
                            </td>
                            <td>{{ $r->employee_name }}</td>
                            <td>{{ $r->project_name }}</td>
                            <td>{{ $r->job_card_date ? \Carbon\Carbon::parse($r->job_card_date)->format('Y-m-d') : '-' }}</td>
                            <td>{{ $r->start_time ? $r->start_time->setTimezone('Asia/Dubai')->format('Y-m-d H:i') . ' (Dubai)' : '-' }}</td>
                            <td>
                                @if($currentHours > 0)
                                    <span class="{{ $isOverdue ? 'text-danger fw-bold' : '' }}">
                                        {{ $currentHours }} hrs
                                        @if($isOverdue)
                                            <br><small class="text-danger">(+{{ $hoursOver }} hrs over)</small>
                                        @endif
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $r->standard_man_hours ?? '-' }} hrs</td>
                            <td>
                                <span class="badge bg-warning">{{ ucfirst($r->status) }}</span>
                                @if($currentPerformance > 0)
                                    <br><small class="text-muted">Performance: 
                                        <span class="{{ $currentPerformance >= 100 ? 'text-success' : ($currentPerformance >= 50 ? 'text-warning' : 'text-danger') }}">
                                            {{ $currentPerformance }}%
                                        </span>
                                    </small>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('time.edit', $r->id) }}" class="btn btn-sm btn-success" title="Close ticket and mark as completed">
                                    <i class="bi bi-check-circle"></i> Close Ticket
                                </a>
                                <form action="{{ route('time.destroy', $r->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No in-progress tasks found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Completed Tasks --}}
    <div class="master-table-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 style="color: white; margin: 0;">
                <i class="bi bi-check-circle me-2"></i>Completed Tasks ({{ $completedTasks->count() }})
            </h5>
            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="downloadCompleted" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Download
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="downloadCompleted">
                    <li><a class="dropdown-item" href="{{ route('time.export', ['status' => 'completed', 'format' => 'pdf']) }}">
                        <i class="bi bi-file-pdf me-2"></i>PDF
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('time.export', ['status' => 'completed', 'format' => 'csv']) }}">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV
                    </a></li>
                </ul>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Ticket</th>
                            <th>Employee</th>
                            <th>Project</th>
                            <th>Job Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Actual Duration (hrs)</th>
                            <th>Allocated Time (hrs)</th>
                            <th>Status</th>
                            <th>Performance (%)</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($completedTasks as $key => $r)
                        <tr class="{{ ($r->delayed_days && $r->delayed_days > 0) ? 'table-danger' : '' }}">
                            <td>{{ $key + 1 }}</td>
                            <td><strong>{{ $r->ticket_number }}</strong></td>
                            <td>{{ $r->employee_name }}</td>
                            <td>{{ $r->project_name }}</td>
                            <td>{{ $r->job_card_date ? \Carbon\Carbon::parse($r->job_card_date)->format('Y-m-d') : '-' }}</td>
                            <td>{{ $r->start_time ? $r->start_time->setTimezone('Asia/Dubai')->format('Y-m-d H:i') . ' (Dubai)' : '-' }}</td>
                            <td>{{ $r->end_time ? $r->end_time->setTimezone('Asia/Dubai')->format('Y-m-d H:i') . ' (Dubai)' : '-' }}</td>
                            <td>{{ $r->duration_hours ?? '-' }} hrs</td>
                            <td>{{ $r->standard_man_hours ?? '-' }} hrs</td>
                            <td><span class="badge bg-success">{{ ucfirst($r->status) }}</span></td>
                            <td>
                                @if($r->performance_percent)
                                    <span class="badge {{ $r->performance_percent >= 100 ? 'bg-success' : ($r->performance_percent >= 50 ? 'bg-warning' : 'bg-danger') }}">
                                        {{ $r->performance_percent }}%
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <form action="{{ route('time.destroy', $r->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">No completed tasks found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
