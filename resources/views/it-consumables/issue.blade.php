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
            <div class="col-md-3 mb-2"><strong>Item Type:</strong> {{ $item->category->category_name ?? '-' }}</div>
            <div class="col-md-3 mb-2"><strong>ID No:</strong> {{ $item->id_no }}</div>
            <div class="col-md-3 mb-2"><strong>TKT Ref No:</strong> {{ $item->tkt_ref_no ?? '-' }}</div>
            <div class="col-md-5 mb-2"><strong>Item:</strong> {{ $item->item_description }}</div>
            <div class="col-md-4 mb-2"><strong>Allocation Date:</strong> {{ optional($item->issued_date)->format('d-m-Y') }}</div>
            <div class="col-md-3 mb-2"><strong>Allocated Qty:</strong> {{ $item->allocated_qty }}</div>
            <div class="col-md-3 mb-2"><strong>Issued Qty:</strong> {{ $issuedQty }}</div>
            <div class="col-md-3 mb-2"><strong>Remaining Qty:</strong> <span class="badge {{ $remainingQty > 0 ? 'bg-success' : 'bg-secondary' }}">{{ $remainingQty }}</span></div>
            @if(!is_null($stockAvailable))
            <div class="col-md-3 mb-2"><strong>Asset Stock Available:</strong> <span class="badge {{ $stockAvailable > 0 ? 'bg-primary' : 'bg-danger' }}">{{ $stockAvailable }}</span></div>
            @endif
        </div>
        <p class="text-muted small mb-0 mt-2">Issuing to an employee reduces Asset Stock for this item type.</p>
    </div>

    <div class="master-form-card mb-4">
        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">Issue To Employee</h5>
        <form method="POST" action="{{ route('it-consumables.issue-store', $item->id) }}" autocomplete="off">
            @csrf
            <div class="row">
                <div class="col-md-5 mb-3">
                    <label class="form-label">Employee <span class="text-danger">*</span></label>
                    <input type="hidden" name="employee_id" id="employee_id" value="{{ old('employee_id') }}">
                    <div class="position-relative">
                        <input type="text" id="employee_search" class="form-control" placeholder="Type employee name or ID..."
                               value="{{ old('employee_display') }}" {{ $remainingQty <= 0 ? 'disabled' : '' }} required autocomplete="off">
                        <div id="employee_suggestions" class="list-group position-absolute w-100 shadow" style="z-index: 20; display: none; max-height: 220px; overflow:auto;"></div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Issue Qty <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" min="1" max="{{ $remainingQty }}" class="form-control" value="{{ old('quantity', 1) }}" {{ $remainingQty <= 0 ? 'disabled' : '' }} required>
                    <small class="text-muted">Max: {{ $remainingQty }}</small>
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
            <h5 style="color: white; margin: 0;"><i class="bi bi-clock-history me-2"></i>Issue History — Who Got What</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Employee</th>
                            <th>Qty</th>
                            <th>Issue Date</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($item->issues as $issue)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <strong>{{ $issue->employee->name ?? $issue->issue_to_name }}</strong>
                                    @if($issue->employee && $issue->employee->employee_id)
                                        <div class="small text-muted">{{ $issue->employee->employee_id }}</div>
                                    @endif
                                </td>
                                <td>{{ $issue->quantity }}</td>
                                <td>{{ optional($issue->issue_date)->format('d-m-Y') }}</td>
                                <td>{{ $issue->remarks ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No issues created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('employee_search');
    const hiddenId = document.getElementById('employee_id');
    const box = document.getElementById('employee_suggestions');
    if (!searchInput || !hiddenId || !box) return;

    let timer = null;
    searchInput.addEventListener('input', function () {
        hiddenId.value = '';
        clearTimeout(timer);
        const q = searchInput.value.trim();
        if (q.length < 2) {
            box.style.display = 'none';
            box.innerHTML = '';
            return;
        }
        timer = setTimeout(function () {
            fetch('{{ route('employees.autocomplete') }}?query=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(rows => {
                    if (!Array.isArray(rows) || rows.length === 0) {
                        box.innerHTML = '<div class="list-group-item text-muted">No employees found</div>';
                        box.style.display = 'block';
                        return;
                    }
                    box.innerHTML = rows.map(function (emp) {
                        const label = (emp.name || '') + (emp.employee_id ? ' (' + emp.employee_id + ')' : '');
                        return '<a href="#" class="list-group-item list-group-item-action emp-pick" data-id="' + emp.id + '" data-label="' + label.replace(/"/g, '&quot;') + '">' + label + '</a>';
                    }).join('');
                    box.style.display = 'block';
                })
                .catch(function () {
                    box.style.display = 'none';
                });
        }, 250);
    });

    box.addEventListener('click', function (e) {
        const a = e.target.closest('.emp-pick');
        if (!a) return;
        e.preventDefault();
        hiddenId.value = a.getAttribute('data-id');
        searchInput.value = a.getAttribute('data-label');
        box.style.display = 'none';
    });

    document.addEventListener('click', function (e) {
        if (!box.contains(e.target) && e.target !== searchInput) {
            box.style.display = 'none';
        }
    });
});
</script>
@endsection
