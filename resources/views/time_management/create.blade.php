@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header d-flex justify-content-between align-items-center">
        <div>
            <h2><i class="bi bi-plus-circle me-2"></i>New Work Log</h2>
           
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
                'action' => route('time.store'),
                'method' => 'POST',
                'ticketNumber' => $ticketNumber,
                'employeeName' => $employeeName,
                'record' => null,
                'todayTotals' => $todayTotals ?? ['total_hours' => 0, 'job_count' => 0],
                'isAdmin' => $isAdmin ?? false,
            ])
        </div>
    </div>
</div>
@endsection
