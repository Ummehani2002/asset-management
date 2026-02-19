@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2><i class="bi bi-person-badge me-2"></i>Edit Employee</h2>
            @if($employee->is_active === false)
                <span class="badge bg-secondary fs-6 mt-2">Non Active</span>
            @endif
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if($employee->is_active === false)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            This employee has returned all assets and is marked as non-active. All details are read-only.
        </div>
    @endif

    <div class="master-form-card">
        <form action="{{ route('employees.update', $employee->id) }}" method="POST" autocomplete="off">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Employee ID</label>
                    <input type="text" value="{{ $employee->employee_id }}" class="form-control bg-light" readonly disabled>
               
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" value="{{ $employee->name ?? $employee->entity_name }}" class="form-control bg-light" readonly disabled>
                   
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email', $employee->email) }}" class="form-control" maxlength="100" inputmode="email"
                           {{ $employee->is_active === false ? 'readonly disabled' : '' }}>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="form-control" maxlength="20"
                           {{ $employee->is_active === false ? 'readonly disabled' : '' }}>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Entity Name</label>
                    <select name="entity_name" class="form-control searchable-select" data-placeholder="Type to search..." {{ $employee->is_active === false ? 'disabled' : '' }}>
                        <option value="">-- Select Entity --</option>
                        @foreach(\App\Helpers\EntityHelper::getEntities() as $ent)
                            <option value="{{ $ent }}" {{ old('entity_name', $employee->entity_name) == $ent ? 'selected' : '' }}>{{ ucwords($ent) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Department Name</label>
                    <input type="text" name="department_name" value="{{ old('department_name', $employee->department_name) }}" class="form-control" maxlength="100"
                           {{ $employee->is_active === false ? 'readonly disabled' : '' }}>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" list="designation-list" class="form-control" autocomplete="off"
                           value="{{ old('designation', $employee->designation ?? '') }}" placeholder="Type or select (e.g. Project Manager, PC Secretary)" maxlength="100">
                    <small class="text-muted">Editable even for non-active employees (e.g. if missing from import).</small>
                    <datalist id="designation-list">
                        <option value="Project Manager">
                        <option value="Assistant Project Manager">
                        <option value="Person in Charge">
                        <option value="Document Controller">
                        <option value="PC Secretary">
                    </datalist>
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Update
                </button>
                <button type="button" class="btn btn-secondary ms-2" onclick="window.location.href='{{ route('employees.index') }}'">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
