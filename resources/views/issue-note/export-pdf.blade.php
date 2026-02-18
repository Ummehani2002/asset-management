<!DOCTYPE html>
<html>
<head>
    <title>Issue Notes Report - {{ ucfirst($noteType !== 'all' ? $noteType : 'All') }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #1F2A44; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        h2 { color: #333; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 10px; }
        .badge-issue { background-color: #0d6efd; color: white; }
        .badge-return { background-color: #198754; color: white; }
    </style>
</head>
<body>
    <h2>Issue Notes & Return Notes Report</h2>
    <p><strong>Type:</strong> {{ ucfirst($noteType !== 'all' ? $noteType : 'All Notes') }}</p>
    <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    <p><strong>Total Notes:</strong> {{ count($issueNotes) }}</p>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Type</th>
                <th>Employee</th>
                <th>Department</th>
                <th>Entity</th>
                <th>Location</th>
                <th>Serial Number</th>
                <th>Printer Code</th>
                <th>Issued Date</th>
                <th>Return Date</th>
                <th>Items</th>
                <th>Software Installed</th>
            </tr>
        </thead>
        <tbody>
            @foreach($issueNotes as $index => $note)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        @if($note->note_type == 'return')
                            <span class="badge badge-return">Return</span>
                        @else
                            <span class="badge badge-issue">Issue</span>
                        @endif
                    </td>
                    <td>{{ $note->employee->name ?? $note->employee->entity_name ?? 'N/A' }}</td>
                    <td>{{ $note->department ?? 'N/A' }}</td>
                    <td>{{ $note->entity ?? 'N/A' }}</td>
                    <td>{{ $note->location ?? 'N/A' }}</td>
                    <td>{{ $note->system_code ?? 'N/A' }}</td>
                    <td>{{ $note->printer_code ?? 'N/A' }}</td>
                    <td>{{ $note->issued_date ? $note->issued_date->format('Y-m-d') : 'N/A' }}</td>
                    <td>{{ $note->return_date ? $note->return_date->format('Y-m-d') : 'N/A' }}</td>
                    <td>
                        @if(is_array($note->items) && count($note->items) > 0)
                            {{ implode(', ', $note->items) }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ $note->software_installed ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <p style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 11px; color: #666; text-align: center;">
        This is generated from the system. Do not reply.
    </p>
</body>
</html>

