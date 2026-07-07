@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h2><i class="bi bi-clock-history me-2"></i>Time Management</h2>
                @unless($isAdmin)
                    <p class="text-muted mb-0">Log your daily tasks and time spent.</p>
                @endunless
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

    <div class="master-table-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('time.index') }}" class="row g-3 align-items-end">
                @if($isAdmin)
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
                            <th>Time Spent</th>
                            <th>Overtime</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tasks as $key => $task)
                        @php
                            $displayStatus = $task->status === 'in_progress' ? 'pending' : $task->status;
                        @endphp
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td><strong>{{ $task->ticket_number }}</strong></td>
                            @if($isAdmin)<td>{{ $task->employee_name }}</td>@endif
                            <td>{{ $task->category ?? 'End User Support' }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($task->task_description ?? '-', 40) }}</td>
                            <td>{{ $task->site_location ?? '-' }}</td>
                            <td>{{ $task->job_card_date ? $task->job_card_date->format('Y-m-d') : '-' }}</td>
                            <td>{{ $task->start_time ? $task->start_time->format('H:i') : '-' }}</td>
                            <td>{{ $task->end_time ? $task->end_time->format('H:i') : '-' }}</td>
                            <td>{{ $task->duration_hours ?? 0 }} hrs</td>
                            <td>
                                @if(($task->overtime_hours ?? 0) > 0)
                                    <span class="text-danger fw-bold">{{ $task->overtime_hours }} hrs</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $displayStatus === 'completed' ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ ucfirst($displayStatus) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('time.edit', $task->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('time.destroy', $task->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this work log?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 13 : 12 }}" class="text-center text-muted py-4">No work logs found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
