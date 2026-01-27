@extends('layouts.app')

@section('title', 'Internet Services Management')

@section('content')
<div class="container-fluid master-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-wifi me-2"></i>Internet Services Management</h2>
                <p>Manage internet service subscriptions and accounts</p>
            </div>
            <a href="{{ route('internet-services.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Add New Internet Service
            </a>
        </div>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            @if(session('returned_service_id'))
                <a href="{{ route('internet-services.download-form', session('returned_service_id')) }}" class="btn btn-sm btn-outline-light ms-2" target="_blank">
                    <i class="bi bi-download me-1"></i>Download Form (PDF)
                </a>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search and Filter Form -->
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-search me-2"></i>Search & Filter</h5>
        <form method="GET" action="{{ route('internet-services.index') }}" id="searchForm">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by project, account number..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Service Type</label>
                    <select name="service_type" class="form-control">
                        <option value="">All Types</option>
                        <option value="simcard" {{ request('service_type') == 'simcard' ? 'selected' : '' }}>SIM Card</option>
                        <option value="fixed" {{ request('service_type') == 'fixed' ? 'selected' : '' }}>Fixed Service</option>
                        <option value="service" {{ request('service_type') == 'service' ? 'selected' : '' }}>Out Sourced</option>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="suspend" {{ request('status') == 'suspend' ? 'selected' : '' }}>Suspend</option>
                        <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i>Search
                    </button>
                    <a href="{{ route('internet-services.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    
    @if(request()->hasAny(['search', 'service_type', 'status']) && $internetServices->count() > 0)
        <div class="master-table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 style="color: white; margin: 0;"><i class="bi bi-list-ul me-2"></i>All Internet Services</h5>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download"></i> Download
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="downloadDropdown">
                        <li><a class="dropdown-item" href="{{ route('internet-services.export', array_merge(request()->only(['search', 'service_type', 'status']), ['format' => 'pdf'])) }}">
                            <i class="bi bi-file-pdf me-2"></i>PDF
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('internet-services.export', array_merge(request()->only(['search', 'service_type', 'status']), ['format' => 'csv'])) }}">
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
                                <th>Project</th>
                                <th>Entity</th>
                                <th>Service Type</th>
                                <th>Account No.</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Person in Charge</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($internetServices as $index => $service)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $service->project_name ?? 'N/A' }}</strong><br>
                                        <small class="text-muted">ID: {{ $service->project_id ?? ($service->project->id ?? 'N/A') }}</small>
                                    </td>
                                    <td>{{ $service->entity ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ ucfirst($service->service_type ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($service->account_number)
                                            <span class="text-danger fw-semibold">{{ $service->account_number }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>{{ $service->service_start_date->format('d-m-Y') }}</td>
                                    <td>
                                        @if($service->service_end_date)
                                            {{ $service->service_end_date->format('d-m-Y') }}
                                        @else
                                            <span class="text-muted">Ongoing</span>
                                        @endif
                                    </td>
                                    <td>{{ $service->person_in_charge ?? 'N/A' }}</td>
                                    <td>
                                        <span class="status-badge status-{{ $service->status ?? 'active' }}">
                                            {{ ucfirst($service->status ?? 'Active') }}
                                        </span>
                                    </td>
                                    <td>
                                        @if(!$service->service_end_date)
                                            <a href="{{ route('internet-services.return', $service->id) }}" 
                                               class="btn btn-sm btn-info" title="Return Service">
                                                <i class="bi bi-arrow-return-left"></i> Return
                                            </a>
                                        @endif
                                        <a href="{{ route('internet-services.edit', $service->id) }}" 
                                           class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('internet-services.destroy', $service->id) }}" 
                                              method="POST" class="d-inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this internet service?')" 
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer" style="background: #f8f9fa; padding: 12px 20px; border-top: 1px solid #dee2e6;">
                <p class="text-muted mb-0" style="font-size: 14px;">
                    Showing {{ $internetServices->count() }} internet service(s)
                </p>
            </div>
        </div>
    @elseif(request()->hasAny(['search', 'service_type', 'status']))
        <div class="alert alert-info text-center">
            <i class="bi bi-wifi-off display-4 d-block mb-3"></i>
            <h4>No Results Found</h4>
            <p class="mb-3">No internet services match your search criteria. Try adjusting your filters.</p>
        </div>
    @else
        
    @endif
</div>
@endsection
