@extends('layouts.app')

@section('content')
<div class="container">
    {{-- Asset summary --}}
    <div class="card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #1F2A44 0%, #2C3E66 100%); color: #fff;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <h2 class="h4 mb-1">
                        <i class="bi bi-clock-history me-2"></i>Asset History
                    </h2>
                    <p class="mb-0 opacity-90">Complete flow from assign to return for this asset</p>
                </div>
                <a href="{{ route('assets.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Back to Assets
                </a>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Asset Details</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Asset ID</strong><br>
                    <span class="text-primary">{{ $asset->asset_id ?? 'N/A' }}</span>
                </div>
                <div class="col-md-2">
                    <strong>Serial Number</strong><br>
                    {{ $asset->serial_number ?? 'N/A' }}
                </div>
                <div class="col-md-2">
                    <strong>Category</strong><br>
                    {{ optional($asset->category)->category_name ?? 'N/A' }}
                </div>
                <div class="col-md-2">
                    <strong>Brand</strong><br>
                    {{ optional($asset->brand)->name ?? 'N/A' }}
                </div>
                <div class="col-md-3">
                    <strong>Current Status</strong><br>
                    @php
                        $latest = $history->last();
                        $statusLabel = $latest ? ($latest->transaction_type === 'return' ? 'Returned / Available' : 'Assigned') : 'No transactions yet';
                        $statusClass = $latest && $latest->transaction_type === 'return' ? 'success' : 'info';
                    @endphp
                    <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Transaction flow: Assign → Return → Assign → … --}}
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-arrow-down-up me-2"></i>Transaction Flow (Assign → Return)</h5>
        </div>
        <div class="card-body">
            @if($history->isEmpty())
                <p class="text-center text-muted py-4 mb-0">No transaction history found for this asset.</p>
            @else
                <div class="timeline-flow">
                    @foreach($history as $index => $txn)
                        @php
                            $step = $index + 1;
                            $isAssign = ($txn->transaction_type ?? '') === 'assign';
                            $eventDate = $txn->return_date ?? $txn->issue_date;
                            $dateFormatted = $eventDate ? (is_object($eventDate) ? $eventDate->format('d-m-Y') : \Carbon\Carbon::parse($eventDate)->format('d-m-Y')) : 'N/A';
                        @endphp
                        <div class="d-flex align-items-start mb-4 {{ $loop->last ? '' : 'pb-3' }}">
                            <div class="flex-shrink-0 me-3">
                                <span class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold {{ $isAssign ? 'bg-primary text-white' : 'bg-secondary text-white' }}" style="width: 36px; height: 36px; font-size: 14px;">
                                    {{ $step }}
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="card border {{ $isAssign ? 'border-primary' : 'border-secondary' }}" style="border-left-width: 4px !important;">
                                    <div class="card-body py-3">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                            <span class="badge {{ $isAssign ? 'bg-primary' : 'bg-secondary' }}">
                                                {{ $isAssign ? 'Assigned' : 'Returned' }}
                                            </span>
                                            <span class="text-muted small">{{ $dateFormatted }}</span>
                                        </div>
                                        @if($isAssign)
                                            <p class="mb-1">
                                                <strong>Assigned to:</strong>
                                                {{ $txn->employee->name ?? $txn->assigned_to ?? 'N/A' }}
                                                @if($txn->employee && $txn->employee->email)
                                                    <span class="text-muted small">({{ $txn->employee->email }})</span>
                                                @endif
                                            </p>
                                            @if($txn->project_name)
                                                <p class="mb-1 small"><strong>Project:</strong> {{ $txn->project_name }}</p>
                                            @endif
                                            @if($txn->location)
                                                <p class="mb-0 small"><strong>Location:</strong> {{ $txn->location->location_name }}</p>
                                            @endif
                                        @else
                                            <p class="mb-0">
                                                <strong>Returned on</strong> {{ $dateFormatted }}.
                                                @if($txn->employee)
                                                    <span class="text-muted">(returned from {{ $txn->employee->name }})</span>
                                                @endif
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @if(!$loop->last)
                                <div class="flex-shrink-0 mx-2 d-none d-md-block" style="width: 24px; align-self: stretch;">
                                    <div class="bg-light rounded d-block mx-auto" style="width: 2px; height: 100%; min-height: 24px;"></div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="mt-3">
        <a href="{{ route('assets.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Assets
        </a>
    </div>
</div>
@endsection
