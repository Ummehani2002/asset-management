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
        <form action="{{ route('employees.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Employee ID <span class="text-danger">*</span></label>
                    <input type="text" name="employee_id" value="{{ old('employee_id') }}" class="form-control" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-control">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Entity Name <span class="text-danger">*</span></label>
                    <input type="text" name="entity_name" value="{{ old('entity_name') }}" class="form-control" required>
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
            </div>
        </form>
    </div>

    {{-- Search Section --}}
    <div class="master-form-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0"><i class="bi bi-search me-2"></i>Search Employee</h5>
            <div class="dropdown">
                <button class="btn btn-sm btn-success dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download me-1"></i>Download All
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
        <form method="GET" action="{{ route('employees.index') }}" id="searchForm">
            <div class="row">
                <div class="col-md-10 mb-3">
                    <label class="form-label">Search by Employee Name or ID</label>
                    <input type="text" name="search" id="searchEmployee" class="form-control" 
                           placeholder="Type employee name or ID..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>Search
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Employee Table (only shown when search is performed) --}}
    @if(request()->has('search') && request('search'))
        @if($employees->count() > 0)
            <div class="master-table-card">
                <div class="card-header">
                    <h5 style="color: white; margin: 0;">
                        <i class="bi bi-people me-2"></i>Search Results ({{ $employees->count() }} found)
                    </h5>
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
                <h4>No Employee Found</h4>
               
            </div>
        @endif
    @else
       
    @endif
</div>
@endsection
