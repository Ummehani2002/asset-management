@extends('layouts.app')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="mb-0"><i class="bi bi-clock-history me-2"></i>Expense History</h2>
        <a href="{{ route('budget-expenses.create') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to New Expense</a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('budget-expenses.history') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="entity_id" class="form-label">Entity</label>
                    <select name="entity_id" id="entity_id" class="form-select" required>
                        <option value="">Select Entity</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}" {{ request('entity_id') == $entity->id ? 'selected' : '' }}>{{ $entity->entity_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="expense_type" class="form-label">Expense Type</label>
                    <select name="expense_type" id="expense_type" class="form-select" required>
                        <option value="">Select Type</option>
                        @foreach($expenseTypes as $t)
                            <option value="{{ $t }}" {{ request('expense_type') == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="cost_head" class="form-label">Cost Head</label>
                    <select name="cost_head" id="cost_head" class="form-select" required>
                        <option value="">Select Cost Head</option>
                        @if(request('expense_type') && isset($costHeadsByType[request('expense_type')]))
                            @foreach($costHeadsByType[request('expense_type')] as $ch)
                                <option value="{{ $ch }}" {{ request('cost_head') == $ch ? 'selected' : '' }}>{{ $ch }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i>Show History</button>
                </div>
            </form>
        </div>
    </div>

    @if($entityName !== null && $costHead !== null && $expenseType !== null)
        <div class="card mb-3">
            <div class="card-body py-2">
                <strong>Entity:</strong> {{ $entityName }} &nbsp;|&nbsp; <strong>Cost Head:</strong> {{ $costHead }} &nbsp;|&nbsp; <strong>Expense Type:</strong> {{ $expenseType }}
                &nbsp;|&nbsp; <strong>Budget (current year):</strong> {{ number_format($budgetAmount, 2) }} &nbsp;|&nbsp; <strong>Total expenses (all cost heads):</strong> {{ number_format($totalExpensesAll ?? 0, 2) }}
            </div>
        </div>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Entity</th>
                                <th>Cost Head</th>
                                <th>Expense Type</th>
                                <th>Amount (incl. VAT)</th>
                                <th>Description</th>
                                <th>Balance After</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenses as $exp)
                                <tr>
                                    <td>{{ $exp['expense_date'] }}</td>
                                    <td>{{ $exp['entity_name'] }}</td>
                                    <td>{{ $exp['cost_head'] }}</td>
                                    <td>{{ $exp['expense_type'] }}</td>
                                    <td>{{ $exp['expense_amount'] }}</td>
                                    <td>{{ $exp['description'] }}</td>
                                    <td>{{ $exp['balance_after'] }}</td>
                                    <td>
                                        <a href="{{ route('budget-expenses.edit', $exp['id']) }}" class="btn btn-sm btn-outline-primary me-1">Edit</a>
                                        <a href="{{ route('budget-expenses.print', $exp['id']) }}" target="_blank" class="btn btn-sm btn-outline-secondary me-1">Print</a>
                                        <form action="{{ route('budget-expenses.destroy', $exp['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this expense?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">No expenses found for this entity, cost head and expense type.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>Select Entity, Expense Type and Cost Head above, then click <strong>Show History</strong> to view all expenses.
        </div>
    @endif
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const costHeadsByType = @json($costHeadsByType ?? []);
    const expenseTypeSelect = document.getElementById('expense_type');
    const costHeadSelect = document.getElementById('cost_head');
    function populateCostHeads() {
        const type = expenseTypeSelect.value;
        costHeadSelect.innerHTML = '<option value="">Select Cost Head</option>';
        if (type && costHeadsByType[type]) {
            costHeadsByType[type].forEach(function(name) {
                const opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                if (name === '{{ request('cost_head') }}') opt.selected = true;
                costHeadSelect.appendChild(opt);
            });
        }
    }
    expenseTypeSelect.addEventListener('change', populateCostHeads);
    populateCostHeads();
});
</script>
@endsection
