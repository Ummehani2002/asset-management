@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <!-- Page Header -->
    <div class="page-header">
        <h2 class="mb-0"><i class="bi bi-person-badge me-2"></i>Employee Master</h2>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('error') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Employee Form --}}
    <div class="master-form-card">
        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;"> New Employee</h5>
        <form action="{{ route('employees.store') }}" method="POST" autocomplete="off">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Employee ID <span class="text-danger">*</span></label>
                    <input type="text" name="employee_id" value="{{ old('employee_id') }}" class="form-control" autocomplete="off" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" autocomplete="off">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="text" name="email" value="{{ old('email') }}" class="form-control" autocomplete="off" inputmode="email">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" autocomplete="off">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Entity Name <span class="text-danger">*</span></label>
                    <select name="entity_name" class="form-control" required>
                        <option value="">-- Select Entity --</option>
                        @foreach(\App\Helpers\EntityHelper::getEntities() as $ent)
                            <option value="{{ $ent }}" {{ old('entity_name') == $ent ? 'selected' : '' }}>{{ ucwords($ent) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Department Name</label>
                    <input type="text" name="department_name" value="{{ old('department_name') }}" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" list="designation-list" class="form-control" autocomplete="off"
                           value="{{ old('designation') }}" placeholder="Type or select ">
                    <datalist id="designation-list">
                        <option value="Project Manager">
                        <option value="Person in Charge">
                        <option value="Document Controller">
                        <option value="PC Secretary">
                    </datalist>

                </div>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle me-2"></i>Save Employee
                </button>
                <button type="button" class="btn btn-secondary ms-2" onclick="resetForm(this)">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>

   
</div>
@endsection
