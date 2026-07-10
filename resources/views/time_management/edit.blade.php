@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-pencil-square me-2"></i>Edit Work Log</h2>
            <p class="text-muted mb-0">Ticket: {{ $record->ticket_number }}</p>
        </div>
        <a href="{{ route('time.index') }}" class="btn btn-secondary"><i class="bi bi-arrow-left me-2"></i>Back</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="master-table-card">
        <div class="card-header">
            <h5 style="color: white; margin: 0;"><i class="bi bi-card-checklist me-2"></i>Work Log Form</h5>
        </div>
        <div class="card-body">
            @include('time_management._form', [
                'action' => route('time.update', $record->id),
                'method' => 'POST',
                'employeeName' => $record->employee_name,
                'record' => $record,
                'todayTotals' => $todayTotals ?? ['total_hours' => 0, 'job_count' => 0],
                'isAdmin' => $isAdmin ?? false,
                'openTickets' => $openTickets ?? collect(),
            ])
        </div>
    </div>
</div>
@endsection
