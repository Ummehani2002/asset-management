<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { 
            background: linear-gradient(135deg, #1F2A44 0%, #2C3E66 100%); 
            color: white; 
            padding: 25px 20px; 
            text-align: center;
        }
        .header h2 { margin: 0; font-size: 22px; }
        .content { padding: 25px; }
        .footer { background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #e0e0e0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; background: #fafafa; border-radius: 6px; overflow: hidden; }
        table td { padding: 12px 15px; border-bottom: 1px solid #e0e0e0; }
        table tr:last-child td { border-bottom: none; }
        table td:first-child { font-weight: 600; width: 35%; color: #1F2A44; background: #f0f0f0; }
        table td:last-child { color: #333; }
        .info-box { 
            background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%); 
            padding: 18px; 
            border-left: 4px solid #4CAF50; 
            margin: 20px 0; 
            border-radius: 4px;
        }
        .warning-box { 
            background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%); 
            padding: 18px; 
            border-left: 4px solid #FF9800; 
            margin: 20px 0; 
            border-radius: 4px;
        }
        .contact-box { 
            background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%); 
            padding: 18px; 
            border-left: 4px solid #2196F3; 
            margin: 20px 0; 
            border-radius: 4px;
        }
        .greeting { font-size: 16px; margin-bottom: 15px; }
        .section-title { font-size: 18px; font-weight: 600; color: #1F2A44; margin: 25px 0 15px 0; padding-bottom: 8px; border-bottom: 2px solid #C6A87D; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @php
                $transactionType = $transaction->transaction_type ?? 'assign';
                $isReassignment = $transactionType === 'assign' && $transaction->maintenance_notes !== null;
                $title = match($transactionType) {
                    'assign' => $isReassignment ? '✅ Asset Ready for Collection' : '✅ Asset Assigned to You',
                    'return' => '📦 Asset Return Notification',
                    'system_maintenance' => '🔧 Asset Maintenance Notification',
                    default => '📋 Asset Transaction Notification',
                };
            @endphp
            <h2>{{ $title }}</h2>
        </div>

        <div class="content">
            <p class="greeting">Dear <strong>{{ $employee->name ?? 'Employee' }}</strong>,</p>
            
            @if($transactionType === 'assign')
                @if($isReassignment)
                    <div class="info-box">
                        <p style="margin: 0; font-size: 16px;"><strong>🎉 Great News!</strong> Your asset has completed maintenance and is now ready for collection.</p>
                    </div>
                @else
                    <p>An asset has been assigned to you. Please find the complete details below:</p>
                @endif
                
                <div class="section-title">Asset Details</div>
                <table>
                    <tr>
                        <td>Asset ID:</td>
                        <td><strong>{{ $asset->asset_id ?? 'N/A' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Category:</td>
                        <td>{{ $asset->assetCategory->category_name ?? ($asset->category ?? 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td>Brand:</td>
                        <td>{{ $asset->brand->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Serial Number:</td>
                        <td><strong>{{ $asset->serial_number ?? 'N/A' }}</strong></td>
                    </tr>
                    @if($transaction->issue_date)
                    <tr>
                        <td>Issue Date:</td>
                        <td><strong>{{ \Carbon\Carbon::parse($transaction->issue_date)->format('F d, Y') }}</strong></td>
                    </tr>
                    @endif
                    @if($transaction->location_id && $transaction->location)
                    <tr>
                        <td>Location:</td>
                        <td>{{ $transaction->location->location_name ?? 'N/A' }}</td>
                    </tr>
                    @endif
                </table>

                @if($isReassignment)
                    <div class="info-box">
                        <p style="margin: 0; font-size: 15px;"><strong>📋 Action Required:</strong> Please collect your asset from the IT department. The asset has been reassigned to you and is ready for use.</p>
                    </div>
                @endif
                
                <div class="contact-box">
                    <p style="margin: 0;"><strong>📞 Important:</strong> If you have any questions or concerns about this asset, please contact the administrator immediately.</p>
                </div>

            @elseif($transactionType === 'return')
                <p>An asset has been returned. Please find the complete asset details below:</p>
                
                <div class="section-title">Asset Details</div>
                <table>
                    <tr>
                        <td>Asset ID:</td>
                        <td><strong>{{ $asset->asset_id ?? 'N/A' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Category:</td>
                        <td>{{ $asset->assetCategory->category_name ?? ($asset->category ?? 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td>Brand:</td>
                        <td>{{ $asset->brand->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Serial Number:</td>
                        <td><strong>{{ $asset->serial_number ?? 'N/A' }}</strong></td>
                    </tr>
                    @if($asset->po_number)
                    <tr>
                        <td>PO Number:</td>
                        <td>{{ $asset->po_number }}</td>
                    </tr>
                    @endif
                    @if($asset->purchase_date)
                    <tr>
                        <td>Purchase Date:</td>
                        <td>{{ \Carbon\Carbon::parse($asset->purchase_date)->format('F d, Y') }}</td>
                    </tr>
                    @endif
                    @if($transaction->return_date)
                    <tr>
                        <td>Return Date:</td>
                        <td><strong>{{ \Carbon\Carbon::parse($transaction->return_date)->format('F d, Y') }}</strong></td>
                    </tr>
                    @endif
                    @if($transaction->remarks)
                    <tr>
                        <td>Remarks:</td>
                        <td>{{ $transaction->remarks }}</td>
                    </tr>
                    @endif
                </table>

                <div class="info-box">
                    <p style="margin: 0;"><strong>✓ Asset Returned:</strong> This asset has been successfully returned and is now available in the system.</p>
                </div>

            @elseif($transactionType === 'system_maintenance')
                <div class="warning-box">
                    <p style="margin: 0;"><strong>⚠️ Maintenance Notice:</strong> Your assigned asset has been sent for system maintenance.</p>
                </div>
                
                <div class="section-title">Asset Details</div>
                <table>
                    <tr>
                        <td>Asset ID:</td>
                        <td><strong>{{ $asset->asset_id ?? 'N/A' }}</strong></td>
                    </tr>
                    <tr>
                        <td>Category:</td>
                        <td>{{ $asset->assetCategory->category_name ?? ($asset->category ?? 'N/A') }}</td>
                    </tr>
                    <tr>
                        <td>Brand:</td>
                        <td>{{ $asset->brand->name ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td>Serial Number:</td>
                        <td><strong>{{ $asset->serial_number ?? 'N/A' }}</strong></td>
                    </tr>
                </table>

                <div class="section-title">Maintenance Schedule</div>
                <table>
                    @if($transaction->receive_date)
                    <tr>
                        <td>Collection Date:</td>
                        <td><strong>{{ \Carbon\Carbon::parse($transaction->receive_date)->format('F d, Y') }}</strong></td>
                    </tr>
                    @endif
                    @if($transaction->delivery_date)
                    <tr>
                        <td>Expected Delivery Date:</td>
                        <td><strong style="color: #FF9800;">{{ \Carbon\Carbon::parse($transaction->delivery_date)->format('F d, Y') }}</strong></td>
                    </tr>
                    @endif
                    @if($transaction->repair_type)
                    <tr>
                        <td>Repair Type:</td>
                        <td>{{ $transaction->repair_type }}</td>
                    </tr>
                    @endif
                    @if($transaction->remarks)
                    <tr>
                        <td>Remarks:</td>
                        <td>{{ $transaction->remarks }}</td>
                    </tr>
                    @endif
                </table>

                <div class="info-box">
                    <p style="margin: 0;">
                        <strong>📅 Important Dates:</strong><br>
                        @if($transaction->receive_date)
                        • Asset collected on: <strong>{{ \Carbon\Carbon::parse($transaction->receive_date)->format('F d, Y') }}</strong><br>
                        @endif
                        @if($transaction->delivery_date)
                        • Expected delivery on: <strong>{{ \Carbon\Carbon::parse($transaction->delivery_date)->format('F d, Y') }}</strong><br>
                        @endif
                       
                    </p>
                </div>
            @endif

            
        <div class="footer">
            <p>&copy; {{ date('Y') }} Tanseeq Investment - Asset Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>




