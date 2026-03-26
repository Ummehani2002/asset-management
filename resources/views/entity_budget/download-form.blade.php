<!DOCTYPE html>
<html>
<head>
    <title>Budget - {{ $budget->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .print-area { width: 100%; }
        h2 { color: #1F2A44; border-bottom: 2px solid #C6A87D; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #1F2A44; color: white; }

        @media print {
            /*
             * Move budget details down to fit the blank lower area
             * of the pre-printed PR sheet.
             */
            body { padding: 0 20px 20px 20px; }
            .print-area { margin-top: 320px; }
        }
    </style>
</head>
<body>
    <div class="print-area">
    <h2>Entity Budget Form - {{ $currentYear }}</h2>
    
    {{-- Summary box designed to sit in the blank area of the PR form --}}
    <table>
        <thead>
            <tr>
                <th>Entity</th>
                <th>Expense Type</th>
                <th>Cost Head</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $budget->employee->entity_name ?? 'N/A' }}</td>
                <td>{{ $budget->expense_type ?? 'N/A' }}</td>
                <td>{{ $budget->cost_head ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>Budget {{ $currentYear }}</th>
                <th>Total Expenses</th>
                <th>Available Balance</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalExpenses = $budget->expenses->sum('expense_amount');
                $available = $budgetAmount - $totalExpenses;
            @endphp
            <tr>
                <td>{{ number_format($budgetAmount, 2) }}</td>
                <td>{{ number_format($totalExpenses, 2) }}</td>
                <td>{{ number_format($available, 2) }}</td>
            </tr>
        </tbody>
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
    </div>
</body>
</html>
