@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="bi bi-search me-2"></i>Search Employees</h2>
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

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span>{{ session('warning') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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
        <form method="GET" action="{{ route('employees.search') }}" id="searchForm">
            <div class="row">
                <div class="col-md-10 mb-3">
                    <label class="form-label">Search by Employee Name, ID, Entity, or Email</label>
                    <div class="position-relative" id="searchEmployeeWrap">
                        <input type="text" name="search" id="searchEmployee" class="form-control" 
                               placeholder="Type employee name, ID, entity, or email..." value="{{ request('search') }}"
                               autocomplete="off">
                        <div id="employeeDropdown" class="list-group position-absolute start-0 end-0 mt-1 shadow-sm border rounded" 
                             style="z-index: 9999; display: none; max-height: 280px; overflow-y: auto; background: #fff; top: 100%;"></div>
                    </div>
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>Search
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Employee Table (shown when search is performed) --}}
    @if(request()->has('search') && request('search'))
        @if(isset($employees) && $employees->count() > 0)
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
                                    <th>Status</th>
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
                                    <tr class="{{ $emp->is_active === false ? 'table-secondary' : '' }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $emp->employee_id }}</td>
                                        <td>{{ $emp->name ?? 'N/A' }}</td>
                                        <td>
                                            @if($emp->is_active !== false)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Non Active</span>
                                            @endif
                                        </td>
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
                <p>No employees match your search criteria.</p>
            </div>
        @endif
    @else
        <div class="alert alert-info text-center">
            <i class="bi bi-search display-4 d-block mb-3"></i>
            <h4>Search for Employees</h4>
            <p>Enter a search term above to find employees by name, ID, entity, or email.</p>
        </div>
    @endif
</div>

<style>
#searchEmployeeWrap { overflow: visible !important; }
#searchEmployeeWrap .row { overflow: visible !important; }
#employeeDropdown.list-group .list-group-item { cursor: pointer; }
</style>

<script>
(function() {
    const input = document.getElementById('searchEmployee');
    const dropdown = document.getElementById('employeeDropdown');
    const wrap = document.getElementById('searchEmployeeWrap');
    const form = document.getElementById('searchForm');
    let debounceTimer = null;
    const minChars = 1;

    if (!input || !dropdown) return;

    function hideDropdown() {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
    }

    function showDropdown(items) {
        if (!items || items.length === 0) {
            dropdown.innerHTML = '<div class="list-group-item text-muted">No similar employees found</div>';
        } else {
            dropdown.innerHTML = items.map(function(emp) {
                const name = emp.name || emp.entity_name || 'N/A';
                const extra = [emp.employee_id, emp.department_name, emp.designation].filter(Boolean).join(' · ');
                const label = '<div class="fw-semibold">' + escapeHtml(name) + '</div>' + (extra ? '<small class="text-muted">' + escapeHtml(extra) + '</small>' : '');
                return '<a href="#" class="list-group-item list-group-item-action employee-suggestion" data-value="' + escapeHtml(emp.name || emp.entity_name || emp.employee_id || '') + '" data-id="' + emp.id + '">' + label + '</a>';
            }).join('');
        }
        dropdown.style.display = 'block';
    }

    function escapeHtml(s) {
        if (!s) return '';
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML.replace(/"/g, '&quot;');
    }

    function fetchSuggestions(q) {
        if (!q || q.length < minChars) {
            hideDropdown();
            return;
        }
        dropdown.innerHTML = '<div class="list-group-item text-muted">Loading...</div>';
        dropdown.style.display = 'block';
        const url = '{{ route("employees.autocomplete") }}?query=' + encodeURIComponent(q);
        fetch(url, { credentials: 'same-origin' })
            .then(function(r) {
                if (!r.ok) {
                    return r.text().then(function(t) {
                        try {
                            var d = JSON.parse(t);
                            dropdown.innerHTML = '<div class="list-group-item text-danger">' + (d.error || 'Error loading suggestions') + '</div>';
                        } catch (e) {
                            dropdown.innerHTML = '<div class="list-group-item text-danger">Error loading suggestions (status ' + r.status + ')</div>';
                        }
                    });
                }
                return r.json();
            })
            .then(function(data) {
                if (data && Array.isArray(data)) {
                    showDropdown(data);
                } else if (data && data.error) {
                    dropdown.innerHTML = '<div class="list-group-item text-danger">' + data.error + '</div>';
                }
            })
            .catch(function(err) {
                dropdown.innerHTML = '<div class="list-group-item text-danger">Error loading suggestions. Please try again.</div>';
            });
    }

    input.addEventListener('input', function() {
        const q = (input.value || '').trim();
        clearTimeout(debounceTimer);
        if (q.length < minChars) {
            hideDropdown();
            return;
        }
        debounceTimer = setTimeout(function() {
            fetchSuggestions(q);
        }, 200);
    });

    input.addEventListener('focus', function() {
        const q = (input.value || '').trim();
        if (q.length >= minChars) {
            if (dropdown.innerHTML) {
                dropdown.style.display = 'block';
            } else {
                fetchSuggestions(q);
            }
        }
    });

    dropdown.addEventListener('click', function(e) {
        const item = e.target.closest('.employee-suggestion');
        if (item) {
            e.preventDefault();
            input.value = item.getAttribute('data-value') || item.textContent.trim();
            hideDropdown();
            form.submit();
        }
    });

    document.addEventListener('click', function(e) {
        if (wrap && !wrap.contains(e.target)) {
            hideDropdown();
        }
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideDropdown();
        }
    });
})();
</script>
@endsection
