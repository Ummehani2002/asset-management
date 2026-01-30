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
                    <p class="mb-0 opacity-90">Assignments, returns, and maintenance for this asset</p>
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
                        $status = $asset->status ?? 'available';
                        $statusLabel = $status === 'assigned' ? 'Assigned' : ($status === 'under_maintenance' ? 'Under Maintenance' : 'Available / Returned');
                        $statusClass = $status === 'assigned' ? 'info' : ($status === 'under_maintenance' ? 'warning' : 'success');
                    @endphp
                    <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Timeline: Assign → Return / Maintenance → Assign → … --}}
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-arrow-down-up me-2"></i>Transaction Timeline</h5>
        </div>
        <div class="card-body">
            @if($history->isEmpty())
                <p class="text-center text-muted py-4 mb-0">No transaction history found for this asset.</p>
            @else
                <div class="timeline-flow">
                    @foreach($history as $index => $txn)
                        @php
                            $step = $index + 1;
                            $type = $txn->transaction_type ?? '';
                            $isAssign = $type === 'assign';
                            $isReturn = $type === 'return';
                            $isMaintenance = $type === 'system_maintenance';
                            $eventDate = $txn->return_date ?? $txn->issue_date ?? $txn->receive_date ?? $txn->created_at;
                            $dateFormatted = $eventDate ? (is_object($eventDate) ? $eventDate->format('d-m-Y') : \Carbon\Carbon::parse($eventDate)->format('d-m-Y')) : 'N/A';
                            $badgeClass = $isAssign ? 'bg-primary' : ($isMaintenance ? 'bg-warning text-dark' : 'bg-secondary');
                            $borderClass = $isAssign ? 'border-primary' : ($isMaintenance ? 'border-warning' : 'border-secondary');
                        @endphp
                        <div class="d-flex align-items-start mb-4 {{ $loop->last ? '' : 'pb-3' }}">
                            <div class="flex-shrink-0 me-3">
                                <span class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold {{ $badgeClass }} text-white" style="width: 36px; height: 36px; font-size: 14px;">
                                    {{ $step }}
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div class="card border {{ $borderClass }}" style="border-left-width: 4px !important;">
                                    <div class="card-body py-3">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                            <span class="badge {{ $badgeClass }}">
                                                @if($isAssign)
                                                    Assigned
                                                @elseif($isMaintenance)
                                                    Maintenance
                                                @else
                                                    Returned
                                                @endif
                                            </span>
                                            <span class="text-muted small">{{ $dateFormatted }}</span>
                                        </div>

                                        @if($isAssign)
                                            <p class="mb-1">
                                                <strong>Issued to:</strong>
                                                {{ $txn->employee->name ?? $txn->employee->entity_name ?? $txn->assigned_to ?? 'N/A' }}
                                                @if($txn->employee && ($txn->employee->employee_id ?? null))
                                                    <span class="text-muted small">(ID: {{ $txn->employee->employee_id }})</span>
                                                @endif
                                            </p>
                                            <p class="mb-1 small">
                                                <strong>Issue date:</strong> {{ $txn->issue_date ? \Carbon\Carbon::parse($txn->issue_date)->format('d-m-Y') : 'N/A' }}
                                            </p>
                                            @if($txn->project_name)
                                                <p class="mb-1 small"><strong>Project:</strong> {{ $txn->project_name }}</p>
                                            @endif
                                            @if($txn->location)
                                                <p class="mb-0 small"><strong>Location:</strong> {{ $txn->location->location_name ?? 'N/A' }}</p>
                                            @endif

                                        @elseif($isReturn)
                                            <p class="mb-1">
                                                <strong>Returned on:</strong> {{ $txn->return_date ? \Carbon\Carbon::parse($txn->return_date)->format('d-m-Y') : 'N/A' }}
                                            </p>
                                            @if($txn->employee)
                                                <p class="mb-0 small text-muted">Returned from: {{ $txn->employee->name ?? $txn->employee->entity_name ?? 'N/A' }}</p>
                                            @endif

                                        @elseif($isMaintenance)
                                            <p class="mb-1">
                                                <strong>Sent for maintenance:</strong> {{ $txn->receive_date ? \Carbon\Carbon::parse($txn->receive_date)->format('d-m-Y') : 'N/A' }}
                                            </p>
                                            @if($txn->employee)
                                                <p class="mb-1 small text-muted">Previously assigned to: {{ $txn->employee->name ?? $txn->employee->entity_name ?? 'N/A' }}</p>
                                            @endif
                                            @if(!empty($txn->repair_type))
                                                <p class="mb-1 small"><strong>Repair type:</strong> {{ $txn->repair_type }}</p>
                                            @endif
                                            @if(!empty($txn->maintenance_notes))
                                                <p class="mb-1 small"><strong>Notes:</strong> {{ $txn->maintenance_notes }}</p>
                                            @endif
                                            @if(!empty($txn->delivery_date))
                                                <p class="mb-0 small"><strong>Returned from maintenance:</strong> {{ \Carbon\Carbon::parse($txn->delivery_date)->format('d-m-Y') }}</p>
                                            @else
                                                <p class="mb-0 small text-muted">(Not yet returned from maintenance)</p>
                                            @endif
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
