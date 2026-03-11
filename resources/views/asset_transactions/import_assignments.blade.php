@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-link-45deg me-2"></i>Import Asset Assignments</h2>
        <p class="text-muted mb-0">Bulk assign assets to employees using a CSV from your entity-wise Excel register.</p>
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

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-4">Upload CSV (from your Excel)</h5>

            <div class="alert alert-info mb-4">
                <strong>Required columns in your file:</strong>
                <ul class="mb-2 mt-1">
                    <li><strong>ERP ID</strong> (or Employee ID) – must match the Employee ID in <strong>Employee Master</strong> (same as in your Excel register).</li>
                    <li><strong>SERVICE TAG</strong> (or Serial Number) – must match the asset’s <strong>Serial number</strong> in the system (same as in your asset register).</li>
                </ul>
                <strong>Your Excel columns:</strong> Sl.no, <strong>ERP ID</strong>, NAME, Designation, CATEGORY, <strong>SERVICE TAG</strong>, BRAND, MODEL, etc. are fine – we only use <strong>ERP ID</strong> and <strong>SERVICE TAG</strong>.<br>
                <strong>Before importing:</strong> Ensure employees are imported (Employee Master / Import) and assets are imported (Assets / Import) so ERP ID and SERVICE TAG exist. Only <em>available</em> or <em>under maintenance</em> assets can be assigned.<br>
                <strong>File format:</strong> Save Excel as <strong>CSV UTF-8</strong> (File → Save As → CSV UTF-8).
            </div>

            <form action="{{ route('asset-transactions.import-assignments') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Issue date for all assignments</label>
                    <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', date('Y-m-d')) }}">
                    <small class="text-muted">Leave as today unless you want a different date for all rows.</small>
                </div>

                <div class="mb-4">
                    <label class="form-label">File (CSV) <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                    <small class="text-muted">Upload a CSV exported from your entity-wise asset register (e.g. TANSEEQ INVESTMENT LLC).</small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-2"></i>Import Assignments
                    </button>
                    <a href="{{ route('asset-transactions.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
