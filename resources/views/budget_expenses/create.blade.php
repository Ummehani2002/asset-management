@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Add Budget Expense</h2>

    <div id="flash-placeholder">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
    </div>

    <form id="expenseForm" method="POST" action="{{ route('budget-expenses.store') }}">
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
                <label for="cost_head">Cost Head</label>
                <select name="cost_head" id="cost_head" class="form-control" required>
                    <option value="">Select Cost Head</option>
                    @foreach($costHeads as $head)
                        <option value="{{ $head }}">{{ ucfirst($head) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 mb-3">
                <label for="expense_type">Expense Type</label>
                <select id="expense_type" name="expense_type" class="form-control" required>
                    <option value="">Select Type</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Capex Software">Capex Software</option>
                    <option value="Subscription">Subscription</option>
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

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="expense_amount">Expense Amount</label>
                <input type="number" step="0.01" id="expense_amount" name="expense_amount" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="expense_date">Expense Date</label>
                <input type="date" id="expense_date" name="expense_date" class="form-control" required>
            </div>
        </div>

        <div class="mb-3">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save Expense</button>
    </form>

    <div class="mt-4">
        <h4>Previous Expenses</h4>
        <table class="table table-striped" id="expenses_table">
            <thead>
                <tr>
                    <th>Date</th>
            <th>Entity</th>
            <th>Cost Head</th>
            <th>Expense Type</th>
            <th>Amount</th>
            <th>Description</th>
            <th>Balance After</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
    const detailsUrl = "{{ route('budget-expenses.get-details') }}";
    const storeUrl = form.action;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]').value;
    const flashPlaceholder = document.getElementById('flash-placeholder');

    function renderFlash(message, type = 'success') {
        flashPlaceholder.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
        setTimeout(() => flashPlaceholder.innerHTML = '', 4000);
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
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data) return null;

        entityBudgetId.value = data.entity_budget_id || '';
        budgetAmountEl.textContent = data.budget_amount ?? '0';
        totalExpensesEl.textContent = data.total_expenses ?? '0';
        availableBalanceEl.textContent = data.available_balance ?? '0';
        renderExpenses(data.expenses || [], data);
        updateBalance();
        return data;
    }

    function renderExpenses(expenses, meta = {}) {
        tbody.innerHTML = '';
        if (!expenses || expenses.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">No expenses found</td></tr>';
            return;
        }
        expenses.forEach(exp => {
            tbody.innerHTML += `
                <tr>
                    <td>${exp.expense_date}</td>
                    <td>${meta.entity_name ?? exp.entity_name ?? ''}</td>
                    <td>${meta.cost_head ?? exp.cost_head ?? ''}</td>
                    <td>${meta.expense_type ?? exp.expense_type ?? ''}</td>
                    <td>${exp.expense_amount}</td>
                    <td>${exp.description ?? '-'}</td>
                    <td>${exp.balance_after}</td>
                </tr>
            `;
        });
    }

    function updateBalance() {
        const newExpense = parseFloat(expenseAmount.value) || 0;
        const budgetAmount = parseFloat(String(budgetAmountEl.textContent).replace(/[,]/g, '')) || 0;
        const totalExpenses = parseFloat(String(totalExpensesEl.textContent).replace(/[,]/g, '')) || 0;
        newExpenseEl.textContent = newExpense.toFixed(2);
        availableBalanceEl.textContent = (budgetAmount - totalExpenses - newExpense).toFixed(2);
    }

    // update details when selects change
    entitySelect.addEventListener('change', fetchDetails);
    costHeadSelect.addEventListener('change', fetchDetails);
    expenseTypeSelect.addEventListener('change', fetchDetails);
    expenseAmount.addEventListener('input', updateBalance);

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

                updateBalance();
                renderFlash('Expense saved successfully.', 'success');
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
