@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h2 class="mb-0">New Budget Expense</h2>
    </div>

    <div id="flash-placeholder">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
    </div>

    <form id="expenseForm" method="POST" action="{{ route('budget-expenses.store') }}" autocomplete="off">
        @csrf
        
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="entity_id">Entity</label>
                <select id="entity_id" name="entity_id" class="form-control" required>
                    <option value="">Select Entity</option>
                    @foreach($entities as $entity)
                        <option value="{{ $entity->id }}">{{ $entity->entity_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label for="expense_type">Expense Type</label>
                <select id="expense_type" name="expense_type" class="form-control" required>
                    <option value="">Select Type</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Capex Software">Capex Software</option>
                    <option value="Capex Hardware">Capex Hardware</option>
                    <option value="Subscription">Subscription</option>
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label for="cost_head">Cost Head</label>
                <select name="cost_head" id="cost_head" class="form-control" required>
                    <option value="">Select Expense Type first</option>
                </select>
            </div>
        </div>

        <div class="card mt-3 mb-3">
            <div class="card-body">
                <h5>Budget Details</h5>
                <div class="row">
                    <div class="col-md-3">
                        <p>Budget Amount: <span id="budget_amount" class="fw-bold">0</span></p>
                    </div>
                    <div class="col-md-3">
                        <p>Total Expenses: <span id="total_expenses" class="fw-bold">0</span></p>
                    </div>
                    <div class="col-md-3">
                        <p>New Expense: <span id="new_expense" class="fw-bold">0</span></p>
                    </div>
                    <div class="col-md-3">
                        <p>Available Balance: <span id="available_balance" class="fw-bold">0</span></p>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" id="entity_budget_id" name="entity_budget_id">

        <div class="mb-3">
            <label class="d-flex align-items-center gap-2">
                <input type="checkbox" id="is_contracting" name="is_contracting" value="1" class="form-check-input">
                <span>Contracting company (15% VAT)</span>
            </label>
            <small class="text-muted">If unchecked, 5% VAT applies.</small>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="expense_amount">Amount (before VAT)</label>
                <input type="number" step="0.01" id="expense_amount" name="expense_amount" class="form-control" required placeholder="Amount excluding VAT">
            </div>
            <div class="col-md-6 mb-3">
                <label for="expense_date">Expense Date</label>
                <input type="date" id="expense_date" name="expense_date" class="form-control" required>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-4">
                <p class="mb-0"><strong>VAT (<span id="vat_percent_label">5</span>%):</strong> <span id="vat_amount_preview">0.00</span></p>
            </div>
            <div class="col-md-4">
                <p class="mb-0"><strong>Total (deducted from balance):</strong> <span id="total_with_vat_preview">0.00</span></p>
            </div>
        </div>

        <div class="mb-3">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control"></textarea>
        </div>

        <div class="no-print">
            <button type="submit" class="btn btn-primary">Save Expense</button>
            <button type="button" class="btn btn-secondary ms-2" onclick="resetForm(this)">
                <i class="bi bi-x-circle me-2"></i>Cancel
            </button>
        </div>
    </form>

    <div class="mt-4">
        <h4>Latest Expense</h4>
        <table class="table table-striped" id="expenses_table">
            <thead>
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
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const costHeadsWithTypes = @json($costHeadsWithTypes ?? []);
    // Build map: expense type -> list of cost head names (for filtering Cost Head by Expense Type)
    const costHeadsByExpenseType = {};
    Object.entries(costHeadsWithTypes).forEach(function(entry) {
        const name = entry[0], type = entry[1];
        if (!costHeadsByExpenseType[type]) costHeadsByExpenseType[type] = [];
        costHeadsByExpenseType[type].push(name);
    });
    const entitySelect = document.getElementById('entity_id');
    const costHeadSelect = document.getElementById('cost_head');
    const expenseTypeSelect = document.getElementById('expense_type');
    const expenseAmount = document.getElementById('expense_amount');
    const entityBudgetId = document.getElementById('entity_budget_id');
    const budgetAmountEl = document.getElementById('budget_amount');
    const totalExpensesEl = document.getElementById('total_expenses');
    const availableBalanceEl = document.getElementById('available_balance');
    const newExpenseEl = document.getElementById('new_expense');
    const tbody = document.querySelector('#expenses_table tbody');
    const form = document.getElementById('expenseForm');
    const isContractingCheck = document.getElementById('is_contracting');
    const vatPercentLabel = document.getElementById('vat_percent_label');
    const vatAmountPreview = document.getElementById('vat_amount_preview');
    const totalWithVatPreview = document.getElementById('total_with_vat_preview');
    const detailsUrl = "{{ route('budget-expenses.get-details') }}";
    const storeUrl = form.action;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]').value;
    const flashPlaceholder = document.getElementById('flash-placeholder');

    function renderFlash(message, type = 'success', printUrl = null) {
        let html = `<div class="alert alert-${type}">${message}`;
        if (printUrl) {
            html += ` <a href="${printUrl}" target="_blank" class="btn btn-sm btn-outline-light ms-3"><i class="bi bi-printer me-1"></i>Print</a>`;
        }
        html += '</div>';
        flashPlaceholder.innerHTML = html;
        setTimeout(() => flashPlaceholder.innerHTML = '', 8000);
    }

    async function fetchDetails() {
        const entity_id = entitySelect.value;
        const cost_head = costHeadSelect.value;
        const expense_type = expenseTypeSelect.value;

        if (!entity_id || !cost_head || !expense_type) {
            entityBudgetId.value = '';
            budgetAmountEl.textContent = '0';
            totalExpensesEl.textContent = '0';
            availableBalanceEl.textContent = '0';
            tbody.innerHTML = '';
            return null;
        }

        const url = `${detailsUrl}?entity_id=${encodeURIComponent(entity_id)}&cost_head=${encodeURIComponent(cost_head)}&expense_type=${encodeURIComponent(expense_type)}`;
        
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            
            if (!data || !data.success) {
                // No budget found or error
                entityBudgetId.value = '';
                budgetAmountEl.textContent = '0';
                totalExpensesEl.textContent = '0';
                availableBalanceEl.textContent = '0';
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">' + (data.message || 'No budget found') + '</td></tr>';
                updateBalance();
                console.log('Budget not found:', data.message || 'Unknown error');
                return null;
            }

            entityBudgetId.value = data.entity_budget_id || '';
            budgetAmountEl.textContent = data.budget_amount ?? '0';
            totalExpensesEl.textContent = data.total_expenses ?? '0';
            availableBalanceEl.textContent = data.available_balance ?? '0';
            renderExpenses(data.expenses || [], data);
            updateBalance();
            return data;
        } catch (error) {
            console.error('Error fetching budget details:', error);
            entityBudgetId.value = '';
            budgetAmountEl.textContent = '0';
            totalExpensesEl.textContent = '0';
            availableBalanceEl.textContent = '0';
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error loading budget details</td></tr>';
            updateBalance();
            return null;
        }
    }

    const editExpenseBase = "{{ url('budget-expenses') }}";
    function renderExpenses(expenses, meta = {}) {
        tbody.innerHTML = '';
        if (!expenses || expenses.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center">No expenses found</td></tr>';
            return;
        }
        expenses.forEach(exp => {
            const editUrl = exp.id ? (editExpenseBase + '/' + exp.id + '/edit') : '#';
            const printUrl = exp.id ? (editExpenseBase + '/' + exp.id + '/print') : '#';
            tbody.innerHTML += `
                <tr>
                    <td>${exp.expense_date}</td>
                    <td>${meta.entity_name ?? exp.entity_name ?? ''}</td>
                    <td>${meta.cost_head ?? exp.cost_head ?? ''}</td>
                    <td>${meta.expense_type ?? exp.expense_type ?? ''}</td>
                    <td>${exp.expense_amount}</td>
                    <td>${exp.description ?? '-'}</td>
                    <td>${exp.balance_after}</td>
                    <td>${exp.id ? `<a href="${editUrl}" class="btn btn-sm btn-outline-primary me-1">Edit</a><a href="${printUrl}" target="_blank" class="btn btn-sm btn-outline-secondary">Print</a>` : ''}</td>
                </tr>
            `;
        });
    }

    function getVatPercent() { return isContractingCheck.checked ? 15 : 5; }
    function getTotalWithVat() {
        const amount = parseFloat(expenseAmount.value) || 0;
        const vatPct = getVatPercent();
        return Math.round((amount * (1 + vatPct / 100)) * 100) / 100;
    }

    function updateVatPreview() {
        const amount = parseFloat(expenseAmount.value) || 0;
        const vatPct = getVatPercent();
        const vatAmount = Math.round(amount * vatPct / 100 * 100) / 100;
        const total = amount + vatAmount;
        vatPercentLabel.textContent = vatPct;
        vatAmountPreview.textContent = vatAmount.toFixed(2);
        totalWithVatPreview.textContent = total.toFixed(2);
    }

    function updateBalance() {
        const totalNewExpense = getTotalWithVat();
        const budgetAmount = parseFloat(String(budgetAmountEl.textContent).replace(/[,]/g, '')) || 0;
        const totalExpenses = parseFloat(String(totalExpensesEl.textContent).replace(/[,]/g, '')) || 0;
        newExpenseEl.textContent = totalNewExpense.toFixed(2);
        availableBalanceEl.textContent = (budgetAmount - totalExpenses - totalNewExpense).toFixed(2);
        updateVatPreview();
    }

    function populateCostHeads() {
        const type = expenseTypeSelect.value;
        const currentVal = costHeadSelect.value;
        costHeadSelect.innerHTML = '<option value="">Select Cost Head</option>';
        if (type && costHeadsByExpenseType[type]) {
            costHeadsByExpenseType[type].forEach(function(name) {
                const opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                if (name === currentVal) opt.selected = true;
                costHeadSelect.appendChild(opt);
            });
        }
    }
    expenseTypeSelect.addEventListener('change', function() {
        populateCostHeads();
        costHeadSelect.value = '';
        fetchDetails();
    });
    entitySelect.addEventListener('change', fetchDetails);
    costHeadSelect.addEventListener('change', fetchDetails);
    expenseAmount.addEventListener('input', updateBalance);
    isContractingCheck.addEventListener('change', updateBalance);
    updateVatPreview();

    // submit via AJAX so we can show success and update table without redirect
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        await fetchDetails(); // ensure entity_budget_id is set

        if (!entityBudgetId.value) {
            renderFlash('No budget found for selected Entity / Cost Head / Expense Type', 'danger');
            return;
        }

        const formData = new FormData(form);
        formData.set('entity_budget_id', entityBudgetId.value);
        formData.set('is_contracting', isContractingCheck.checked ? '1' : '0');

        try {
            const res = await fetch(storeUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: formData
            });
            const data = await res.json();

            // controller returns getBudgetDetails structure on success
            if (data && (data.entity_budget_id || data.success)) {
                // use response to update UI
                const details = data;
                entityBudgetId.value = details.entity_budget_id ?? entityBudgetId.value;
                budgetAmountEl.textContent = details.budget_amount ?? budgetAmountEl.textContent;
                totalExpensesEl.textContent = details.total_expenses ?? totalExpensesEl.textContent;
                availableBalanceEl.textContent = details.available_balance ?? availableBalanceEl.textContent;
                renderExpenses(details.expenses || [], details);

                
                expenseAmount.value = '';
                document.getElementById('expense_date').value = '';
                document.getElementById('description').value = '';
                isContractingCheck.checked = false;

                updateBalance();
                renderFlash('Expense saved successfully.', 'success', data.print_url || null);
            } else {
                const msg = data.message || 'Error saving expense';
                renderFlash(msg, 'danger');
            }
        } catch (err) {
            console.error(err);
            renderFlash('Error saving expense', 'danger');
        }
    });
});
</script>
@endsection
