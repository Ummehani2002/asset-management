@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-pencil-square me-2"></i>Edit NAS Storage</h2>
    </div>

    <div class="master-form-card">
        <form method="POST" action="{{ route('nas-storage.update', $item->id) }}" autocomplete="off">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Site Name <span class="text-danger">*</span></label>
                    <input type="text" name="site_name" class="form-control" value="{{ old('site_name', $item->site_name) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Location <span class="text-danger">*</span></label>
                    <input type="text" name="location" class="form-control" value="{{ old('location', $item->location) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">IP Address <span class="text-danger">*</span></label>
                    <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address', $item->ip_address) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" value="{{ old('username', $item->username) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="text" name="password" class="form-control" value="{{ old('password', $item->password) }}" required>
                </div>
            </div>
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Update
                </button>
                <a href="{{ route('nas-storage.index') }}" class="btn btn-secondary ms-2">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
