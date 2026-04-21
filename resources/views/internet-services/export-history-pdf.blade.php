<!DOCTYPE html>
<html>
<head>
    <title>Service History Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1f2937; }
        h2 { margin: 0 0 6px 0; color: #111827; }
        .meta { margin: 0 0 8px 0; color: #4b5563; font-size: 10px; }
        .record {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .record-header {
            background: #1f2937;
            color: #ffffff;
            padding: 7px 10px;
            font-size: 11px;
            font-weight: 700;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }
        th {
            width: 34%;
            background-color: #f3f4f6;
            text-align: left;
            color: #111827;
            font-weight: 600;
        }
        .empty {
            border: 1px solid #d1d5db;
            padding: 16px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h2>Service History (All Types)</h2>
    <p class="meta"><strong>Account / search:</strong> {{ $accountLabel }}</p>
    <p class="meta">Generated on: {{ date('Y-m-d H:i:s') }}</p>

    @forelse($historyRows as $index => $history)
        <div class="record">
            <div class="record-header">
                Event #{{ $index + 1 }} - {{ ucfirst($history->transaction_type ?? 'N/A') }}
            </div>
            <table>
                <tbody>
                    <tr><th>Account No.</th><td>{{ $history->account_number ?? 'N/A' }}</td></tr>
                    <tr><th>Service Type</th><td>{{ ucfirst($history->service_type ?? 'N/A') }}</td></tr>
                    <tr><th>Project</th><td>{{ $history->project_name ?? 'N/A' }}</td></tr>
                    <tr><th>Person in Charge</th><td>{{ $history->person_in_charge ?? 'N/A' }}</td></tr>
                    <tr><th>Transaction</th><td>{{ ucfirst($history->transaction_type ?? 'N/A') }}</td></tr>
                    <tr><th>Start Date</th><td>{{ $history->service_start_date ? $history->service_start_date->format('Y-m-d') : 'N/A' }}</td></tr>
                    <tr><th>End Date</th><td>{{ $history->service_end_date ? $history->service_end_date->format('Y-m-d') : 'Ongoing' }}</td></tr>
                    <tr><th>Status</th><td>{{ ucfirst($history->status ?? 'N/A') }}</td></tr>
                    <tr><th>MRC</th><td>{{ number_format($history->mrc ?? 0, 2) }}</td></tr>
                    <tr><th>Cost</th><td>{{ number_format($history->cost ?? 0, 2) }}</td></tr>
                </tbody>
            </table>
        </div>
    @empty
        <div class="empty">No history found for this account/search value.</div>
    @endforelse
</body>
</html>
