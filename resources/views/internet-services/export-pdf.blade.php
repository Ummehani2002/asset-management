<!DOCTYPE html>
<html>
<head>
    <title>Internet Services Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; color: #1f2937; }
        h2 { margin: 0 0 6px 0; color: #111827; }
        .meta { margin: 0 0 14px 0; color: #4b5563; font-size: 10px; }
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
    <h2>Internet Services Report</h2>
    <p class="meta">Generated on: {{ date('Y-m-d H:i:s') }}</p>

    @forelse($internetServices as $index => $service)
        <div class="record">
            <div class="record-header">
                Service #{{ $index + 1 }} - {{ $service->account_number ?? 'N/A' }}
            </div>
            <table>
                <tbody>
                    <tr><th>Project</th><td>{{ $service->project_name ?? 'N/A' }}</td></tr>
                    <tr><th>Entity</th><td>{{ $service->entity ?? 'N/A' }}</td></tr>
                    <tr><th>Service Type</th><td>{{ ucfirst($service->service_type ?? 'N/A') }}</td></tr>
                    <tr><th>Bandwidth</th><td>{{ $service->bandwidth ?? 'N/A' }}</td></tr>
                    <tr><th>Transaction Type</th><td>{{ ucfirst($service->transaction_type ?? 'N/A') }}</td></tr>
                    <tr><th>Account Number</th><td>{{ $service->account_number ?? 'N/A' }}</td></tr>
                    <tr><th>Service Start Date</th><td>{{ $service->service_start_date ? $service->service_start_date->format('Y-m-d') : 'N/A' }}</td></tr>
                    <tr><th>Service End Date</th><td>{{ $service->service_end_date ? $service->service_end_date->format('Y-m-d') : 'Ongoing' }}</td></tr>
                    <tr><th>Person in Charge</th><td>{{ $service->person_in_charge ?? 'N/A' }}</td></tr>
                    <tr><th>PM Contact Number</th><td>{{ $service->pm_contact_number ?? 'N/A' }}</td></tr>
                    <tr><th>Document Controller</th><td>{{ $service->document_controller ?? 'N/A' }}</td></tr>
                    <tr><th>Document Controller Number</th><td>{{ $service->document_controller_number ?? 'N/A' }}</td></tr>
                    <tr><th>MRC</th><td>{{ number_format($service->mrc ?? 0, 2) }}</td></tr>
                    <tr><th>Cost</th><td>{{ number_format($service->cost ?? 0, 2) }}</td></tr>
                    <tr><th>PR Number</th><td>{{ $service->pr_number ?? 'N/A' }}</td></tr>
                    <tr><th>PO Number</th><td>{{ $service->po_number ?? 'N/A' }}</td></tr>
                    <tr><th>Status</th><td>{{ ucfirst($service->status ?? 'N/A') }}</td></tr>
                </tbody>
            </table>
        </div>
    @empty
        <div class="empty">No internet service records found for the selected filters.</div>
    @endforelse
</body>
</html>

