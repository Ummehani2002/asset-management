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
                    <small class="text-muted">Select an entity to see its locations.</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Search (optional)</label>
                    <input type="text" name="search" class="form-control" placeholder="Location ID, name, or category..." value="{{ request('search') }}">
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
                                <th>Location ID</th>
                                <th>Category</th>
                                <th>Location Name</th>
                                <th>Entity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($locations->count() > 0)
                                @foreach($locations as $loc)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $loc->location_id ?? 'N/A' }}</td>
                                        <td>{{ $loc->location_category ?? 'N/A' }}</td>
                                        <td>{{ $loc->location_name ?? 'N/A' }}</td>
                                        <td>{{ $loc->location_entity ?? 'N/A' }}</td>
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
        <div class="alert alert-info text-center">
            <i class="bi bi-funnel display-4 d-block mb-3"></i>
            <h4>Search Locations by Entity</h4>
            <p>Select an entity above and click Search to view locations for that entity.</p>
        </div>
    @endif
</div>
@endsection
