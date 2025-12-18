@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-tools me-2"></i>Preventive Maintenance Records</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="master-table-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 style="color: white; margin: 0;">
                <i class="bi bi-list-ul me-2"></i>Maintenance Records ({{ $maintenances->total() }})
            </h5>
            <a href="{{ route('preventive-maintenance.create') }}" class="btn btn-sm btn-light">
                <i class="bi bi-plus-circle me-2"></i>Add New Maintenance
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Asset ID</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Serial Number</th>
                            <th>Location</th>
                            <th>Maintenance Date</th>
                            <th>Next Maintenance Date</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($maintenances as $key => $maintenance)
                        @php
                            // Get location from asset's latest transaction
                            $latestTransaction = \App\Models\AssetTransaction::where('asset_id', $maintenance->asset_id)
                                ->whereNotNull('location_id')
                                ->with('location')
                                ->latest('issue_date')
                                ->first();
                            $location = $latestTransaction && $latestTransaction->location 
                                ? $latestTransaction->location->location_name 
                                : 'N/A';
                        @endphp
                        <tr>
                            <td>{{ $maintenances->firstItem() + $key }}</td>
                            <td>{{ $maintenance->asset->asset_id ?? 'N/A' }}</td>
                            <td>{{ $maintenance->asset->assetCategory->category_name ?? 'N/A' }}</td>
                            <td>{{ $maintenance->asset->brand->name ?? 'N/A' }}</td>
                            <td>{{ $maintenance->asset->serial_number ?? 'N/A' }}</td>
                            <td>{{ $location }}</td>
                            <td>{{ $maintenance->maintenance_date ? $maintenance->maintenance_date->format('Y-m-d') : 'N/A' }}</td>
                            <td>{{ $maintenance->next_maintenance_date ? $maintenance->next_maintenance_date->format('Y-m-d') : 'N/A' }}</td>
                            <td>{{ $maintenance->maintenance_description ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                No preventive maintenance records found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $maintenances->links() }}
        </div>
    </div>
</div>
@endsection

