<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Budget Expense - {{ $entity_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            padding: 12px;
            margin: 0;
            color: #333;
            max-width: 210mm;
            margin: 0 auto;
        }
        .budget-block {
            border: 1px solid #999;
            padding: 10px 12px;
            margin: 0;
        }
        h2 {
            font-size: 12pt;
            color: #1F2A44;
            border-bottom: 1px solid #C6A87D;
            padding-bottom: 4px;
            margin: 0 0 10px 0;
        }
        .budget-lines {
            font-size: 10pt;
            line-height: 1.5;
            margin-bottom: 10px;
        }
        .budget-lines .line {
            display: flex;
            margin: 3px 0;
        }
        .budget-lines .label {
            min-width: 130px;
            font-weight: 600;
        }
        .budget-lines .value { }
        h3 {
            font-size: 10pt;
            margin: 8px 0 4px 0;
            color: #1F2A44;
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 10pt;
        }
        th, td {
            border: 1px solid #999;
            padding: 5px 8px;
            text-align: left;
        }
        th {
            background-color: #1F2A44;
            color: white;
            font-size: 10pt;
            font-weight: 600;
        }
        .footer {
            margin-top: 8px;
            font-size: 9pt;
            color: #666;
        }
        @media print {
            /* Shift content slightly upward for better alignment on paper */
            body { padding: 6px; max-width: none; font-size: 10pt; }
            .budget-block { border: 1px solid #333; padding: 6px 10px; }
            h2 { font-size: 12pt; }
            .budget-lines, table { font-size: 10pt; }
            h2 { margin-bottom: 6px; }
            table { margin: 4px 0; }
            table { break-inside: avoid; }
            th, td { padding: 4px 6px; }
        }
    </style>
</head>
<body>
    <div class="budget-block">
    <h2>Budget Expense</h2>

    <div class="budget-lines">
        <div class="line"><span class="label">Entity:</span><span class="value">{{ $entity_name }}</span></div>
        <div class="line"><span class="label">Expense Type:</span><span class="value">{{ $expense_type }}</span></div>
        <div class="line"><span class="label">Cost Head:</span><span class="value">{{ $cost_head }}</span></div>
        <div class="line"><span class="label">Total Budget:</span><span class="value">{{ $budget_amount }}</span></div>
        <div class="line"><span class="label">Used Budget:</span><span class="value">{{ $total_expenses }}</span></div>
        <div class="line"><span class="label">Remaining Balance:</span><span class="value">{{ $available_balance }}</span></div>
    </div>

    <h3>Expense Details (this expense)</h3>
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

    <p class="footer">Generated on: {{ date('Y-m-d H:i:s') }}</p>
    </div>

    @if(!empty($autoPrint))
        <script>window.onload = function() { window.print(); };</script>
    @endif
</body>
</html>
