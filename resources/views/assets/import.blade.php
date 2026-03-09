@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-upload me-2"></i>Import Assets</h2>
        <p class="text-muted mb-0">Import assets by category, entity, serial number, brand, and warranty dates. You can map assets to employees later.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">Import assets from CSV</h5>

            <div class="alert alert-info mb-4">
                <strong>Brand & Model</strong> (from Brand Management): only <strong>Category</strong> and <strong>Brand</strong> are used from your file; they must exist under <strong>Add Brand & Model</strong> / <strong>Model Values</strong>. Model can be set later when editing the asset.<br>
                <strong>Asset details</strong> (other columns): Entity, Serial Number, Purchase Date, Warranty — these are stored in Asset Master.<br><br>
                <strong>Required columns:</strong> Category, Brand, Serial Number (or <strong>SERVICE TAG</strong>), Purchase Date, Warranty Start. Optional: Entity, Warranty End.<br>
                <strong>Column mapping:</strong><br>
                <ul class="mb-0 mt-1">
                    <li><strong>Category</strong> / <strong>CATEGORY</strong> → Asset category (e.g. LAPTOP, DESKTOP). Must exist in Manage Categories.</li>
                    <li><strong>Brand</strong> / <strong>BRAND</strong> → Brand (must exist for the category in Brand Management).</li>
                    <li><strong>Entity</strong> / <strong>Entity Name</strong> / <strong>Company</strong> → Entity; use Default Entity below if missing.</li>
                    <li><strong>Serial Number</strong> / <strong>SERVICE TAG</strong> → Serial number (unique).</li>
                    <li><strong>Purchase Date</strong> / <strong>PURCHASE DATE</strong> → Purchase date (DD.MM.YYYY or Y-m-d).</li>
                    <li><strong>Warranty Start</strong> / <strong>WARRANTY STA</strong> → Warranty start (defaults to purchase date if empty).</li>
                    <li><strong>Warranty End</strong> / <strong>WARRANTY END</strong> → Warranty expiry date.</li>
                </ul>
                <strong class="mt-2 d-block">File format:</strong> Save your Excel as <strong>CSV UTF-8</strong> (File → Save As → CSV UTF-8 Comma delimited). Rows with duplicate serial numbers or missing required fields are skipped.
            </div>

            <form action="{{ route('assets.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Default Entity</label>
                    <select name="default_entity" class="form-control">
                        <option value="">-- Use Entity from CSV if available --</option>
                        @foreach($entities ?? [] as $ent)
                            <option value="{{ $ent->name }}" {{ old('default_entity') == $ent->name ? 'selected' : '' }}>{{ $ent->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Used when a row has no Entity column or empty Entity value</small>
                </div>

                <div class="mb-4">
                    <label class="form-label">File (CSV) <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                    <small class="text-muted">Upload a CSV file. In Excel: File → Save As → CSV UTF-8.</small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-2"></i>Import Assets
                    </button>
                    <a href="{{ route('assets.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
