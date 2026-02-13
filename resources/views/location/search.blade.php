@extends('layouts.app')

@section('content')
@php
    $locations = $locations ?? collect([]);
    if (!$locations instanceof \Illuminate\Support\Collection) {
        $locations = collect($locations ?? []);
    }
@endphp
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-search me-2"></i>Location Search</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Search by Entity --}}
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Search by Entity</h5>
        <form method="GET" action="{{ route('location-master.search') }}" id="locationSearchForm">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Entity <span class="text-danger">*</span></label>
                    <select name="entity" class="form-control" required>
                        <option value="">-- Select Entity --</option>
                        @foreach($entities ?? [] as $ent)
                            <option value="{{ $ent }}" {{ request('entity') == $ent ? 'selected' : '' }}>{{ ucwords($ent) }}</option>
                        @endforeach
                    </select>
                 
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Search (optional)</label>
                    <div class="position-relative" id="locationSearchWrap">
                        <input type="text" name="search" id="locationSearchInput" class="form-control" placeholder="Type name, country, or entity..." value="{{ request('search') }}" autocomplete="off">
                        <div id="locationSearchDropdown" class="list-group position-absolute w-100 shadow" style="z-index: 1050; display: none; max-height: 260px; overflow-y: auto;"></div>
                    </div>
                
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i>Search
                    </button>
                    <a href="{{ route('location-master.search') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    @if(request()->filled('entity'))
        <div class="master-table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 style="color: white; margin: 0;"><i class="bi bi-list-ul me-2"></i>Location List ({{ ucwords(request('entity')) }})</h5>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download"></i> Download
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="downloadDropdown">
                        <li><a class="dropdown-item" href="{{ route('location-master.export', ['format' => 'pdf', 'entity' => request('entity'), 'search' => request('search')]) }}">
                            <i class="bi bi-file-earmark-pdf me-2"></i>PDF
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('location-master.export', ['format' => 'csv', 'entity' => request('entity'), 'search' => request('search')]) }}">
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV
                        </a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Entity</th>
                                <th>Country</th>
                                <th>Location Name</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($locations->count() > 0)
                                @foreach($locations as $loc)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $loc->location_entity ?? 'N/A' }}</td>
                                        <td>{{ $loc->location_country ?? 'N/A' }}</td>
                                        <td>{{ $loc->location_name ?? 'N/A' }}</td>
                                        <td>
                                            @if(isset($loc->id))
                                                <a href="{{ route('location.edit', $loc->id) }}" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <form action="{{ route('location-master.destroy', $loc->id) }}" method="POST" style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button onclick="return confirm('Are you sure you want to delete this location?')" class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No locations found for this entity.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
      
    @endif
</div>
<script>
(function() {
    var input = document.getElementById('locationSearchInput');
    var dropdown = document.getElementById('locationSearchDropdown');
    var wrap = document.getElementById('locationSearchWrap');
    var form = document.getElementById('locationSearchForm');
    var debounceTimer = null;
    if (!input || !dropdown) return;
    function hide() { dropdown.style.display = 'none'; dropdown.innerHTML = ''; }
    function show(items) {
        if (!items || items.length === 0) { dropdown.innerHTML = '<div class="list-group-item text-muted">No locations found</div>'; }
        else {
            dropdown.innerHTML = items.map(function(loc) {
                var label = (loc.location_name || '') + (loc.location_country ? ' — ' + loc.location_country : '') + (loc.location_entity ? ' (' + loc.location_entity + ')' : '');
                var val = loc.location_name || '';
                return '<a href="#" class="list-group-item list-group-item-action loc-sugg" data-value="' + (val + '').replace(/"/g, '&quot;') + '">' + label + '</a>';
            }).join('');
        }
        dropdown.style.display = 'block';
    }
    input.addEventListener('input', function() {
        var q = (input.value || '').trim();
        clearTimeout(debounceTimer);
        if (q.length < 1) { hide(); return; }
        debounceTimer = setTimeout(function() {
            fetch('{{ url("location-autocomplete") }}?query=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); }).then(show).catch(hide);
        }, 200);
    });
    dropdown.addEventListener('click', function(e) {
        var el = e.target.closest('.loc-sugg');
        if (el) { e.preventDefault(); input.value = el.getAttribute('data-value'); hide(); form.submit(); }
    });
    document.addEventListener('click', function(e) { if (wrap && !wrap.contains(e.target)) hide(); });
    input.addEventListener('keydown', function(e) { if (e.key === 'Escape') hide(); });
})();
</script>
@endsection
