@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Assets in Category: {{ $category->category_name }}</h2>
        <div class="d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-sm btn-success dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download me-1"></i>Download
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="downloadDropdown">
                    <li><a class="dropdown-item" href="{{ route('assets.byCategory.export', ['id' => $category->id, 'format' => 'pdf']) }}">
                        <i class="bi bi-file-earmark-pdf me-2"></i>PDF
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('assets.byCategory.export', ['id' => $category->id, 'format' => 'csv']) }}">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV
                    </a></li>
                </ul>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    @if($assets->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>No assets found in this category.
        </div>
    @else
        <div class="master-table-card">
            <div class="card-header">
                <h5 style="color: white; margin: 0;">
                    <i class="bi bi-list-ul me-2"></i>Total Assets: {{ $assets->count() }}
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Asset ID</th>
                                <th>Brand</th>
                                <th>Purchase Date</th>
                                <th>Warranty Start</th>
                                <th>Expiry Date</th>
                                <th>PO Number</th>
                                <th>Serial Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($assets as $index => $asset)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $asset->asset_id }}</td>
                                    <td>{{ $asset->brand->name ?? 'N/A' }}</td>
                                    <td>{{ $asset->purchase_date ?? 'N/A' }}</td>
                                    <td>{{ $asset->warranty_start ?? 'N/A' }}</td>
                                    <td>{{ $asset->expiry_date ?? 'N/A' }}</td>
                                    <td>{{ $asset->po_number ?? 'N/A' }}</td>
                                    <td>{{ $asset->serial_number ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
