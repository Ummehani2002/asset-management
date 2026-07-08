@extends('layouts.work-log-app')

@section('title', 'Work Log Form')
@section('page-title', 'Work Log Form')

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
        'action' => route('time.store'),
        'ticketNumber' => $ticketNumber,
        'employeeName' => $employeeName,
        'record' => null,
    ])
</div>
@endsection
