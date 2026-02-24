<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #1F2A44 0%, #2C3E66 100%); color: white; padding: 25px 20px; text-align: center; }
        .header h2 { margin: 0; font-size: 22px; }
        .content { padding: 25px; }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #e0e0e0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; background: #fafafa; border-radius: 6px; overflow: hidden; }
        table td { padding: 10px 15px; border-bottom: 1px solid #e0e0e0; }
        table tr:last-child td { border-bottom: none; }
        table td:first-child { font-weight: 600; width: 40%; color: #1F2A44; background: #f0f0f0; }
        .btn { display: inline-block; padding: 12px 24px; margin: 10px 5px 0 0; text-decoration: none; border-radius: 6px; font-weight: 600; color: white !important; }
        .btn-approve { background: #4CAF50; }
        .btn-reject { background: #f44336; }
        .link-box { margin: 20px 0; padding: 15px; background: #E3F2FD; border-left: 4px solid #2196F3; border-radius: 4px; }
        .link-box p { margin: 0 0 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Maintenance Approval Request</h2>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>A user has requested your approval to send an asset for maintenance. Details below:</p>

            <table>
                <tr>
                    <td>Asset</td>
                    <td><strong>{{ $approvalRequest->asset->serial_number ?? 'N/A' }} ({{ $approvalRequest->asset->asset_id ?? 'N/A' }})</strong></td>
                </tr>
                <tr>
                    <td>Requested by</td>
                    <td>{{ $approvalRequest->requestedByUser->name ?? 'User' }} ({{ $approvalRequest->requestedByUser->email ?? '' }})</td>
                </tr>
                @if($approvalRequest->request_notes)
                <tr>
                    <td>Notes</td>
                    <td>{{ $approvalRequest->request_notes }}</td>
                </tr>
                @endif
            </table>

            <div class="link-box">
                <p><strong>Click one of the buttons below to approve or reject (no login required):</strong></p>
                <p>
                    <a href="{{ $approveUrl }}" class="btn btn-approve">Approve</a>
                    <a href="{{ $rejectUrl }}" class="btn btn-reject">Reject</a>
                </p>
                <p style="font-size: 12px; color: #666;">Links are valid for 7 days. After you approve, the requester can fill the maintenance form and submit.</p>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} Asset Management System.</p>
        </div>
    </div>
</body>
</html>
