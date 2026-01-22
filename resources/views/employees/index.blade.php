@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="bi bi-person-badge me-2"></i>Employee Master</h2>
      
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
                        <option value="proscape" {{ old('entity_name') == 'proscape' ? 'selected' : '' }}>Proscape</option>
                        <option value="water in motion" {{ old('entity_name') == 'water in motion' ? 'selected' : '' }}>Water in Motion</option>
                        <option value="bioscape" {{ old('entity_name') == 'bioscape' ? 'selected' : '' }}>Bioscape</option>
                        <option value="tanseeq realty" {{ old('entity_name') == 'tanseeq realty' ? 'selected' : '' }}>Tanseeq Realty</option>
                        <option value="transmech" {{ old('entity_name') == 'transmech' ? 'selected' : '' }}>Transmech</option>
                        <option value="timbertech" {{ old('entity_name') == 'timbertech' ? 'selected' : '' }}>Timbertech</option>
                        <option value="ventana" {{ old('entity_name') == 'ventana' ? 'selected' : '' }}>Ventana</option>
                        <option value="garden center" {{ old('entity_name') == 'garden center' ? 'selected' : '' }}>Garden Center</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Department Name <span class="text-danger">*</span></label>
                    <input type="text" name="department_name" value="{{ old('department_name') }}" class="form-control" required>
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

    {{-- Employee List Table --}}
    @if(isset($employees) && $employees->count() > 0)
        <div class="master-table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 style="color: white; margin: 0;">
                    <i class="bi bi-people me-2"></i>All Employees ({{ $employees->count() }})
                </h5>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download"></i> Download
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="downloadDropdown">
                        <li><a class="dropdown-item" href="{{ route('employees.export', ['format' => 'pdf']) }}">
                            <i class="bi bi-file-earmark-pdf me-2"></i>PDF
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('employees.export', ['format' => 'csv']) }}">
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV
                        </a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0" id="employeeTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Entity</th>
                                <th>Department</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="employeeBody">
                            @foreach($employees as $emp)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $emp->employee_id }}</td>
                                    <td>{{ $emp->name ?? 'N/A' }}</td>
                                    <td>{{ $emp->email ?? 'N/A' }}</td>
                                    <td>{{ $emp->phone ?? 'N/A' }}</td>
                                    <td>{{ $emp->entity_name ?? 'N/A' }}</td>
                                    <td>{{ $emp->department_name ?? 'N/A' }}</td>
                                    <td>{{ $emp->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <a href="{{ route('employees.edit', $emp->id) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" style="display:inline-block;">
                                            @csrf
                                            @method('DELETE')
                                            <button onclick="return confirm('Are you sure you want to delete this employee?')" class="btn btn-sm btn-danger">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle display-4 d-block mb-3"></i>
            <h4>No Employees Found</h4>
            <p>No employees have been added yet.</p>
        </div>
    @endif
</div>
@endsection
