@extends('layouts.app')

@section('content')
@php
    // Ensure $locations is always defined and is a collection
    $locations = $locations ?? collect([]);
    if (!$locations instanceof \Illuminate\Support\Collection) {
        $locations = collect($locations ?? []);
    }
@endphp
<div class="container-fluid master-page">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="bi bi-geo-alt me-2"></i>Location Master</h2>
       
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Location Form --}}
    <div class="master-form-card">
        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;"> New Location</h5>
        <form method="POST" action="{{ route('location-master.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Location ID <span class="text-danger">*</span></label>
                    <input type="text" name="location_id" class="form-control" value="{{ old('location_id') }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Location Category</label>
                    <input type="text" name="location_category" class="form-control" value="{{ old('location_category') }}">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Location Name</label>
                    <input type="text" name="location_name" class="form-control" value="{{ old('location_name') }}">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Location Entity <span class="text-danger">*</span></label>
                    <input type="text" name="location_entity" class="form-control" value="{{ old('location_entity') }}" required>
                </div>
            </div>

            <div class="text-end mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle me-2"></i>Add Location
                </button>
            </div>
        </form>
    </div>

    {{-- Location Table --}}
    <div class="master-table-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 style="color: white; margin: 0;"><i class="bi bi-list-ul me-2"></i>Location List</h5>
            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-download"></i> Download
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="downloadDropdown">
                    <li><a class="dropdown-item" href="{{ route('location-master.export', ['format' => 'pdf']) }}">
                        <i class="bi bi-file-earmark-pdf me-2"></i>PDF
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('location-master.export', ['format' => 'csv']) }}">
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
                        @if(isset($locations) && $locations->count() > 0)
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
                                            <form action="{{ route('location.destroy', $loc->id) }}" method="POST" style="display:inline-block;">
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
                                <td colspan="6" class="text-center text-muted py-4">No locations found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
