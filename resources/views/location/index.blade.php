@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-geo-alt me-2"></i>Location Master</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="master-form-card">
        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">New Location</h5>
        <form method="POST" action="{{ route('location-master.store') }}" autocomplete="off">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Location ID <span class="text-danger">*</span></label>
                    <input type="text" name="location_id" class="form-control" value="{{ old('location_id') }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Location Country</label>
                    <input type="text" name="location_country" class="form-control" value="{{ old('location_country') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Location Name</label>
                    <input type="text" name="location_name" class="form-control" value="{{ old('location_name') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Location Entity <span class="text-danger">*</span></label>
                    <select name="location_entity" class="form-control" required>
                        <option value="">-- Select Entity --</option>
                        @foreach($entities ?? [] as $ent)
                            <option value="{{ $ent }}" {{ old('location_entity') == $ent ? 'selected' : '' }}>{{ ucwords($ent) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle me-2"></i>Add Location
                </button>
                <button type="button" class="btn btn-secondary ms-2" onclick="resetForm(this)">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
