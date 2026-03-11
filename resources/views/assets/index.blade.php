@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <!-- Page Header -->
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h2><i class="bi bi-pc-display me-2"></i>Asset Master</h2>
            <p class="mb-0">View and manage all assets in the system</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('assets.import.form') }}" class="btn btn-outline-primary">
                <i class="bi bi-upload me-1"></i>Import Assets
            </a>
            <a href="{{ route('assets.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Add Asset
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Filter Form --}}
    <div class="master-form-card">
        <form method="GET" action="{{ route('assets.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Category</label>
                    <select name="category_id" id="category_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- All Categories --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (isset($categoryId) && $categoryId == $category->id) ? 'selected' : '' }}>
                                {{ $category->category_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if($entities->isNotEmpty())
                <div class="col-md-4">
                    <label for="entity" class="form-label">Entity</label>
                    <select name="entity" id="entity" class="form-control" onchange="this.form.submit()">
                        <option value="">-- All Entities --</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}" {{ (isset($selectedEntityId) && $selectedEntityId == $entity->id) ? 'selected' : '' }}>
                                {{ $entity->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('assets.index') }}" class="btn btn-outline-secondary">Clear</a>
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
                                <th>Entity</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th>Purchase Date</th>
                                <th>Warranty Start</th>
                                <th>Expiry Date</th>
                                <th>PO Number</th>
                                <th>Vendor Name</th>
                                <th>Value</th>
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
                                    <td>{{ optional($asset->entity)->name ?? '-' }}</td>
                                    <td>{{ optional($asset->category)->category_name ?? 'N/A' }}</td>
                                    <td>{{ optional($asset->brand)->name ?? 'N/A' }}</td>
                                    <td>{{ $asset->purchase_date ?? 'N/A' }}</td>
                                    <td>{{ $asset->warranty_start ?? 'N/A' }}</td>
                                    <td>{{ $asset->expiry_date ?? 'N/A' }}</td>
                                    <td>{{ $asset->po_number ?? 'N/A' }}</td>
                                    <td>{{ $asset->vendor_name ?? '-' }}</td>
                                    <td>{{ $asset->value ? number_format($asset->value, 2) : '-' }}</td>
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
                                            @php
                                                $disk = config('filesystems.default', 'public');
                                                $hasS3 = !empty(config('filesystems.disks.s3.key')) && !empty(config('filesystems.disks.s3.bucket'));
                                                if (($disk === 's3' || $disk === 'object-storage') && $hasS3) {
                                                    $invoiceLink = Storage::disk($disk)->temporaryUrl($asset->invoice_path, now()->addMinutes(60));
                                                } else {
                                                    $invoiceLink = asset('storage/' . $asset->invoice_path);
                                                }
                                            @endphp
                                            <a href="{{ $invoiceLink }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-file-earmark-pdf"></i> View
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-center text-muted py-4">No assets found.</td>
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
