@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-person-gear me-2"></i>Assign Asset Manager</h2>
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
        <form method="POST" action="{{ route('asset-manager.update', $entity->id) }}" autocomplete="off">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Entity</label>
                    <input type="text" class="form-control bg-light" value="{{ ucwords($entity->name) }}" readonly disabled>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Asset Manager (Employee)</label>
                    <select name="asset_manager_id" class="form-control employee-select" data-placeholder="Type to search...">
                        <option value="">-- No asset manager --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('asset_manager_id', $entity->asset_manager_id) == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name ?? $emp->entity_name ?? 'N/A' }} ({{ $emp->employee_id ?? $emp->id }})
                            </option>
                        @endforeach
                    </select>
                  
                </div>
            </div>
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Save
                </button>
                <a href="{{ route('asset-manager.index') }}" class="btn btn-secondary ms-2">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
