@extends('layouts.work-log-app')

@section('title', $isAdmin ? 'Team Progress' : 'My Jobs')
@section('page-title', $isAdmin ? 'Team Progress' : 'My Jobs')

@section('content')
<div class="mb-3">
    <a href="{{ route('worklog.create') }}" class="btn btn-sm btn-outline-primary w-100">
        <i class="bi bi-play-circle me-1"></i> Start New Work
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
        <div class="num text-primary" style="font-size:1rem;line-height:1.3;">
            {{ \App\Models\TimeManagement::formatDuration($stats['hours_today'] ?? 0) }}
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
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending / Running</option>
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
        @endif
    </div>
</div>
@endif

@if($tasks->isEmpty())
    <div class="text-center text-muted py-5">
        <i class="bi bi-journal-x" style="font-size: 2.5rem;"></i>
        <p class="mt-2 mb-3">No work logs yet.</p>
        <a href="{{ route('worklog.create') }}" class="btn btn-app" style="width: auto; padding: 10px 24px;">
            <i class="bi bi-play-circle me-1"></i> Start Work
        </a>
    </div>
@else
    <div class="table-responsive bg-white rounded border">
        <table class="table table-sm table-bordered mb-0 align-middle" style="font-size: 0.78rem;">
            <thead class="table-light">
                <tr>
                    <th>Ticket</th>
                    @if($isAdmin)<th>Emp</th>@endif
                    <th>Location</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Worked</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($tasks as $task)
                @php
                    $isRunning = $task->isRunning();
                    $canStop = $isRunning && $task->isOwnedBy(auth()->user());
                @endphp
                <tr class="{{ $isRunning ? 'table-warning' : '' }}">
                    <td>
                        <strong>{{ $task->ticket_number }}</strong>
                        <div class="text-muted">{{ \Illuminate\Support\Str::limit($task->task_description, 24) }}</div>
                    </td>
                    @if($isAdmin)
                        <td>{{ \Illuminate\Support\Str::limit($task->employee_name, 12) }}</td>
                    @endif
                    <td>{{ \Illuminate\Support\Str::limit($task->site_location, 14) }}</td>
                    <td>{{ $task->start_time?->format('H:i') ?? '-' }}</td>
                    <td>{{ $task->end_time?->format('H:i') ?? '—' }}</td>
                    <td>
                        @if($isRunning)
                            <span class="text-warning fw-semibold"
                                  data-elapsed-start="{{ $task->start_time?->toIso8601String() }}">…</span>
                        @else
                            {{ \App\Models\TimeManagement::formatDuration($task->duration_hours) }}
                        @endif
                    </td>
                    <td>
                        @if($isRunning)
                            <span class="badge bg-warning text-dark">Running</span>
                        @else
                            <span class="badge {{ $task->ticketStatus() === 'completed' ? 'bg-success' : 'bg-secondary' }}">
                                {{ ucfirst($task->ticketStatus()) }}
                            </span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @if($canStop)
                            <form action="{{ route('time.stop', $task->id) }}" method="POST" class="mb-1">
                                @csrf
                                <input type="hidden" name="_from_app" value="1">
                                <input type="hidden" name="complete_ticket" value="0">
                                <button type="submit" class="btn btn-sm btn-warning py-0 px-2 w-100">Stop Visit</button>
                            </form>
                            <form action="{{ route('time.stop', $task->id) }}" method="POST" class="m-0"
                                  onsubmit="return confirm('Stop this visit and complete the ticket?');">
                                @csrf
                                <input type="hidden" name="_from_app" value="1">
                                <input type="hidden" name="complete_ticket" value="1">
                                <button type="submit" class="btn btn-sm btn-danger py-0 px-2 w-100">Complete</button>
                            </form>
                        @elseif(! $isAdmin && ! $isRunning)
                            <a href="{{ route('worklog.edit', $task->id) }}" class="btn btn-sm btn-outline-primary py-0 px-2">Edit</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection

@push('scripts')
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
@endpush
