
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

    {{-- Add Budget Form --}}
    <div class="master-form-card mb-4 budget-form-printable">
        <h5 class="mb-3"><i class="bi bi-plus-circle me-2"></i> New Budget</h5>
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
                            <option value="{{ $type }}" {{ old('expense_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
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