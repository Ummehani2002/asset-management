<!DOCTYPE html>
<html>
<head>
    <title>Internet Service - {{ $internetService->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h2 { color: #1F2A44; border-bottom: 2px solid #C6A87D; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #1F2A44; color: white; }
    </style>
</head>
<body>
    <h2>Internet Service Form</h2>
    
    <table>
        <tr>
            <th>Field</th>
            <th>Value</th>
        </tr>
        <tr>
            <td><strong>Project</strong></td>
            <td>{{ $internetService->project_name ?? 'N/A' }} ({{ $internetService->entity ?? 'N/A' }})</td>
        </tr>
        <tr>
            <td><strong>Service Type</strong></td>
            <td>{{ ucfirst($internetService->service_type ?? 'N/A') }}</td>
        </tr>
        <tr>
            <td><strong>Transaction Type</strong></td>
            <td>{{ ucfirst($internetService->transaction_type ?? 'N/A') }}</td>
        </tr>
        <tr>
            <td><strong>Account Number</strong></td>
            <td>{{ $internetService->account_number ?? 'N/A' }}</td>
        </tr>
        @if($internetService->service_type == 'simcard')
        <tr>
            <td><strong>PR Number</strong></td>
            <td>{{ $internetService->pr_number ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>PO Number</strong></td>
            <td>{{ $internetService->po_number ?? 'N/A' }}</td>
        </tr>
        @endif
        <tr>
            <td><strong>Service Start Date</strong></td>
            <td>{{ $internetService->service_start_date ? $internetService->service_start_date->format('Y-m-d') : 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Service End Date</strong></td>
            <td>{{ $internetService->service_end_date ? $internetService->service_end_date->format('Y-m-d') : 'Ongoing' }}</td>
        </tr>
        <tr>
            <td><strong>MRC (Cost Per Day)</strong></td>
            <td>{{ $internetService->mrc ? number_format($internetService->mrc, 2) : 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Total Cost</strong></td>
            <td>{{ $internetService->cost ? number_format($internetService->cost, 2) : 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Person in Charge</strong></td>
            <td>{{ $internetService->person_in_charge ?? 'N/A' }} ({{ $internetService->contact_details ?? 'N/A' }})</td>
        </tr>
        <tr>
            <td><strong>Project Manager</strong></td>
            <td>{{ $internetService->project_manager ?? 'N/A' }} ({{ $internetService->pm_contact_number ?? 'N/A' }})</td>
        </tr>
        <tr>
            <td><strong>Document Controller</strong></td>
            <td>{{ $internetService->document_controller ?? 'N/A' }} ({{ $internetService->document_controller_number ?? 'N/A' }})</td>
        </tr>
        <tr>
            <td><strong>Status</strong></td>
            <td>{{ ucfirst($internetService->status ?? 'N/A') }}</td>
        </tr>
    </table>
    
    <p style="margin-top: 30px; font-size: 12px; color: #666;">
        Generated on: {{ date('Y-m-d H:i:s') }}
    </p>
</body>
</html>
