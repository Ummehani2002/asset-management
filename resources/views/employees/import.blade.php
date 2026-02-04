@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-upload me-2"></i>Import Employees</h2>
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
            <h5 class="card-title mb-4">Import ~1500 employees from Excel/CSV</h5>

            <div class="alert alert-info mb-4">
                <strong>Expected columns:</strong> Name, EmployeeID, Designation, Department Name, Email, Phone<br>
                <strong>File format:</strong> Save your Excel as <strong>CSV UTF-8</strong> (File → Save As → CSV UTF-8 Comma delimited). Then upload the .csv file.
            </div>

            <form action="{{ route('employees.import') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="delete_existing" value="1" id="delete_existing">
                        <label class="form-check-label text-danger fw-semibold" for="delete_existing">
                            Delete all existing employees before import
                        </label>
                    </div>
                    <small class="text-muted">Check this to replace current employees with the imported list. Asset transactions will have employee references set to null.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Default Entity <span class="text-danger">*</span></label>
                    <select name="default_entity" class="form-control" required>
                        <option value="">-- Select Entity --</option>
                        @foreach($entities ?? [] as $ent)
                            <option value="{{ $ent }}" {{ old('default_entity') == $ent ? 'selected' : '' }}>{{ ucwords($ent) }}</option>
                        @endforeach
                        <option value="tanseeq" {{ old('default_entity') == 'tanseeq' ? 'selected' : '' }}>Tanseeq</option>
                        <option value="N/A" {{ old('default_entity') == 'N/A' ? 'selected' : '' }}>N/A</option>
                    </select>
                    <small class="text-muted">Used when your file has no Entity column (e.g. for Tanseeq employees)</small>
                </div>

                <div class="mb-4">
                    <label class="form-label">File (CSV) <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                    <small class="text-muted">Upload a CSV file. In Excel: File → Save As → CSV UTF-8.</small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-2"></i>Import
                    </button>
                    <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
