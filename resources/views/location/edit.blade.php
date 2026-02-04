@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-geo-alt me-2"></i>Edit Location</h2>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="master-form-card">
        <form action="{{ route('location-master.update', $location->id) }}" method="POST" autocomplete="off">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Location ID</label>
                    <input type="text" value="{{ $location->location_id }}" class="form-control bg-light" readonly disabled>
                    <small class="text-muted">Location ID cannot be changed.</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Location Country</label>
                    <input type="text" name="location_country" value="{{ old('location_country', $location->location_country) }}" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Location Name</label>
                    <input type="text" name="location_name" value="{{ old('location_name', $location->location_name) }}" class="form-control" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Location Entity <span class="text-danger">*</span></label>
                    <select name="location_entity" class="form-control" required>
                        <option value="">-- Select Entity --</option>
                        @foreach(\App\Helpers\EntityHelper::getEntities() as $ent)
                            <option value="{{ $ent }}" {{ old('location_entity', $location->location_entity) == $ent ? 'selected' : '' }}>{{ ucwords($ent) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Update Location
                </button>
                <button type="button" class="btn btn-secondary ms-2" onclick="window.location.href='{{ route('location-master.index') }}'">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
