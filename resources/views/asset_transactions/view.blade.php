@extends('layouts.app')
@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-list-ul me-2"></i>View Asset Transactions</h2>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('asset-transactions.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Transactions
                </a>
            </div>
        </div>
    </div>

    <!-- Filter Dropdown -->
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filter Transactions</h5>
        <form method="GET" action="{{ route('asset-transactions.view') }}" id="filterForm">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">View Transactions</label>
                    <select name="filter" id="filter" class="form-control" onchange="document.getElementById('filterForm').submit();">
                        <option value="">-- Select Filter --</option>
                        <option value="assigned" {{ request('filter') == 'assigned' ? 'selected' : '' }}>Assigned Assets</option>
                        <option value="maintenance" {{ request('filter') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        <option value="return" {{ request('filter') == 'return' ? 'selected' : '' }}>Return</option>
                        <option value="available" {{ request('filter') == 'available' ? 'selected' : '' }}>Available</option>
                    </select>
                    <small class="text-muted">Select a filter to view specific transaction types</small>
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <a href="{{ route('asset-transactions.view') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Clear Filter
                    </a>
                </div>
            </div>
        </form>
    </div>

    @if($transactions->count() > 0)
        <div class="master-table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 style="color: white; margin: 0;">
                    <i class="bi bi-list-ul me-2"></i>Transactions 
                    @if($currentFilter)
                        - {{ ucfirst($currentFilter) }}
                    @endif
                    ({{ $transactions->total() }})
                </h5>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download"></i> Download
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="downloadDropdown">
                        <li><a class="dropdown-item" href="{{ route('asset-transactions.export', array_merge(request()->only(['filter']), ['format' => 'pdf'])) }}">
                            <i class="bi bi-file-pdf me-2"></i>PDF
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('asset-transactions.export', array_merge(request()->only(['filter']), ['format' => 'csv'])) }}">
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV
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
                                <th>Entity</th>
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
                                    <td>{{ ucwords(trim(optional($t->location)->location_entity ?? $t->employee->entity_name ?? '')) ?: 'N/A' }}</td>
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
            <h4>No Transactions Found</h4>
            <p class="mb-3">
                @if($currentFilter)
                    No transactions found for "{{ ucfirst($currentFilter) }}". Please select a different filter.
                @else
                    Please select a filter from the dropdown above to view transactions.
                @endif
            </p>
        </div>
    @endif
</div>
@endsection

