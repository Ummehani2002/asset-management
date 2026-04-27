@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h2><i class="bi bi-box-arrow-right me-2"></i>Issue IT Consumable</h2>
        <a href="{{ route('it-consumables.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="master-form-card mb-4">
        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">Allocated Item Details</h5>
        <div class="row">
            <div class="col-md-3 mb-2"><strong>ID No:</strong> {{ $item->id_no }}</div>
            <div class="col-md-5 mb-2"><strong>Item:</strong> {{ $item->item_description }}</div>
            <div class="col-md-4 mb-2"><strong>Allocation Date:</strong> {{ optional($item->issued_date)->format('d-m-Y') }}</div>
            <div class="col-md-3 mb-2"><strong>Allocated Qty:</strong> {{ $item->allocated_qty }}</div>
            <div class="col-md-3 mb-2"><strong>Issued Qty:</strong> {{ $issuedQty }}</div>
            <div class="col-md-3 mb-2"><strong>Remaining Qty:</strong> <span class="badge {{ $remainingQty > 0 ? 'bg-success' : 'bg-secondary' }}">{{ $remainingQty }}</span></div>
        </div>
    </div>

    <div class="master-form-card mb-4">
        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">Issue Form</h5>
        <form method="POST" action="{{ route('it-consumables.issue-store', $item->id) }}" autocomplete="off">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Issue To <span class="text-danger">*</span></label>
                    <input type="text" name="issue_to_name" class="form-control" value="{{ old('issue_to_name') }}" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">TKT Ref No <span class="text-danger">*</span></label>
                    <input type="text" name="tkt_ref_no" class="form-control" value="{{ old('tkt_ref_no') }}" {{ $remainingQty <= 0 ? 'disabled' : '' }} required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Issue Qty <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" min="1" max="{{ $remainingQty }}" class="form-control" value="{{ old('quantity') }}" {{ $remainingQty <= 0 ? 'disabled' : '' }} required>
                    <small class="text-muted">Max allowed: {{ $remainingQty }}</small>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                    <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', now()->format('Y-m-d')) }}" {{ $remainingQty <= 0 ? 'disabled' : '' }} required>
                </div>
                <div class="col-md-12 mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea name="remarks" class="form-control" rows="2" {{ $remainingQty <= 0 ? 'disabled' : '' }}>{{ old('remarks') }}</textarea>
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100" {{ $remainingQty <= 0 ? 'disabled' : '' }}>
                        <i class="bi bi-check-circle me-1"></i>Issue
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="master-table-card">
        <div class="card-header">
            <h5 style="color: white; margin: 0;"><i class="bi bi-clock-history me-2"></i>Issue History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Issue To</th>
                            <th>TKT Ref No</th>
                            <th>Qty</th>
                            <th>Issue Date</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($item->issues as $issue)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $issue->issue_to_name }}</td>
                                <td>{{ $issue->tkt_ref_no ?? '-' }}</td>
                                <td>{{ $issue->quantity }}</td>
                                <td>{{ optional($issue->issue_date)->format('d-m-Y') }}</td>
                                <td>{{ $issue->remarks ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No issues created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
