<!DOCTYPE html>
<html>
<head>
    <title>Budget Expense - {{ $entity_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h2 { color: #1F2A44; border-bottom: 2px solid #C6A87D; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #1F2A44; color: white; }
        .summary { margin-bottom: 20px; }
        .summary p { margin: 5px 0; }
        .summary-grid { display: flex; flex-wrap: wrap; gap: 10px 30px; }
        .summary-item { min-width: 200px; }
    </style>
</head>
<body>
    <h2>Budget Expense</h2>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-item"><p><strong>Entity:</strong> {{ $entity_name }}</p></div>
            <div class="summary-item"><p><strong>Expense Type:</strong> {{ $expense_type }}</p></div>
            <div class="summary-item"><p><strong>Cost Head:</strong> {{ $cost_head }}</p></div>
            <div class="summary-item"><p><strong>Budget Amount:</strong> {{ $budget_amount }}</p></div>
            <div class="summary-item"><p><strong>Total Expenses:</strong> {{ $total_expenses }}</p></div>
            <div class="summary-item"><p><strong>Available Balance:</strong> {{ $available_balance }}</p></div>
        </div>
    </div>

    <h3>Expense Details</h3>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Amount (excl. VAT)</th>
                <th>VAT</th>
                <th>Total Amount</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @if(count($rows) > 0)
                @php $row = $rows[0]; @endphp
                <tr>
                    <td>{{ $row['expense_date'] }}</td>
                    <td>{{ $row['amount_before_vat'] ?? $row['expense_amount'] }}</td>
                    <td>{{ $row['vat_amount'] ?? '0.00' }}</td>
                    <td>{{ $row['expense_amount'] }}</td>
                    <td>{{ $row['description'] }}</td>
                </tr>
            @else
                <tr><td colspan="5" class="text-center">No expense</td></tr>
            @endif
        </tbody>
    </table>

    <p style="margin-top: 24px; font-size: 12px; color: #666;">
        Generated on: {{ date('Y-m-d H:i:s') }}
    </p>
    @if(!empty($autoPrint))
        <script>window.onload = function() { window.print(); };</script>
    @endif
</body>
</html>
