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
        .summary p { margin: 4px 0; }
    </style>
</head>
<body>
    <h2>Budget Expense</h2>

    <div class="summary">
        <p><strong>Entity:</strong> {{ $entity_name }}</p>
        <p><strong>Expense Type:</strong> {{ $expense_type }}</p>
        <p><strong>Cost Head:</strong> {{ $cost_head }}</p>
        <p><strong>Budget Amount:</strong> {{ $budget_amount }}</p>
        <p><strong>Total Expenses:</strong> {{ $total_expenses }}</p>
        <p><strong>Available Balance:</strong> {{ $available_balance }}</p>
    </div>

    <h3>Expenses</h3>
    <table>
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
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['expense_date'] }}</td>
                    <td>{{ $row['entity_name'] }}</td>
                    <td>{{ $row['cost_head'] }}</td>
                    <td>{{ $row['expense_type'] }}</td>
                    <td>{{ $row['expense_amount'] }}</td>
                    <td>{{ $row['description'] }}</td>
                    <td>{{ $row['balance_after'] }}</td>
                </tr>
                @if(!empty($row['amount_before_vat']))
                <tr class="vat-breakdown" style="background-color: #f9f9f9;">
                    <td colspan="4" style="border: none; padding-left: 24px;"><strong>VAT breakdown:</strong></td>
                    <td style="border: none;">Amount (excl. VAT): {{ $row['amount_before_vat'] }} | VAT ({{ $row['vat_percent'] }}%): {{ $row['vat_amount'] }} | Total: {{ $row['expense_amount'] }}</td>
                    <td colspan="2" style="border: none;"></td>
                </tr>
                @endif
            @empty
                <tr><td colspan="7" class="text-center">No expenses</td></tr>
            @endforelse
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
