<!DOCTYPE html>
<html>
<head>
    <title>Entity Budget Report - {{ $entityName }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1F2A44; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        h2 { color: #333; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>Entity Budget Report</h2>
    <p><strong>Entity:</strong> {{ $entityName }}</p>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Budgets:</strong> {{ count($budgets) }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Entity</th>
                <th>Cost Head</th>
                <th>Expense Type</th>
                <th class="text-right">Budget 2025</th>
                <th class="text-right">Total Expenses</th>
                <th class="text-right">Available Balance</th>
            </tr>
        </thead>
        <tbody>
            @foreach($budgets as $index => $budget)
                @php
                    $totalExpenses = $budget->expenses->sum('expense_amount');
                    $availableBalance = $budget->budget_2025 - $totalExpenses;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $budget->employee->entity_name ?? 'N/A' }}</td>
                    <td>{{ ucfirst($budget->cost_head) }}</td>
                    <td>{{ $budget->expense_type }}</td>
                    <td class="text-right">{{ number_format($budget->budget_2025, 2) }}</td>
                    <td class="text-right">{{ number_format($totalExpenses, 2) }}</td>
                    <td class="text-right">{{ number_format($availableBalance, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

