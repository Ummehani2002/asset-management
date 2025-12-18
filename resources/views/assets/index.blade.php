@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="bi bi-pc-display me-2"></i>Asset Master</h2>
        <p>View and manage all assets in the system</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Filter Form --}}
    <div class="master-form-card">
        <form method="GET" action="{{ route('assets.filter') }}">
            <div class="row">
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Filter by Category:</label>
                    <select name="category_id" id="category_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- All Categories --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (isset($categoryId) && $categoryId == $category->id) ? 'selected' : '' }}>
                                {{ $category->category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </form>
    </div>

    @if($assets->isEmpty())
        <div class="alert alert-info">
            No assets found.
        </div>
    @else
        <div class="master-table-card">
            <div class="card-header">
                <h5 style="color: white; margin: 0;"><i class="bi bi-list-ul me-2"></i>All Assets</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Asset ID</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th>Purchase Date</th>
                                <th>Warranty Start</th>
                                <th>Expiry Date</th>
                                <th>PO Number</th>
                                <th>Serial Number</th>
                                <th>Features</th>
                                <th>Invoice</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assets as $asset)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $asset->asset_id }}<br>
                                        <a href="{{ route('asset.history', $asset->id) }}" class="btn btn-sm btn-outline-info mt-1" style="font-size: 11px;">
                                            <i class="bi bi-clock-history"></i> History
                                        </a>
                                    </td>
                                    <td>{{ optional($asset->category)->category_name ?? 'N/A' }}</td>
                                    <td>{{ optional($asset->brand)->name ?? 'N/A' }}</td>
                                    <td>{{ $asset->purchase_date ?? 'N/A' }}</td>
                                    <td>{{ $asset->warranty_start ?? 'N/A' }}</td>
                                    <td>{{ $asset->expiry_date ?? 'N/A' }}</td>
                                    <td>{{ $asset->po_number ?? 'N/A' }}</td>
                                    <td>{{ $asset->serial_number ?? 'N/A' }}</td>
                                    <td>
                                        @if($asset->featureValues->count() > 0)
                                            <ul class="mb-0" style="font-size: 12px;">
                                                @foreach($asset->featureValues as $fv)
                                                    <li><strong>{{ $fv->feature->feature_name ?? 'N/A' }}</strong>: {{ $fv->value }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        @if($asset->invoice_path)
                                            <a href="{{ asset('storage/' . $asset->invoice_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-file-earmark-pdf"></i> View
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">No assets found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
