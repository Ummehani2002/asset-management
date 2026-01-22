<!DOCTYPE html>
<html>
<head>
    <title>Issue Note - {{ $issueNote->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h2 { color: #1F2A44; border-bottom: 2px solid #C6A87D; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #1F2A44; color: white; }
        .signature { width: 200px; height: 100px; border: 1px solid #ddd; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>System Issue Note</h2>
    
    <table>
        <tr>
            <th>Field</th>
            <th>Value</th>
        </tr>
        <tr>
            <td><strong>Employee Name</strong></td>
            <td>{{ $issueNote->employee->name ?? $issueNote->employee->entity_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Department</strong></td>
            <td>{{ $issueNote->department ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Entity</strong></td>
            <td>{{ $issueNote->entity ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Location</strong></td>
            <td>{{ $issueNote->location ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>System Code</strong></td>
            <td>{{ $issueNote->system_code ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Printer Code</strong></td>
            <td>{{ $issueNote->printer_code ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Issued Date</strong></td>
            <td>{{ $issueNote->issued_date ? $issueNote->issued_date->format('Y-m-d') : 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Software Installed</strong></td>
            <td>{{ $issueNote->software_installed ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td><strong>Issued Items</strong></td>
            <td>
                @if($issueNote->items && is_array($issueNote->items))
                    {{ implode(', ', $issueNote->items) }}
                @else
                    N/A
                @endif
            </td>
        </tr>
    </table>
    
    @if($issueNote->user_signature)
        <div>
            <strong>User Signature:</strong><br>
            <img src="{{ $issueNote->user_signature }}" class="signature" alt="User Signature">
        </div>
    @endif
    
    @if($issueNote->manager_signature)
        <div>
            <strong>IT Manager Signature:</strong><br>
            <img src="{{ $issueNote->manager_signature }}" class="signature" alt="Manager Signature">
        </div>
    @endif
    
    <p style="margin-top: 30px; font-size: 12px; color: #666;">
        Generated on: {{ date('Y-m-d H:i:s') }}
    </p>
</body>
</html>
