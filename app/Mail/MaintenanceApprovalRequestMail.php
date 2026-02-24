<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\MaintenanceApprovalRequest;

class MaintenanceApprovalRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $approvalRequest;

    public function __construct(MaintenanceApprovalRequest $approvalRequest)
    {
        $this->approvalRequest = $approvalRequest;
    }

    public function build()
    {
        $asset = $this->approvalRequest->asset;
        $serial = $asset ? ($asset->serial_number . ' (' . ($asset->asset_id ?? '') . ')') : 'Asset';

        return $this->subject('Maintenance Approval Request: ' . $serial)
            ->view('emails.maintenance_approval_request')
            ->with([
                'approveUrl' => \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'asset-transactions.maintenance-approval-approve-signed',
                    now()->addDays(7),
                    ['id' => $this->approvalRequest->id]
                ),
                'rejectUrl' => \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'asset-transactions.maintenance-approval-reject-signed',
                    now()->addDays(7),
                    ['id' => $this->approvalRequest->id]
                ),
            ]);
    }
}
