@extends('layouts.work-log-app')

@section('title', 'Edit Work Log')
@section('page-title', 'Edit Work Log')

@section('content')
@if($errors->any())
    <div class="alert alert-danger py-2">
        <ul class="mb-0 ps-3">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="form-card">
    @include('work_log_app._form', [
        'action' => route('time.update', $record->id),
        'ticketNumber' => $record->ticket_number,
        'employeeName' => $record->employee_name ?? Auth::user()->name,
        'record' => $record,
        'todayTotals' => $todayTotals ?? ['total_hours' => 0, 'job_count' => 0],
        'isAdmin' => $isAdmin ?? false,
    ])
</div>
@endsection
