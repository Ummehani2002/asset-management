@extends('layouts.work-log-app')

@section('title', 'Work Log Form')
@section('page-title', 'Start Work')

@section('content')
@if(session('success'))
    <div class="alert alert-success py-2">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning py-2">{{ session('warning') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger py-2">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="form-card">
    @include('work_log_app._form', [
        'action' => route('time.store'),
        'employeeName' => $employeeName,
        'record' => null,
        'todayTotals' => $todayTotals ?? ['total_hours' => 0, 'job_count' => 0],
        'isAdmin' => $isAdmin ?? false,
        'runningLog' => $runningLog ?? null,
        'openTickets' => $openTickets ?? collect(),
        'continueTicket' => $continueTicket ?? null,
    ])
</div>

@if(!empty($todayJobs) && $todayJobs->isNotEmpty())
<div class="mt-4">
    <h6 class="fw-semibold mb-2">Today's jobs</h6>
    <div class="table-responsive">
        <table class="table table-sm table-bordered bg-white mb-0" style="font-size: 0.8rem;">
            <thead class="table-light">
                <tr>
                    <th>Ticket</th>
                    <th>Start</th>
                    <th>Time</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($todayJobs as $job)
                <tr>
                    <td>
                        <strong>{{ $job->ticket_number }}</strong>
                        <div class="text-muted">{{ \Illuminate\Support\Str::limit($job->task_description, 28) }}</div>
                    </td>
                    <td>{{ $job->start_time?->format('H:i') }}</td>
                    <td>
                        @if($job->isRunning())
                            <span class="text-warning fw-semibold" data-elapsed-start="{{ $job->start_time?->toIso8601String() }}">…</span>
                        @else
                            {{ \App\Models\TimeManagement::formatDuration($job->duration_hours) }}
                        @endif
                    </td>
                    <td>
                        @if($job->isRunning())
                            <form action="{{ route('time.stop', $job->id) }}" method="POST" class="mb-1">
                                @csrf
                                <input type="hidden" name="_from_app" value="1">
                                <input type="hidden" name="complete_ticket" value="0">
                                <button type="submit" class="btn btn-sm btn-warning w-100">Stop Visit</button>
                            </form>
                            <form action="{{ route('time.stop', $job->id) }}" method="POST" class="m-0"
                                  onsubmit="return confirm('Stop this visit and complete the ticket?');">
                                @csrf
                                <input type="hidden" name="_from_app" value="1">
                                <input type="hidden" name="complete_ticket" value="1">
                                <button type="submit" class="btn btn-sm btn-danger w-100">Complete</button>
                            </form>
                        @else
                            <span class="badge bg-success">Done</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
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
