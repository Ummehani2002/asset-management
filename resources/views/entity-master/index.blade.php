@extends('layouts.app')

@section('content')
@php
    $entities = $entities ?? collect([]);
@endphp
<div class="container-fluid master-page">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2><i class="bi bi-building me-2"></i>Entity Master</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('employees.import.form') }}" class="btn btn-primary">
                <i class="bi bi-upload me-1"></i>Import Employees
            </a>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#entityImportModal">
                <i class="bi bi-file-earmark-arrow-up me-1"></i>Import Entities
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
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

    {{-- Entity Import Modal --}}
    <div class="modal fade" id="entityImportModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-file-earmark-arrow-up me-2"></i>Import Entities</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Choose how to import entities:</p>

                    {{-- Option 1: Sync from CSV --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <h6 class="card-title"><i class="bi bi-file-earmark-spreadsheet text-primary me-2"></i>From CSV/Excel</h6>
                            <p class="small text-muted mb-2">Upload a CSV with Entity, Entity Name, or Company column.</p>
                            <form action="{{ route('entity-master.sync-from-csv') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="replace_existing" value="1" id="replace_csv">
                                    <label class="form-check-label" for="replace_csv">Replace existing entities</label>
                                </div>
                                <div class="input-group">
                                    <input type="file" name="file" class="form-control form-control-sm" accept=".csv,.txt" required>
                                    <button type="submit" class="btn btn-primary btn-sm">Import</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- Option 2: Sync from Employees --}}
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title"><i class="bi bi-people text-primary me-2"></i>From Existing Employees</h6>
                            <p class="small text-muted mb-2">Extract entity names from employee records already in the system.</p>
                            <form action="{{ route('entity-master.sync-from-employees') }}" method="POST">
                                @csrf
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="replace_existing" value="1" id="replace_emp">
                                    <label class="form-check-label" for="replace_emp">Replace existing entities</label>
                                </div>
                                <button type="submit" class="btn btn-outline-primary btn-sm">Sync from Employees</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- New Entity Form --}}
    <div class="master-form-card">
        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">New Entity</h5>
        <form method="POST" action="{{ route('entity-master.store') }}" autocomplete="off">
            @csrf
            <div class="row">
                <div class="col-md-10 mb-3">
                    <label class="form-label">Entity Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}"  required>
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus-circle me-1"></i>Add Entity
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Entity List --}}
    <div class="master-table-card">
        <div class="card-header">
            <h5 style="color: white; margin: 0;"><i class="bi bi-list-ul me-2"></i>Entity List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Entity Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($entities->count() > 0)
                            @foreach($entities as $ent)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ ucwords($ent->name) }}</td>
                                    <td>
                                        <a href="{{ route('entity-master.edit', $ent->id) }}" class="btn btn-sm btn-warning">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <form action="{{ route('entity-master.destroy', $ent->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this entity?');">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No entities yet. Add one above.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
