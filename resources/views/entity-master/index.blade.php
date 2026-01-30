@extends('layouts.app')

@section('content')
@php
    $entities = $entities ?? collect([]);
@endphp
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-building me-2"></i>Entity Master</h2>
    </div>

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

    {{-- New Entity Form --}}
    <div class="master-form-card">
        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">New Entity</h5>
        <form method="POST" action="{{ route('entity-master.store') }}" autocomplete="off">
            @csrf
            <div class="row">
                <div class="col-md-10 mb-3">
                    <label class="form-label">Entity Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Proscape, Water in Motion" required>
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
