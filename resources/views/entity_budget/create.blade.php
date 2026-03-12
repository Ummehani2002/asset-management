
@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2 class="mb-0"><i class="bi bi-cash-coin me-2"></i>Entity Budget Management</h2>
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

    {{-- Bulk: Set budget for all cost heads at once --}}
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-collection me-2"></i> Set Budget for All Cost Heads (One Amount)</h5>
        <form action="{{ route('entity_budget.bulk-store') }}" method="POST" autocomplete="off">
            @csrf
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="bulk_entity_id">Entity <span class="text-danger">*</span></label>
                    <select name="entity_id" id="bulk_entity_id" class="form-control" required>
                        <option value="">Select Entity</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}" {{ (request('entity_id') == $entity->id) ? 'selected' : '' }}>{{ $entity->entity_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="bulk_expense_type">Expense Type <span class="text-danger">*</span></label>
                    <select name="expense_type" id="bulk_expense_type" class="form-control" required>
                        <option value="">Select Type</option>
                        @foreach($expenseTypes as $type)
                            <option value="{{ $type }}" {{ request('expense_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="bulk_budget_year">Year <span class="text-danger">*</span></label>
                    <select name="budget_year" id="bulk_budget_year" class="form-control" required>
                        @foreach($availableYears as $year)
                            <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="bulk_budget_amount">Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="budget_amount" id="bulk_budget_amount" class="form-control" required placeholder="Same for all" min="0">
                </div>
                <div class="col-md-2 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Apply to All Cost Heads</button>
                </div>
            </div>
            <p class="text-muted small mb-0">This sets the same budget amount for every cost head under the selected expense type. Use the form below to set or change a single cost head.</p>
        </form>
    </div>

    {{-- Add Budget Form (single cost head) --}}
    <div class="master-form-card mb-4 budget-form-printable">
        <h5 class="mb-3"><i class="bi bi-plus-circle me-2"></i> New Budget (Single Cost Head)</h5>
        <form action="{{ route('entity_budget.store') }}" method="POST" autocomplete="off">
            @csrf
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="entity_id">Entity <span class="text-danger">*</span></label>
                    <select name="entity_id" id="entity_id" class="form-control" required>
                        <option value="">Select Entity</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}" {{ (request('entity_id') == $entity->id || old('entity_id') == $entity->id) ? 'selected' : '' }}>
                                {{ $entity->entity_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="expense_type">Expense Type <span class="text-danger">*</span></label>
                    <select name="expense_type" id="expense_type" class="form-control" required>
                        <option value="">Select Type</option>
                        @foreach($expenseTypes as $type)
                            <option value="{{ $type }}" {{ (old('expense_type') ?? request('expense_type')) == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="cost_head">Cost Head <span class="text-danger">*</span></label>
                    <select name="cost_head" id="cost_head" class="form-control" required>
                        <option value="">Select Expense Type first</option>
                    </select>
                    <small class="text-muted">Select expense type to see cost heads</small>
                </div>
                <input type="hidden" name="category" id="category" value="Overhead">
            </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="budget_year">Budget Year</label>
                <select name="budget_year" id="budget_year" class="form-control" required>
                    @foreach($availableYears as $year)
                        <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label for="budget_amount">Budget Amount</label>
                <input type="number" step="0.01" name="budget_amount" id="budget_amount" class="form-control" required placeholder="Enter budget amount">
            </div>
        </div>

        <div class="no-print">
            <button type="submit" class="btn btn-primary">Save Budget</button>
            <button type="button" class="btn btn-secondary ms-2" onclick="resetForm(this)">
                <i class="bi bi-x-circle me-2"></i>Cancel
            </button>
        </div>
    </form>

    {{-- Existing budgets list (when filters applied) with Delete option --}}
    @if(isset($budgets) && $budgets->isNotEmpty())
        @php $yearCol = 'budget_' . ($selectedYear ?? date('Y')); @endphp
        <div class="master-table-card mt-4 no-print">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0" style="color: white;"><i class="bi bi-list-ul me-2"></i>Existing budgets</h5>
                <span class="text-white-50 small">Filter by Entity / Expense type above to see list</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Entity</th>
                                <th>Cost Head</th>
                                <th>Expense Type</th>
                                <th>Budget ({{ $selectedYear ?? date('Y') }})</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($budgets as $budget)
                                <tr>
                                    <td>{{ $budget->employee->entity_name ?? 'N/A' }}</td>
                                    <td>{{ $budget->cost_head ?? 'N/A' }}</td>
                                    <td>{{ $budget->expense_type ?? 'N/A' }}</td>
                                    <td>{{ isset($budget->$yearCol) ? number_format($budget->$yearCol, 2) : '–' }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('entity_budget.destroy', $budget->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this budget? Any expenses under it will also be removed.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete budget">
                                                <i class="bi bi-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var costHeadsList = @json($costHeadsList ?? []);
    var expenseTypeSelect = document.getElementById('expense_type');
    var costHeadSelect = document.getElementById('cost_head');
    if (!expenseTypeSelect || !costHeadSelect) return;

    var oldCostHead = @json(old('cost_head'));

    function updateCostHeadOptions() {
        var expenseType = expenseTypeSelect.value;
        costHeadSelect.innerHTML = '<option value="">Select Cost Head</option>';
        if (!expenseType) {
            costHeadSelect.options[0].text = 'Select Expense Type first';
            return;
        }
        costHeadSelect.options[0].text = 'Select Cost Head';
        var matching = costHeadsList.filter(function(ch) { return ch.expense_type === expenseType; });
        matching.forEach(function(ch) {
            var opt = document.createElement('option');
            opt.value = ch.name;
            opt.textContent = ch.name;
            costHeadSelect.appendChild(opt);
        });
        if (oldCostHead && costHeadSelect.querySelector('option[value="' + oldCostHead.replace(/"/g, '\\"') + '"]')) {
            costHeadSelect.value = oldCostHead;
        }
    }

    expenseTypeSelect.addEventListener('change', function() {
        updateCostHeadOptions();
    });
    updateCostHeadOptions();
});
</script>
@endsection