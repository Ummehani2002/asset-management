@extends('layouts.app')
@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-upload me-2"></i>Import Category, Brand & Model</h2>
        <p class="text-muted mb-0">Add these first from your Excel. Use a CSV with exactly three columns: <strong>CATEGORY</strong>, <strong>BRAND</strong>, <strong>MODEL</strong>.</p>
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
            <h5 class="card-title mb-4">Upload CSV (Category, Brand, Model only)</h5>

          
            <form action="{{ route('brand-management.import-category-brand-model') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="form-label">File (CSV) <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                    <small class="text-muted">Upload a CSV with columns: CATEGORY, BRAND, MODEL.</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-2"></i>Import
                    </button>
                    <a href="{{ route('brand-management.add-brand-model') }}" class="btn btn-secondary">Back to Add Brand & Model</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
