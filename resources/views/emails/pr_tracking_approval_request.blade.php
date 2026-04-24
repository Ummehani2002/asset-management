<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background: #f5f5f5; }
        .container { max-width: 640px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; }
        .header { background: #1F2A44; color: #fff; padding: 18px 20px; }
        .content { padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        td { border: 1px solid #e5e7eb; padding: 8px; vertical-align: top; }
        td:first-child { width: 40%; font-weight: 600; background: #f9fafb; }
        .btn { display: inline-block; padding: 10px 16px; margin-right: 8px; text-decoration: none; border-radius: 6px; color: #fff !important; font-weight: 600; }
        .btn-approve { background: #16a34a; }
        .btn-reject { background: #dc2626; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0;">PR Approval Request</h2>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>Please review and approve this PR tracking request:</p>

            <table>
                <tr><td>Requisition Date</td><td>{{ optional($prTracking->requisition_date)->format('d-m-Y') ?? 'N/A' }}</td></tr>
                <tr><td>Requisition Number</td><td>{{ $prTracking->requisition_number }}</td></tr>
                <tr><td>Item Requested</td><td>{{ $prTracking->item_requested }}</td></tr>
                <tr><td>Requisition Received Date</td><td>{{ optional($prTracking->requisition_received_date)->format('d-m-Y') ?? 'N/A' }}</td></tr>
                <tr><td>Requisition Status</td><td>{{ $prTracking->requisition_status ?? 'N/A' }}</td></tr>
                <tr><td>Approved Request Status</td><td>{{ $prTracking->approved_request_status ?? 'N/A' }}</td></tr>
                <tr><td>Forwarded To Purchase Date</td><td>{{ optional($prTracking->forwarded_to_purchase_date)->format('d-m-Y') ?? 'N/A' }}</td></tr>
                <tr><td>Comments</td><td>{{ $prTracking->comments ?? '-' }}</td></tr>
            </table>

            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin-top: 14px; margin-bottom: 10px;">
                <tr>
                    <td style="border: none; padding: 0 8px 0 0; background: transparent;">
                        <a href="{{ $approveUrl }}"
                           style="display: inline-block; background-color: #16a34a; color: #ffffff !important; text-decoration: none; font-weight: 700; border-radius: 6px; padding: 10px 18px; border: 1px solid #15803d;">
                            Approve
                        </a>
                    </td>
                    <td style="border: none; padding: 0; background: transparent;">
                        <a href="{{ $rejectUrl }}"
                           style="display: inline-block; background-color: #dc2626; color: #ffffff !important; text-decoration: none; font-weight: 700; border-radius: 6px; padding: 10px 18px; border: 1px solid #b91c1c;">
                            Reject
                        </a>
                    </td>
                </tr>
            </table>
            <p style="font-size: 12px; color: #6b7280; margin-top: 6px;">
                If buttons are not clickable, use links below:
                <br>
                Approve: <a href="{{ $approveUrl }}" style="color: #2563eb; word-break: break-all;">{{ $approveUrl }}</a>
                <br>
                Reject: <a href="{{ $rejectUrl }}" style="color: #2563eb; word-break: break-all;">{{ $rejectUrl }}</a>
            </p>
            <p style="font-size: 12px; color: #6b7280;">Links are valid for 7 days.</p>
        </div>
    </div>
</body>
</html>

