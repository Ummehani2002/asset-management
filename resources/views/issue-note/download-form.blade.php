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
        .signature { width: 200px; height: 100px; border: 1px solid #ddd; margin: 10px 0; display: block; }
        .signature-block { margin-top: 20px; }
        .footer-note { margin-top: 30px; padding-top: 15px; border-top: 1px solid #999; font-size: 12px; color: #333; text-align: center; font-weight: 500; }
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
            <td><strong>Serial Number (Asset)</strong></td>
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
    
    @if(!empty($userSigBase64))
        <div class="signature-block">
            <strong>User Signature:</strong><br>
            <img src="data:image/png;base64,{{ $userSigBase64 }}" class="signature" alt="User Signature" />
        </div>
    @endif
    @if(!empty($managerSigBase64))
        <div class="signature-block">
            <strong>IT Manager Signature:</strong><br>
            <img src="data:image/png;base64,{{ $managerSigBase64 }}" class="signature" alt="Manager Signature" />
        </div>
    @endif

    <p style="margin-top: 20px; font-size: 12px; color: #666;">
        Generated on: {{ date('Y-m-d H:i:s') }}
    </p>
    <div class="footer-note">
        {{ $footerNote ?? 'This is auto-generated. Do not reply.' }}
    </div>
</body>
</html>
