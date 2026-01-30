@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="bi bi-kanban me-2"></i>Project Master</h2>
       
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div></div>
        <div class="d-flex gap-2">
            <div class="dropdown">
                <button class="btn btn-sm btn-success dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download me-1"></i>Download
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="downloadDropdown">
                    <li><a class="dropdown-item" href="{{ route('projects.export', ['format' => 'pdf']) }}">
                        <i class="bi bi-file-earmark-pdf me-2"></i>PDF
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('projects.export', ['format' => 'csv']) }}">
                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV
                    </a></li>
                </ul>
            </div>
            <a href="{{ route('projects.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add Project
            </a>
        </div>
    </div>

    <!-- Search and Filter Form -->
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-search me-2"></i>Search & Filter</h5>
        <form method="GET" action="{{ route('projects.index') }}" id="searchForm">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Search (Project ID / Name)</label>
                    <div class="position-relative" id="searchProjectWrap">
                        <input type="text" name="search" id="searchProject" class="form-control" placeholder="Type project ID or name..." value="{{ request('search') }}" autocomplete="off">
                        <div id="projectDropdown" class="list-group position-absolute w-100 shadow" style="z-index: 1050; display: none; max-height: 260px; overflow-y: auto;"></div>
                    </div>
                    <small class="text-muted">Start typing to see matching projects.</small>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Entity</label>
                    <select name="entity" class="form-control">
                        <option value="">-- All entities --</option>
                        @foreach($entities ?? [] as $ent)
                            <option value="{{ $ent }}" {{ request('entity') == $ent ? 'selected' : '' }}>{{ ucwords($ent) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i>Search
                    </button>
                    <a href="{{ route('projects.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <script>
    (function() {
        var input = document.getElementById('searchProject');
        var dropdown = document.getElementById('projectDropdown');
        var wrap = document.getElementById('searchProjectWrap');
        var form = document.getElementById('searchForm');
        var debounceTimer = null;
        if (!input || !dropdown) return;
        function hideDropdown() { dropdown.style.display = 'none'; dropdown.innerHTML = ''; }
        function showDropdown(items) {
            if (!items || items.length === 0) {
                dropdown.innerHTML = '<div class="list-group-item text-muted">No projects found</div>';
            } else {
                dropdown.innerHTML = items.map(function(p) {
                    var label = (p.project_id || '') + ' — ' + (p.project_name || '');
                    return '<a href="#" class="list-group-item list-group-item-action project-suggestion" data-value="' + (p.project_id || p.project_name || '').replace(/"/g, '&quot;') + '">' + label + '</a>';
                }).join('');
            }
            dropdown.style.display = 'block';
        }
        input.addEventListener('input', function() {
            var q = (input.value || '').trim();
            clearTimeout(debounceTimer);
            if (q.length < 1) { hideDropdown(); return; }
            debounceTimer = setTimeout(function() {
                fetch('{{ route("projects.autocomplete") }}?query=' + encodeURIComponent(q))
                    .then(function(r) { return r.json(); })
                    .then(showDropdown)
                    .catch(hideDropdown);
            }, 200);
        });
        dropdown.addEventListener('click', function(e) {
            var item = e.target.closest('.project-suggestion');
            if (item) {
                e.preventDefault();
                input.value = item.getAttribute('data-value');
                hideDropdown();
                form.submit();
            }
        });
        document.addEventListener('click', function(e) {
            if (wrap && !wrap.contains(e.target)) hideDropdown();
        });
        input.addEventListener('keydown', function(e) { if (e.key === 'Escape') hideDropdown(); });
    })();
    </script>

    @if(request()->hasAny(['search', 'entity']) && $projects->count() > 0)
        <div class="master-table-card">
            <div class="card-header">
                <h5 style="color: white; margin: 0;"><i class="bi bi-list-ul me-2"></i>All Projects</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Project ID</th>
                                <th>Project Name</th>
                                <th>Entity</th>
                                <th>Project Manager</th>
                                <th>PC Secretary</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($projects as $index => $project)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $project->project_id }}</td>
                                    <td>{{ $project->project_name }}</td>
                                    <td>{{ $project->entity ?? 'N/A' }}</td>
                                    <td>{{ $project->project_manager ?? 'N/A' }}</td>
                                    <td>{{ $project->pc_secretary ?? 'N/A' }}</td>
                                    <td>
                                        <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('projects.destroy', $project->id) }}" method="POST" class="d-inline-block"
                                              onsubmit="return confirm('Delete project {{ addslashes($project->project_name) }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
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

    @elseif(request()->hasAny(['search', 'entity']))
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle display-4 d-block mb-3"></i>
            <h4>No Projects Found</h4>
           
        </div>
    @else
        
    @endif
</div>
@endsection
