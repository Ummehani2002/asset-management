<?php

namespace App\Mail;

use App\Models\PrTracking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class PrTrackingApprovalRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public PrTracking $prTracking;
    public string $approverKey;

    public function __construct(PrTracking $prTracking, string $approverKey)
    {
        $this->prTracking = $prTracking;
        $this->approverKey = $approverKey;
    }

    public function build()
    {
        return $this->subject('PR Approval Request: ' . $this->prTracking->requisition_number)
            ->view('emails.pr_tracking_approval_request')
            ->with([
                'approveUrl' => URL::temporarySignedRoute(
                    'pr-tracking.approve-signed',
                    now()->addDays(7),
                    ['id' => $this->prTracking->id, 'approver' => $this->approverKey]
                ),
                'rejectUrl' => URL::temporarySignedRoute(
                    'pr-tracking.reject-signed',
                    now()->addDays(7),
                    ['id' => $this->prTracking->id, 'approver' => $this->approverKey]
                ),
            ]);
    }
}

