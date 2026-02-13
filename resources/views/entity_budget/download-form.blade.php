<!DOCTYPE html>
<html>
<head>
    <title>Budget - {{ $budget->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h2 { color: #1F2A44; border-bottom: 2px solid #C6A87D; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #1F2A44; color: white; }
    </style>
</head>
<body>
    <h2>Entity Budget Form - {{ $currentYear }}</h2>
    
    <table>
        <tr>
            <th>Field</th>
            <th>Value</th>
        </tr>
        <tr>
            <td><strong>Entity</strong></td>
            <td>{{ $budget->employee->entity_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Expense Type</strong></td>
            <td>{{ $budget->expense_type ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Cost Head</strong></td>
            <td>{{ $budget->cost_head ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Budget {{ $currentYear }}</strong></td>
            <td>{{ number_format($budgetAmount, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total Expenses</strong></td>
            <td>{{ number_format($budget->expenses->sum('expense_amount'), 2) }}</td>
        </tr>
        <tr>
            <td><strong>Available Balance</strong></td>
            <td>{{ number_format($budgetAmount - $budget->expenses->sum('expense_amount'), 2) }}</td>
        </tr>
    </table>
    
    @if($budget->expenses->count() > 0)
        <h3 style="margin-top: 30px;">Expense Details</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Expense Date</th>
                    <th>Amount</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @foreach($budget->expenses as $index => $expense)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $expense->expense_date ? \Carbon\Carbon::parse($expense->expense_date)->format('Y-m-d') : 'N/A' }}</td>
                        <td>{{ number_format($expense->expense_amount, 2) }}</td>
                        <td>{{ $expense->description ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    
    <p style="margin-top: 30px; font-size: 12px; color: #666;">
        Generated on: {{ date('Y-m-d H:i:s') }}
    </p>
    @if(!empty($autoPrint))
        <script>window.onload = function() { window.print(); };</script>
    @endif
</body>
</html>
