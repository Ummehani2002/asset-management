<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History — {{ $entityName }} ({{ $year }})</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; margin: 16px; }
        h1 { font-size: 16px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .meta { color: #666; margin-bottom: 12px; }
        @media print { body { margin: 10px; } }
    </style>
    @if(!empty($autoPrint ?? false))
    <script>window.onload = function() { window.print(); }</script>
    @endif
</head>
<body>
    <h1>Transaction History</h1>
    <div class="meta"><strong>Entity:</strong> {{ $entityName }} &nbsp;|&nbsp; <strong>Year:</strong> {{ $year }} &nbsp;|&nbsp; <strong>Generated:</strong> {{ now()->format('d-M-Y H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Entity</th>
                <th>Cost Head</th>
                <th>Expense Type</th>
                <th>Budget</th>
                <th>Amount</th>
                <th>Spent (cumulative)</th>
                <th>Balance after</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenseRows as $i => $row)
                @php $e = $row->expense; @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $e->expense_date ? \Carbon\Carbon::parse($e->expense_date)->format('d-M-Y') : '—' }}</td>
                    <td>{{ $e->entityBudget && $e->entityBudget->employee ? $e->entityBudget->employee->entity_name : 'N/A' }}</td>
                    <td>{{ $e->cost_head ?? '—' }}</td>
                    <td>{{ $e->entityBudget->expense_type ?? '—' }}</td>
                    <td>{{ number_format($row->budget_amount, 2) }}</td>
                    <td>{{ number_format($row->amount, 2) }}</td>
                    <td>{{ number_format($row->cumulative_spent, 2) }}</td>
                    <td>{{ number_format($row->balance_after, 2) }}</td>
                    <td>{{ $e->description ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="10" style="text-align: center;">No expenses for this entity in {{ $year }}.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($expenseRows->isNotEmpty())
        <p style="margin-top: 12px;"><strong>Total amount (all rows):</strong> {{ number_format($expenseRows->sum('amount'), 2) }}</p>
    @endif
</body>
</html>
