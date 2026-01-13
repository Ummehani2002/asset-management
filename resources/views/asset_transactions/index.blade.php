@extends('layouts.app')
@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-arrow-left-right me-2"></i>Asset Transactions</h2>
              
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('asset-transactions.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Assign/Return
                </a>
                <a href="{{ route('asset-transactions.maintenance') }}" class="btn btn-info">
                    <i class="bi bi-tools me-2"></i>System Maintenance
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search and Filter Form -->
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-search me-2"></i>Advanced Search & Filter</h5>
        <form method="GET" action="{{ route('asset-transactions.index') }}" id="searchForm">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by asset, employee, project..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Asset Status</label>
                    <select name="asset_status" class="form-control">
                        <option value="">All Statuses</option>
                        <option value="assigned" {{ request('asset_status') == 'assigned' || !request()->hasAny(['search', 'asset_status', 'transaction_type']) ? 'selected' : '' }}>Assigned</option>
                        <option value="available" {{ request('asset_status') == 'available' ? 'selected' : '' }}>Available</option>
                        <option value="under_maintenance" {{ request('asset_status') == 'under_maintenance' ? 'selected' : '' }}>Under Maintenance</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Transaction Type</label>
                    <select name="transaction_type" class="form-control">
                        <option value="">All Types</option>
                        <option value="assign" {{ request('transaction_type') == 'assign' ? 'selected' : '' }}>Assign</option>
                        <option value="return" {{ request('transaction_type') == 'return' ? 'selected' : '' }}>Return</option>
                        <option value="system_maintenance" {{ request('transaction_type') == 'system_maintenance' ? 'selected' : '' }}>Maintenance</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i>Search
                    </button>
                    <a href="{{ route('asset-transactions.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    @if($transactions->count() > 0)
        <div class="master-table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 style="color: white; margin: 0;">
                    <i class="bi bi-list-ul me-2"></i>Transactions ({{ $transactions->total() }})
                </h5>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download"></i> Download
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="downloadDropdown">
                        <li><h6 class="dropdown-header">Filtered Results</h6></li>
                        <li><a class="dropdown-item" href="{{ route('asset-transactions.export', array_merge(request()->only(['search', 'asset_status', 'transaction_type', 'filter']), ['format' => 'pdf'])) }}">
                            <i class="bi bi-file-pdf me-2"></i>PDF (Filtered)
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('asset-transactions.export', array_merge(request()->only(['search', 'asset_status', 'transaction_type', 'filter']), ['format' => 'csv'])) }}">
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV (Filtered)
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><h6 class="dropdown-header">All Transactions</h6></li>
                        <li><a class="dropdown-item" href="{{ route('asset-transactions.export', ['format' => 'pdf', 'download_all' => true]) }}">
                            <i class="bi bi-file-pdf me-2"></i>PDF (All)
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('asset-transactions.export', ['format' => 'csv', 'download_all' => true]) }}">
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV (All)
                        </a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Transaction ID</th>
                                <th>Asset</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Location</th>
                                <th>Date</th>
                                <th>Image</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $index => $t)
                                <tr>
                                    <td>{{ $index + 1 + ($transactions->currentPage() - 1) * $transactions->perPage() }}</td>
                                    <td>{{ $t->id }}</td>
                                    <td>
                                        <strong>{{ $t->asset->asset_id ?? 'N/A' }}</strong><br>
                                        <small class="text-muted">{{ $t->asset->serial_number ?? 'N/A' }}</small>
                                    </td>
                                    <td>{{ $t->asset->assetCategory->category_name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge 
                                            @if($t->transaction_type == 'assign') bg-success
                                            @elseif($t->transaction_type == 'return') bg-warning
                                            @else bg-info
                                            @endif">
                                            {{ ucfirst(str_replace('_', ' ', $t->transaction_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            @if($t->asset->status == 'assigned') bg-primary
                                            @elseif($t->asset->status == 'available') bg-success
                                            @elseif($t->asset->status == 'under_maintenance') bg-warning
                                            @else bg-secondary
                                            @endif">
                                            {{ ucfirst(str_replace('_', ' ', $t->asset->status ?? 'N/A')) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($t->transaction_type == 'system_maintenance')
                                            {{ $t->employee->name ?? 'N/A' }} <br>
                                            <small class="text-muted">(Maintenance)</small>
                                        @else
                                            {{ $t->employee->name ?? $t->project_name ?? 'N/A' }}
                                        @endif
                                    </td>
                                    <td>{{ $t->location->location_name ?? 'N/A' }}</td>
                                    <td>
                                        @if($t->transaction_type == 'system_maintenance')
                                            <div><strong>Receive:</strong> {{ $t->receive_date ?? 'N/A' }}</div>
                                            @if($t->delivery_date)
                                                <div><strong>Delivery:</strong> {{ $t->delivery_date }}</div>
                                            @endif
                                        @else
                                            {{ $t->issue_date ?? $t->return_date ?? 'N/A' }}
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $imagePath = null;
                                            if ($t->transaction_type == 'assign' && $t->assign_image) {
                                                $imagePath = $t->assign_image;
                                            } elseif ($t->transaction_type == 'return' && $t->return_image) {
                                                $imagePath = $t->return_image;
                                            } elseif ($t->transaction_type == 'system_maintenance' && $t->maintenance_image) {
                                                $imagePath = $t->maintenance_image;
                                            }
                                        @endphp
                                        @if($imagePath)
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#imageModal{{ $t->id }}">
                                                <i class="bi bi-image me-1"></i>View
                                            </button>
                                        @else
                                            <span class="text-muted">No image</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('asset-transactions.edit', $t->id) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('asset-transactions.destroy', $t->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this transaction?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" type="submit">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                
                                @if($imagePath)
                                <!-- Image Modal -->
                                <div class="modal fade" id="imageModal{{ $t->id }}" tabindex="-1" aria-labelledby="imageModalLabel{{ $t->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="imageModalLabel{{ $t->id }}">
                                                    Asset Image - {{ ucfirst(str_replace('_', ' ', $t->transaction_type)) }}
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body text-center">
                                                <img src="{{ asset('storage/' . $imagePath) }}" 
                                                     alt="Transaction Image" 
                                                     class="img-fluid" 
                                                     style="max-height: 70vh;">
                                                <p class="mt-2 text-muted">
                                                    <small>Transaction ID: {{ $t->id }} | Date: {{ $t->issue_date ?? $t->return_date ?? $t->receive_date ?? 'N/A' }}</small>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @if($transactions->hasPages())
                <div class="card-footer" style="background: #f8f9fa; padding: 12px 20px; border-top: 1px solid #dee2e6;">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    @else
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle display-4 d-block mb-3"></i>
            <h4>No Results Found</h4>
            <p class="mb-3">No transactions match your search criteria. Try adjusting your filters.</p>
        </div>
    @endif
</div>
@endsection
