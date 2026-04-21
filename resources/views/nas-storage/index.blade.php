@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-device-hdd-network me-2"></i>NAS Data Storage Master</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="master-form-card">
        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">Add NAS Storage</h5>
        <form method="POST" action="{{ route('nas-storage.store') }}" autocomplete="off">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Site Name <span class="text-danger">*</span></label>
                    <input type="text" name="site_name" class="form-control" value="{{ old('site_name') }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Location <span class="text-danger">*</span></label>
                    <input type="text" name="location" class="form-control" value="{{ old('location') }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">IP Address <span class="text-danger">*</span></label>
                    <input type="text" name="ip_address" class="form-control" value="{{ old('ip_address') }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" value="{{ old('username') }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="text" name="password" class="form-control" value="{{ old('password') }}" required>
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus-circle me-1"></i>Add
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="master-table-card">
        <div class="card-header">
            <h5 style="color: white; margin: 0;"><i class="bi bi-list-ul me-2"></i>NAS Data Storage List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Site Name</th>
                            <th>Location</th>
                            <th>IP Address</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->site_name }}</td>
                                <td>{{ $item->location }}</td>
                                <td>{{ $item->ip_address }}</td>
                                <td>{{ $item->username }}</td>
                                <td>{{ $item->password }}</td>
                                <td>
                                    <a href="{{ route('nas-storage.edit', $item->id) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="{{ route('nas-storage.destroy', $item->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this record?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No NAS Storage records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
