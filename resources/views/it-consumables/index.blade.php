@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-box-seam me-2"></i>IT Consumables Master</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="master-form-card">
        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">Add IT Consumable</h5>
        <form method="POST" action="{{ route('it-consumables.store') }}" autocomplete="off">
            @csrf
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">ID No <span class="text-danger">*</span></label>
                    <input type="text" name="id_no" class="form-control" value="{{ old('id_no') }}" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Item Description <span class="text-danger">*</span></label>
                    <input type="text" name="item_description" class="form-control" value="{{ old('item_description') }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Issued Date <span class="text-danger">*</span></label>
                    <input type="date" name="issued_date" class="form-control" value="{{ old('issued_date') }}" required>
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus-circle me-1"></i>Add
                    </button>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                </div>
            </div>
        </form>
    </div>

    <div class="master-table-card">
        <div class="card-header">
            <h5 style="color: white; margin: 0;"><i class="bi bi-list-ul me-2"></i>IT Consumables List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ID No</th>
                            <th>Item Description</th>
                            <th>Issued Date</th>
                            <th>Remarks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->id_no }}</td>
                                <td>{{ $item->item_description }}</td>
                                <td>{{ optional($item->issued_date)->format('d-m-Y') }}</td>
                                <td>{{ $item->remarks ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('it-consumables.edit', $item->id) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="{{ route('it-consumables.destroy', $item->id) }}" method="POST" class="d-inline">
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
                                <td colspan="6" class="text-center text-muted py-4">No IT Consumables records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
