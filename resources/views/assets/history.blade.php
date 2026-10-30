@extends('layouts.app')

@section('content')
<div class="container">
    @php
        $fromMaintenance = request('from') === 'maintenance';
        $backUrl = $fromMaintenance ? route('asset-transactions.maintenance') : route('assets.index');
        $backLabel = $fromMaintenance ? 'Back to System Maintenance' : 'Back to Assets';
    @endphp
    <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #1F2A44 0%, #2C3E66 100%); color: #fff;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h2 class="h4 mb-1">
                        <i class="bi bi-clock-history me-2"></i>Asset History
                    </h2>
                </div>
                <a href="{{ $backUrl }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>{{ $backLabel }}
                </a>
            </div>
        </div>
    </div>

    @include('partials.asset_history_content')

    <div class="mt-3">
        <a href="{{ $backUrl }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>{{ $backLabel }}
        </a>
    </div>
</div>
@endsection
