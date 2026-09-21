@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2 class="mb-0"><i class="bi bi-boxes me-2"></i>Asset Stock</h2>
        <p class="text-muted mb-0">Enter quantities when items arrive. Assigned, maintenance, and scrap items are taken off stock automatically.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">{{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="border rounded p-3 bg-light h-100">
                <div class="small text-muted">Received</div>
                <div class="fs-4 fw-bold">{{ $totals['received'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="border rounded p-3 bg-light h-100">
                <div class="small text-muted">Assigned</div>
                <div class="fs-4 fw-bold text-primary">{{ $totals['assigned'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="border rounded p-3 bg-light h-100">
                <div class="small text-muted">Scrap</div>
                <div class="fs-4 fw-bold text-danger">{{ $totals['scrap'] ?? 0 }}</div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="border rounded p-3 bg-light h-100">
                <div class="small text-muted">In Stock</div>
                <div class="fs-4 fw-bold text-success">{{ $totals['in_stock'] ?? 0 }}</div>
            </div>
        </div>
    </div>

    <div class="master-form-card">
        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">Receive Items</h5>
        <form method="POST" action="{{ route('asset-stock.store') }}">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Asset Type <span class="text-danger">*</span></label>
                    <select name="asset_category_id" class="form-control" required>
                        <option value="">-- Select --</option>
                        @foreach($categories ?? $rows->pluck('category') as $category)
                            <option value="{{ $category->id }}" {{ old('asset_category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->category_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('asset_category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" min="1" class="form-control" value="{{ old('quantity', 1) }}" required>
                    @error('quantity')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <label class="form-label">Received Date <span class="text-danger">*</span></label>
                    <input type="date" name="received_date" class="form-control" value="{{ old('received_date', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Remarks</label>
                    <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}" placeholder="PO / invoice note">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-success w-100">Add</button>
                </div>
            </div>
        </form>
    </div>

    <div class="master-table-card mb-4">
        <div class="card-header">
            <h5 style="color: white; margin: 0;"><i class="bi bi-list-ul me-2"></i>Stock by Asset Type</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Asset Type</th>
                            <th>Received</th>
                            <th>Assigned</th>
                            <th>Maintenance</th>
                            <th>Scrap</th>
                            <th>In Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td><strong>{{ $row['category']->category_name }}</strong></td>
                                <td>{{ $row['received'] }}</td>
                                <td>{{ $row['assigned'] }}</td>
                                <td>{{ $row['maintenance'] }}</td>
                                <td>{{ $row['scrap'] }}</td>
                                <td class="fw-bold {{ $row['in_stock'] < 0 ? 'text-danger' : 'text-success' }}">{{ $row['in_stock'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No asset types found. Add categories first.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="master-table-card">
        <div class="card-header">
            <h5 style="color: white; margin: 0;">Recent Receipts</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Asset Type</th>
                            <th>Quantity</th>
                            <th>Remarks</th>
                            <th>Added By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($receipts as $receipt)
                            <tr>
                                <td>{{ optional($receipt->received_date)->format('Y-m-d') }}</td>
                                <td>{{ $receipt->category->category_name ?? '-' }}</td>
                                <td>{{ $receipt->quantity }}</td>
                                <td>{{ $receipt->remarks ?: '-' }}</td>
                                <td>{{ $receipt->user->name ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No receipts yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
