@extends('layouts.app')
@section('content')
<div class="container-fluid master-page">
    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="mb-0"><i class="bi bi-journal-text me-2"></i>Transaction History</h2>
        <a href="{{ route('entity_budget.create') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Entity Budget</a>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Filter: Entity + Expense Type + Year --}}
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filter Transactions</h5>
        <form method="GET" action="{{ route('entity_budget.transaction-history') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="entity_id" class="form-label">Entity</label>
                <select name="entity_id" id="entity_id" class="form-select">
                    <option value="">All Entities</option>
                    @foreach($entities as $entity)
                        <option value="{{ $entity->id }}" {{ $selectedEntityId == $entity->id ? 'selected' : '' }}>{{ $entity->entity_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="expense_type" class="form-label">Expense Type</label>
                <select name="expense_type" id="expense_type" class="form-select">
                    <option value="">All</option>
                    @if(isset($expenseTypes))
                        @foreach($expenseTypes as $type)
                            <option value="{{ $type }}" {{ request('expense_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-3">
                <label for="year" class="form-label">Year</label>
                <select name="year" id="year" class="form-select">
                    @foreach($availableYears as $y)
                        <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Apply</button>
            </div>
        </form>
    </div>

    @if($expenseRows->count() > 0 || request()->hasAny(['entity_id', 'expense_type', 'year']))
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 no-print">
            <h5 class="mb-0">
                @if($entityName){{ $entityName }}@else All Entities @endif
                @if(request('expense_type')) — {{ request('expense_type') }}@endif
                — {{ $selectedYear }} ({{ $expenseRows->count() }} expense(s))
            </h5>
            <div class="d-flex gap-2">
                <a href="{{ route('entity_budget.transaction-history.print', array_merge(request()->only(['entity_id', 'expense_type', 'year']))) }}" target="_blank" class="btn btn-outline-primary">
                    <i class="bi bi-printer me-1"></i>Print
                </a>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download me-1"></i>Download
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('entity_budget.transaction-history.download', array_merge(request()->only(['entity_id', 'expense_type', 'year']), ['format' => 'pdf'])) }}">PDF</a></li>
                        <li><a class="dropdown-item" href="{{ route('entity_budget.transaction-history.download', array_merge(request()->only(['entity_id', 'expense_type', 'year']), ['format' => 'csv'])) }}">CSV</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Entity</th>
                                <th>Cost Head</th>
                                <th>Expense Type</th>
                                <th>Budget</th>
                                <th>Amount</th>
                                <th>Spent (cumulative)</th>
                                <th>Balance after</th>
                                <th>Description</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenseRows as $i => $row)
                                @php $e = $row->expense; @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $e->expense_date ? \Carbon\Carbon::parse($e->expense_date)->format('d-M-Y') : '—' }}</td>
                                    <td>{{ $e->entityBudget && $e->entityBudget->employee ? $e->entityBudget->employee->entity_name : 'N/A' }}</td>
                                    <td>{{ $e->cost_head ?? '—' }}</td>
                                    <td>{{ $e->entityBudget->expense_type ?? '—' }}</td>
                                    <td>{{ number_format($row->budget_amount, 2) }}</td>
                                    <td>{{ number_format($row->amount, 2) }}</td>
                                    <td>{{ number_format($row->cumulative_spent, 2) }}</td>
                                    <td>{{ number_format($row->balance_after, 2) }}</td>
                                    <td>{{ Str::limit($e->description ?? '—', 50) }}</td>
                                    <td class="no-print">
                                        <a href="{{ route('budget-expenses.edit', $e->id) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                        <a href="{{ route('budget-expenses.print', $e->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Print</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="11" class="text-center text-muted py-4">No expenses for this entity in {{ $selectedYear }}.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>Select an entity and year above, then click <strong>Show List</strong> to view transaction history.
        </div>
    @endif
</div>
@endsection
