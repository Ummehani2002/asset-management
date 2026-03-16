@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h2 class="mb-1"><i class="bi bi-pencil-square me-2"></i>Edit Asset</h2>
            <p class="mb-0 text-muted">Update PO number and commercial details for this asset.</p>
        </div>
        <a href="{{ route('assets.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Asset Master
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="master-form-card">
        <form action="{{ route('assets.update', $asset->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Asset ID</label>
                    <input type="text" class="form-control" value="{{ $asset->asset_id }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <input type="text" class="form-control" value="{{ $asset->category->category_name ?? 'N/A' }}" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Serial Number</label>
                    <input type="text" class="form-control" value="{{ $asset->serial_number ?? 'N/A' }}" readonly>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">PO Number</label>
                    <input type="text" name="po_number" class="form-control" value="{{ old('po_number', $asset->po_number) }}" placeholder="Enter PO number">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vendor Name</label>
                    <input type="text" name="vendor_name" class="form-control" value="{{ old('vendor_name', $asset->vendor_name) }}" placeholder="Enter vendor name">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Value</label>
                    <input type="number" step="0.01" name="value" class="form-control" value="{{ old('value', $asset->value) }}" placeholder="Enter value">
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Save Changes
                </button>
                <a href="{{ route('assets.index') }}" class="btn btn-secondary ms-2">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

