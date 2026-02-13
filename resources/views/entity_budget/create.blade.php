
@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-cash-coin me-2"></i>Entity Budget Management</h2>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            @if(session('saved_budget_id'))
                <a href="{{ route('entity_budget.download-form', session('saved_budget_id')) }}" class="btn btn-sm btn-outline-light ms-3">
                    <i class="bi bi-download me-1"></i>Download Form (PDF)
                </a>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Filter Section: Entity dropdown + Expense Type & Cost Head filters + Search button --}}
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-funnel me-2"></i>Filter by Entity, Year, Expense Type & Cost Head</h5>
        <form method="GET" action="{{ route('entity_budget.create') }}" id="filterForm" autocomplete="off">
            <div class="row">
                <div class="col-md-3">
                    <label for="filter_entity_id" class="form-label">Entity</label>
                    <select name="entity_id" id="filter_entity_id" class="form-control">
                        <option value="">-- All Entities --</option>
                        @foreach($entities as $entity)
                            <option value="{{ $entity->id }}" {{ request('entity_id') == $entity->id ? 'selected' : '' }}>
                                {{ $entity->entity_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="year" class="form-label">Year</label>
                    <input type="number" name="year" id="year" class="form-control" placeholder="e.g. 2026" value="{{ request('year', $selectedYear) }}" min="{{ date('Y') }}" max="{{ date('Y') + 10 }}">
                </div>
                <div class="col-md-2">
                    <label for="filter_expense_type" class="form-label">Expense Type</label>
                    <select name="filter_expense_type" id="filter_expense_type" class="form-control">
                        <option value="">-- All Types --</option>
                        @foreach($expenseTypes as $type)
                            <option value="{{ $type }}" {{ request('filter_expense_type') == $type ? 'selected' : '' }}>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="filter_cost_head" class="form-label">Cost Head</label>
                    <select name="filter_cost_head" id="filter_cost_head" class="form-control">
                        <option value="">-- All Cost Heads --</option>
                        @foreach($costHeads as $head)
                            <option value="{{ $head }}" {{ request('filter_cost_head') == $head ? 'selected' : '' }}>{{ $head }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i>Search
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Add Budget Form --}}
    <div class="master-form-card mb-4">
        <h5 class="mb-3"><i class="bi bi-plus-circle me-2"></i> New Budget</h5>
        <form action="{{ route('entity_budget.store') }}" method="POST" autocomplete="off">
            @csrf
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="entity_id">Entity</label>
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
                    <label for="expense_type">Expense Type</label>
                    <select name="expense_type" id="expense_type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Capex Software">Capex Software</option>
                        <option value="Capex Hardware">Capex Hardware</option>
                        <option value="Subscription">Subscription</option>
                    </select>
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

        <button type="submit" class="btn btn-primary">Save Budget</button>
        <button type="button" class="btn btn-secondary ms-2" onclick="resetForm(this)">
            <i class="bi bi-x-circle me-2"></i>Cancel
        </button>
    </form>

    {{-- Budgets Table: when filter/search was applied (entity + optional expense type & cost head) --}}
    @if(request()->filled('entity_id') || request()->filled('filter_expense_type') || request()->filled('filter_cost_head') || $budgets->count() > 0)
        <div class="master-table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 style="color: white; margin: 0;">
                    <i class="bi bi-list-ul me-2"></i>Budgets
                    @if(request()->filled('entity_id'))
                        @php
                            $selectedEntity = $entities->firstWhere('id', request('entity_id'));
                        @endphp
                        - {{ $selectedEntity ? $selectedEntity->entity_name : 'Selected Entity' }}
                    @endif
                    ({{ $budgets->count() }})
                </h5>
                @if($budgets->count() > 0)
                    @php
                        $exportParams = [
                            'year' => request('year', $selectedYear),
                            'entity_id' => request('entity_id'),
                            'filter_expense_type' => request('filter_expense_type'),
                            'filter_cost_head' => request('filter_cost_head'),
                        ];
                        $exportParams = array_filter($exportParams);
                    @endphp
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" id="downloadDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download"></i> Download
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="downloadDropdown">
                            <li><a class="dropdown-item" href="{{ route('entity_budget.export', array_merge($exportParams, ['format' => 'pdf'])) }}">
                                <i class="bi bi-file-pdf me-2"></i>PDF
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('entity_budget.export', array_merge($exportParams, ['format' => 'csv'])) }}">
                                <i class="bi bi-file-earmark-spreadsheet me-2"></i>CSV
                            </a></li>
                        </ul>
                    </div>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Entity</th>
                                <th>Expense Type</th>
                                <th>Cost Head</th>
                                <th>Budget {{ $selectedYear }}</th>
                                <th>Total Expenses</th>
                                <th>Available Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($budgets as $index => $budget)
                            @php
                                $yearColumn = 'budget_' . $selectedYear;
                                $budgetAmount = $budget->$yearColumn ?? 0;
                                $totalExpenses = $budget->expenses->sum('expense_amount');
                                $availableBalance = $budgetAmount - $totalExpenses;
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $budget->employee->entity_name ?? 'N/A' }}</td>
                                <td>{{ $budget->expense_type }}</td>
                                <td>{{ $budget->cost_head ?? '—' }}</td>
                                <td>{{ number_format($budgetAmount, 2) }}</td>
                                <td>{{ number_format($totalExpenses, 2) }}</td>
                                <td>{{ number_format($availableBalance, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No budgets found for the selected filters. Try different entity, expense type or cost head, or add a new budget.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
       
    @endif
</div>

@endsection