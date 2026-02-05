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
                <div class="col-md-3 col-6 mb-2">
                    <strong>Asset ID</strong><br>
                    <span class="text-primary">{{ $asset->asset_id ?? 'N/A' }}</span>
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <strong>Serial Number</strong><br>
                    {{ $asset->serial_number ?? 'N/A' }}
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <strong>Category</strong><br>
                    {{ optional($asset->category)->category_name ?? 'N/A' }}
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <strong>Brand</strong><br>
                    {{ optional($asset->brand)->name ?? 'N/A' }}
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <strong>Entity</strong><br>
                    @php
                        $latestAssign = $assignReturnHistory->where('transaction_type', 'assign')->last();
                        $currentEntity = $latestAssign ? ($latestAssign->entity_name ?? '-') : '-';
                    @endphp
                    {{ $currentEntity }}
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <strong>Asset Manager</strong><br>
                    @php
                        $currentAssetManager = $latestAssign ? ($latestAssign->asset_manager_name ?? '-') : '-';
                    @endphp
                    {{ $currentAssetManager }}
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <strong>Current Location</strong><br>
                    @php
                        $currentLocation = ($latestAssign && $latestAssign->location) 
                            ? $latestAssign->location->location_name 
                            : (optional($asset->location)->location_name ?? '-');
                    @endphp
                    {{ $currentLocation }}
                </div>
                <div class="col-md-3 col-6 mb-2">
                    <strong>Current Status</strong><br>
                    @php
                        $latest = $assignReturnHistory->last();
                        $statusLabel = $latest ? ($latest->transaction_type === 'return' ? 'Returned / Available' : 'Assigned') : ($asset->status ? ucfirst(str_replace('_', ' ', $asset->status)) : 'No transactions yet');
                        $statusClass = $latest && $latest->transaction_type === 'return' ? 'success' : 'info';
                    @endphp
                    <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Assign & Return - Tabular form --}}
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-arrow-down-up me-2"></i>Assign & Return History</h5>
        </div>
        <div class="card-body p-0">
            @if($assignReturnHistory->isEmpty())
                <p class="text-center text-muted py-4 mb-0">No assign or return history for this asset.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Entity</th>
                                <th>Asset Manager</th>
                                <th>Project</th>
                                <th>Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assignReturnHistory as $index => $txn)
                                @php
                                    $isAssign = ($txn->transaction_type ?? '') === 'assign';
                                    $eventDate = $txn->return_date ?? $txn->issue_date;
                                    $dateFormatted = $eventDate ? (is_object($eventDate) ? $eventDate->format('d-m-Y') : \Carbon\Carbon::parse($eventDate)->format('d-m-Y')) : 'N/A';
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><span class="badge {{ $isAssign ? 'bg-primary' : 'bg-secondary' }}">{{ $isAssign ? 'Assigned' : 'Returned' }}</span></td>
                                    <td>{{ $dateFormatted }}</td>
                                    <td>{{ $txn->employee->name ?? $txn->assigned_to ?? '-' }}</td>
                                    <td>{{ $txn->entity_name ?? '-' }}</td>
                                    <td>{{ $txn->asset_manager_name ?? '-' }}</td>
                                    <td>{{ $txn->project_name ?? '-' }}</td>
                                    <td>{{ $txn->location->location_name ?? optional($asset->location)->location_name ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- System Maintenance - Tabular form with remarks --}}
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="bi bi-wrench me-2"></i>System Maintenance History</h5>
        </div>
        <div class="card-body p-0">
            @if($maintenanceHistory->isEmpty())
                <p class="text-center text-muted py-4 mb-0">No maintenance history for this asset.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Receive Date</th>
                                <th>Delivery Date</th>
                                <th>Repair Type</th>
                                <th>Remarks</th>
                                <th>Maintenance Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($maintenanceHistory as $index => $txn)
                                @php
                                    $receiveFormatted = $txn->receive_date ? (is_object($txn->receive_date) ? $txn->receive_date->format('d-m-Y') : \Carbon\Carbon::parse($txn->receive_date)->format('d-m-Y')) : '-';
                                    $deliveryFormatted = $txn->delivery_date ? (is_object($txn->delivery_date) ? $txn->delivery_date->format('d-m-Y') : \Carbon\Carbon::parse($txn->delivery_date)->format('d-m-Y')) : '-';
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $receiveFormatted }}</td>
                                    <td>{{ $deliveryFormatted }}</td>
                                    <td>{{ $txn->repair_type ?? '-' }}</td>
                                    <td>{{ $txn->remarks ?? '-' }}</td>
                                    <td>{{ $txn->maintenance_notes ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
