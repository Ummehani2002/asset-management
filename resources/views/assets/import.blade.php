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
                <strong>Entity-wise upload:</strong> To assign assets by entity, add an <strong>Entity</strong> (or <strong>Company</strong>) column with the entity name (e.g. TANSEEQ INVESTMENT LLC). Entity names must already exist in Entity Master. If a row has no Entity column or it’s empty, the <strong>Default Entity</strong> below is used.<br><br>
                <strong>Brand & Model:</strong> Category and Brand from your file must exist in <strong>Add Brand & Model</strong>. Model can be set later when editing the asset.<br><br>
                <strong>Required columns:</strong> Category, Brand, Serial Number (or <strong>SERVICE TAG</strong>), and either Purchase Date or Warranty Start. Optional: Entity, Warranty End, Purchase Date.<br>
                <strong>Column mapping (your Excel):</strong><br>
                <ul class="mb-0 mt-1">
                    <li><strong>SERVICE TAG</strong> → Serial number (unique).</li>
                    <li><strong>WARRANTY ST</strong> / <strong>WARRANTY STA</strong> → Warranty start date (DD.MM.YYYY).</li>
                    <li><strong>WAR</strong> / <strong>WARRANTY END</strong> → Warranty end date (use full date DD.MM.YYYY if possible).</li>
                    <li><strong>Entity</strong> / <strong>Company</strong> → Entity name for that row; or set Default Entity below for the whole file.</li>
                    <li><strong>CATEGORY</strong> → LAPTOP, DESKTOP, etc. <strong>BRAND</strong> → DELL, LENOVO, etc.</li>
                    <li><strong>PURCHASE DATE</strong> → Optional if Warranty Start is present.</li>
                </ul>
                <strong class="mt-2 d-block">File format:</strong> Save Excel as <strong>CSV UTF-8</strong> (File → Save As → CSV UTF-8). Rows with duplicate serial numbers or missing required fields are skipped.
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

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" value="1" id="temporary_printer_format" name="temporary_printer_format" {{ old('temporary_printer_format') ? 'checked' : '' }}>
                    <label class="form-check-label" for="temporary_printer_format">
                        Use temporary Printer sheet format for this upload only (ID -> Serial, EXP DATE -> Expiry, SITE prefix -> Entity).
                    </label>
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
