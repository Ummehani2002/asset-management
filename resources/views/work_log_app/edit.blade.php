@extends('layouts.work-log-app')

@section('title', 'Edit Work Log')
@section('page-title', 'Edit Work Log')

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
        'action' => route('time.update', $record->id),
        'employeeName' => $record->employee_name ?? Auth::user()->name,
        'record' => $record,
        'todayTotals' => $todayTotals ?? ['total_hours' => 0, 'job_count' => 0],
        'isAdmin' => $isAdmin ?? false,
        'openTickets' => $openTickets ?? collect(),
    ])
</div>

@if(request('status') === 'completed')
<div class="alert alert-info py-2 mt-3 mb-0">
    <small>Status is set to <strong>Completed</strong>. Tap update to save.</small>
</div>
@endif
@endsection
