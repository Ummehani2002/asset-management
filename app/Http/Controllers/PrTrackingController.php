<?php

namespace App\Http\Controllers;

use App\Mail\PrTrackingApprovalRequestMail;
use App\Models\PrTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PrTrackingController extends Controller
{
    private const APPROVER_ONE_EMAIL = 'rumanmohammed@tanseeqinvestment.com';
    private const APPROVER_TWO_EMAIL = 'badruddin@tanseeqinvestment.com';
    private const CC_NOTIFICATION_EMAIL = 'umme.hani@tanseeqinvestment.com';

    public function index(Request $request)
    {
        $query = PrTracking::query()->latest();

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('requisition_number', 'like', '%' . $search . '%')
                    ->orWhere('item_requested', 'like', '%' . $search . '%')
                    ->orWhere('comments', 'like', '%' . $search . '%');
            });
        }

        $records = $query->get();

        return view('pr-tracking.index', [
            'records' => $records,
            'defaultApproverOne' => self::APPROVER_ONE_EMAIL,
            'defaultApproverTwo' => self::APPROVER_TWO_EMAIL,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'requisition_date' => 'required|date',
            'requisition_number' => 'required|string|max:100|unique:pr_trackings,requisition_number',
            'item_requested' => 'required|string|max:255',
            'requisition_received_date' => 'nullable|date',
            'requisition_status' => 'nullable|string|max:100',
            'approved_request_status' => 'nullable|string|max:100',
            'forwarded_to_purchase_date' => 'nullable|date',
            'comments' => 'nullable|string|max:1000',
        ]);

        $validated['approver_one_email'] = self::APPROVER_ONE_EMAIL;
        $validated['approver_two_email'] = self::APPROVER_TWO_EMAIL;
        $validated['approval_status'] = 'draft';
        $validated['approver_one_status'] = 'pending';
        $validated['approver_two_status'] = 'pending';

        $prTracking = PrTracking::create($validated);

        if ($request->input('submit_action') === 'send_for_approval') {
            $this->prepareForApprovalRequest($prTracking);
            $prTracking->save();
            return $this->sendApprovalEmails($prTracking);
        }

        return redirect()
            ->route('pr-tracking.index')
            ->with('success', 'PR tracking record created successfully.');
    }

    public function requestApproval(PrTracking $prTracking)
    {
        if ($prTracking->approval_status === 'approved') {
            return redirect()->route('pr-tracking.index')->with('success', 'This PR is already approved by all approvers.');
        }

        $this->prepareForApprovalRequest($prTracking);
        $prTracking->save();

        return $this->sendApprovalEmails($prTracking);
    }

    public function approveSigned(Request $request, $id, $approver)
    {
        if (!$request->hasValidSignature()) {
            return redirect()->route('login')->with('error', 'This approval link is invalid or expired.');
        }

        $prTracking = PrTracking::findOrFail($id);
        if (!in_array($approver, ['one', 'two'], true)) {
            return redirect()->route('login')->with('error', 'Invalid approver link.');
        }

        if ($prTracking->approval_status === 'approved') {
            return redirect()->route('login')->with('success', 'This PR is already approved by all approvers.');
        }

        if ($approver === 'one') {
            $prTracking->approver_one_status = 'approved';
            $prTracking->approver_one_action_at = now();
        } else {
            $prTracking->approver_two_status = 'approved';
            $prTracking->approver_two_action_at = now();
        }

        $prTracking->approval_status = $this->resolveOverallApprovalStatus($prTracking);
        if ($prTracking->approval_status === 'approved') {
            $prTracking->approved_request_status = 'Approved';
            if (empty($prTracking->requisition_status)) {
                $prTracking->requisition_status = 'Approved';
            }
        }
        $prTracking->save();

        $msg = $prTracking->approval_status === 'approved'
            ? 'Approved. This PR is now approved by all approvers.'
            : 'Your approval is recorded. Waiting for the other approver.';

        return redirect()->route('login')->with('success', $msg);
    }

    public function rejectSigned(Request $request, $id, $approver)
    {
        if (!$request->hasValidSignature()) {
            return redirect()->route('login')->with('error', 'This rejection link is invalid or expired.');
        }

        $prTracking = PrTracking::findOrFail($id);
        if (!in_array($approver, ['one', 'two'], true)) {
            return redirect()->route('login')->with('error', 'Invalid approver link.');
        }

        if ($approver === 'one') {
            $prTracking->approver_one_status = 'rejected';
            $prTracking->approver_one_action_at = now();
        } else {
            $prTracking->approver_two_status = 'rejected';
            $prTracking->approver_two_action_at = now();
        }

        $prTracking->approval_status = 'rejected';
        $prTracking->approved_request_status = 'Rejected';
        $prTracking->save();

        return redirect()->route('login')->with('success', 'You rejected this PR request.');
    }

    private function resolveOverallApprovalStatus(PrTracking $prTracking): string
    {
        if ($prTracking->approver_one_status === 'rejected' || $prTracking->approver_two_status === 'rejected') {
            return 'rejected';
        }

        if ($prTracking->approver_one_status === 'approved' && $prTracking->approver_two_status === 'approved') {
            return 'approved';
        }

        return 'partially_approved';
    }

    private function sendApprovalEmails(PrTracking $prTracking)
    {
        try {
            if (!empty($prTracking->approver_one_email)) {
                Mail::to($prTracking->approver_one_email)
                    ->cc(self::CC_NOTIFICATION_EMAIL)
                    ->send(new PrTrackingApprovalRequestMail($prTracking, 'one'));
            }
            if (!empty($prTracking->approver_two_email)) {
                Mail::to($prTracking->approver_two_email)
                    ->cc(self::CC_NOTIFICATION_EMAIL)
                    ->send(new PrTrackingApprovalRequestMail($prTracking, 'two'));
            }
        } catch (\Throwable $e) {
            Log::error('PR tracking approval request email failed', [
                'pr_tracking_id' => $prTracking->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('pr-tracking.index')->with(
                'warning',
                'Send for approval failed to email. Please check mail configuration.'
            );
        }

        return redirect()
            ->route('pr-tracking.index')
            ->with('success', 'Send for approval completed. Email sent to both approvers and Umme Hani.');
    }

    private function prepareForApprovalRequest(PrTracking $prTracking): void
    {
        if (in_array($prTracking->approval_status, ['draft', 'rejected'], true)) {
            $prTracking->approver_one_status = 'pending';
            $prTracking->approver_two_status = 'pending';
            $prTracking->approver_one_action_at = null;
            $prTracking->approver_two_action_at = null;
        }

        $prTracking->approver_one_email = self::APPROVER_ONE_EMAIL;
        $prTracking->approver_two_email = self::APPROVER_TWO_EMAIL;
        $prTracking->approval_requested_at = now();
        $prTracking->approval_status = 'pending_approval';
        $prTracking->approved_request_status = 'Pending Approval';
    }
}
