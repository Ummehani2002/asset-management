@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-journal-text me-2"></i>Issue Notes & Return Notes</h2>
                <p>View and manage all issue notes and return notes</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('issue-note.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>New Issue Note
                </a>
                <a href="{{ route('issue-note.create-return') }}" class="btn btn-success">
                    <i class="bi bi-arrow-return-left me-2"></i>New Return Note
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Search and Filter Form -->
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-search me-2"></i>Search & Filter</h5>
        <form method="GET" action="{{ route('issue-note.index') }}" id="searchForm">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by employee, department, entity..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Note Type</label>
                    <select name="note_type" class="form-control">
                        <option value="">All Types</option>
                        <option value="issue" {{ request('note_type') == 'issue' ? 'selected' : '' }}>Issue Notes</option>
                        <option value="return" {{ request('note_type') == 'return' ? 'selected' : '' }}>Return Notes</option>
                    </select>
                </div>
                <div class="col-md-5 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search me-1"></i>Search
                    </button>
                    <a href="{{ route('issue-note.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    @if(request()->hasAny(['search', 'note_type']) && $issueNotes->count() > 0)
        <div class="master-table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 style="color: white; margin: 0;">
                    <i class="bi bi-list-ul me-2"></i>All Notes ({{ $issueNotes->count() }})
                </h5>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download"></i> Download
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="downloadDropdown">
                        <li><a class="dropdown-item" href="{{ route('issue-note.export', array_merge(request()->only(['search', 'note_type']), ['format' => 'pdf'])) }}">
                            <i class="bi bi-file-pdf me-2"></i>PDF
                        </a></li>
                        <li><a class="dropdown-item" href="{{ route('issue-note.export', array_merge(request()->only(['search', 'note_type']), ['format' => 'csv'])) }}">
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
                                <th>Type</th>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Entity</th>
                                <th>Location</th>
                                <th>System Code</th>
                                <th>Printer Code</th>
                                <th>Issued Date</th>
                                <th>Return Date</th>
                                <th>Items</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($issueNotes as $index => $note)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if($note->note_type == 'return')
                                            <span class="badge bg-success">Return</span>
                                        @else
                                            <span class="badge bg-primary">Issue</span>
                                        @endif
                                    </td>
                                    <td>{{ $note->employee->name ?? $note->employee->entity_name ?? 'N/A' }}</td>
                                    <td>{{ $note->department ?? 'N/A' }}</td>
                                    <td>{{ $note->entity ?? 'N/A' }}</td>
                                    <td>{{ $note->location ?? 'N/A' }}</td>
                                    <td>{{ $note->system_code ?? 'N/A' }}</td>
                                    <td>{{ $note->printer_code ?? 'N/A' }}</td>
                                    <td>{{ $note->issued_date ? $note->issued_date->format('Y-m-d') : 'N/A' }}</td>
                                    <td>{{ $note->return_date ? $note->return_date->format('Y-m-d') : 'N/A' }}</td>
                                    <td>
                                        @if(is_array($note->items) && count($note->items) > 0)
                                            <ul class="mb-0" style="font-size: 0.85rem;">
                                                @foreach($note->items as $item)
                                                    <li>{{ $item }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <em class="text-muted">No items</em>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif(request()->hasAny(['search', 'note_type']))
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle display-4 d-block mb-3"></i>
            <h4>No Results Found</h4>
            <p class="mb-3">No notes match your search criteria. Try adjusting your filters.</p>
        </div>
    @else
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle display-4 d-block mb-3"></i>
            <h4>Search to View Notes</h4>
            <p class="mb-3">Use the search and filter options above to view issue notes and return notes.</p>
        </div>
    @endif
</div>
@endsection

