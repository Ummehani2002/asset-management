@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="mb-0">Edit Budget Expense</h2>
        <a href="{{ route('budget-expenses.create') }}" class="btn btn-outline-secondary">Back to New Expense</a>
    </div>

    <div id="flash-placeholder">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
                <a href="{{ route('budget-expenses.print', $expense->id) }}" target="_blank" class="btn btn-sm btn-outline-light ms-3"><i class="bi bi-printer me-1"></i>Print</a>
            </div>
        @endif
    </div>

    <form id="expenseForm" method="POST" action="{{ route('budget-expenses.update', $expense->id) }}" autocomplete="off">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">Entity</label>
                <p class="form-control-plaintext fw-bold">{{ $budget->employee->entity_name ?? 'N/A' }}</p>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Expense Type</label>
                <p class="form-control-plaintext fw-bold">{{ $budget->expense_type }}</p>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">Cost Head</label>
                <p class="form-control-plaintext fw-bold">{{ $expense->cost_head ? ucfirst($expense->cost_head) : ($budget->cost_head ? ucfirst($budget->cost_head) : '—') }}</p>
            </div>
        </div>

        <div class="card mt-3 mb-3">
            <div class="card-body">
                <h5>Budget Summary</h5>
                <div class="row">
                    <div class="col-md-3">
                        <p>Budget Amount: <span class="fw-bold">{{ number_format($budgetAmount, 2) }}</span></p>
                    </div>
                    <div class="col-md-3">
                        <p>Total Expenses: <span class="fw-bold">{{ number_format($totalExpensesAll, 2) }}</span></p>
                    </div>
                    <div class="col-md-3">
                        <p>This Expense (with VAT): <span class="fw-bold" id="total_with_vat_display">{{ number_format($expense->expense_amount, 2) }}</span></p>
                    </div>
                    <div class="col-md-3">
                        <p>Available Balance: <span class="fw-bold">{{ number_format($availableBalance, 2) }}</span></p>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="entity_budget_id" value="{{ $budget->id }}">

        <div class="mb-3">
            <label class="d-flex align-items-center gap-2">
                <input type="checkbox" id="is_contracting" name="is_contracting" value="1" class="form-check-input" {{ ($expense->vat_percent ?? 5) == 15 ? 'checked' : '' }}>
                <span>Contracting company (15% VAT)</span>
            </label>
            <small class="text-muted">If unchecked, 5% VAT applies.</small>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="expense_amount">Amount (before VAT)</label>
                <input type="number" step="0.01" id="expense_amount" name="expense_amount" class="form-control" required value="{{ old('expense_amount', $amountBeforeVat) }}" placeholder="Amount excluding VAT">
            </div>
            <div class="col-md-6 mb-3">
                <label for="expense_date">Expense Date</label>
                <input type="date" id="expense_date" name="expense_date" class="form-control" required value="{{ old('expense_date', $expense->expense_date ? date('Y-m-d', strtotime($expense->expense_date)) : '') }}">
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <p class="mb-0"><strong>VAT (<span id="vat_percent_label">{{ ($expense->vat_percent ?? 5) }}</span>%):</strong> <span id="vat_amount_preview">{{ number_format($expense->vat_amount ?? 0, 2) }}</span></p>
            </div>
            <div class="col-md-4">
                <p class="mb-0"><strong>Total (deducted from balance):</strong> <span id="total_with_vat_preview">{{ number_format($expense->expense_amount ?? 0, 2) }}</span></p>
            </div>
        </div>

        <div class="mb-3">
            <label for="cost_head">Cost Head <small class="text-muted">(optional override)</small></label>
            <select name="cost_head" id="cost_head" class="form-control">
                <option value="">— Same as budget —</option>
                @foreach($costHeads as $ch)
                    <option value="{{ $ch }}" {{ old('cost_head', $expense->cost_head) == $ch ? 'selected' : '' }}>{{ $ch }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control">{{ old('description', $expense->description) }}</textarea>
        </div>

        <div class="no-print d-flex align-items-center gap-2">
            <button type="submit" class="btn btn-primary">Update Expense</button>
            <a href="{{ route('budget-expenses.print', $expense->id) }}" target="_blank" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Print
            </a>
            <a href="{{ route('budget-expenses.create') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isContractingCheck = document.getElementById('is_contracting');
    const expenseAmount = document.getElementById('expense_amount');
    const vatPercentLabel = document.getElementById('vat_percent_label');
    const vatAmountPreview = document.getElementById('vat_amount_preview');
    const totalWithVatPreview = document.getElementById('total_with_vat_preview');
    const totalWithVatDisplay = document.getElementById('total_with_vat_display');

    function getVatPercent() { return isContractingCheck.checked ? 15 : 5; }
    function updateVatPreview() {
        const amount = parseFloat(expenseAmount.value) || 0;
        const vatPct = getVatPercent();
        const vatAmount = Math.round(amount * vatPct / 100 * 100) / 100;
        const total = amount + vatAmount;
        vatPercentLabel.textContent = vatPct;
        vatAmountPreview.textContent = vatAmount.toFixed(2);
        totalWithVatPreview.textContent = total.toFixed(2);
        if (totalWithVatDisplay) totalWithVatDisplay.textContent = total.toFixed(2);
    }
    expenseAmount.addEventListener('input', updateVatPreview);
    isContractingCheck.addEventListener('change', updateVatPreview);
    updateVatPreview();
});
</script>
@endsection
