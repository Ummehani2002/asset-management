<?php

namespace App\Http\Controllers;

use App\Mail\PrTrackingApprovalRequestMail;
use App\Models\PrTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PrTrackingController extends Controller
{
    /** Production flow: Ruman → Badr → completed */
    private const APPROVER_ONE_EMAIL = 'rumanmohammed@tanseeqinvestment.com';
    private const APPROVER_TWO_EMAIL = 'badruddin@tanseeqinvestment.com';

    private const APPROVER_LABELS = [
        'one' => 'Ruman Mohammed',
        'two' => 'Badruddin',
    ];

    private const CHAIN_LABEL = 'Ruman Mohammed → Badruddin';

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
            'approverLabels' => self::APPROVER_LABELS,
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
        // Legacy third-approver column: unused in current 2-step flow
        $validated['approver_three_status'] = 'approved';

        $prTracking = PrTracking::create($validated);

        if ($request->input('submit_action') === 'send_for_approval') {
            $this->prepareForApprovalRequest($prTracking);
            $prTracking->save();
            return $this->sendApprovalEmailToCurrent($prTracking, true);
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

        return $this->sendApprovalEmailToCurrent($prTracking, true);
    }

    public function approveSigned(Request $request, $id, $approver)
    {
        if (!$request->hasValidSignature()) {
            return redirect()->route('login')->with('error', 'This approval link is invalid or expired.');
        }

        $prTracking = PrTracking::findOrFail($id);
        if (!in_array($approver, PrTracking::APPROVER_KEYS, true)) {
            return redirect()->route('login')->with('error', 'Invalid approver link.');
        }

        if ($prTracking->approval_status === 'approved') {
            return redirect()->route('login')->with('success', 'This PR is already approved by all approvers.');
        }

        if ($prTracking->approval_status === 'rejected') {
            return redirect()->route('login')->with('error', 'This PR was already rejected.');
        }

        $currentKey = $prTracking->currentApproverKey();
        if ($currentKey !== $approver) {
            return redirect()->route('login')->with(
                'error',
                'This approval link is not active yet. Approvals must follow order: ' . self::CHAIN_LABEL . '.'
            );
        }

        if ($prTracking->approverStatus($approver) === 'approved') {
            return redirect()->route('login')->with('success', 'Your approval was already recorded.');
        }

        $this->setApproverStatus($prTracking, $approver, 'approved');
        $prTracking->approval_status = $this->resolveOverallApprovalStatus($prTracking);

        if ($prTracking->approval_status === 'approved') {
            $prTracking->approved_request_status = 'Approved';
            if (empty($prTracking->requisition_status)) {
                $prTracking->requisition_status = 'Approved';
            }
            $prTracking->save();

            return redirect()->route('login')->with(
                'success',
                'Approved. This PR is now fully approved (' . self::CHAIN_LABEL . ').'
            );
        }

        $prTracking->save();

        $nextKey = $prTracking->currentApproverKey();
        $mailFailed = false;
        if ($nextKey) {
            $mailFailed = !$this->dispatchApprovalEmail($prTracking, $nextKey);
        }

        $nextLabel = $nextKey ? (self::APPROVER_LABELS[$nextKey] ?? 'next approver') : 'next approver';
        if ($mailFailed) {
            return redirect()->route('login')->with(
                'warning',
                'Your approval is recorded. Email to ' . $nextLabel . ' could not be sent — ask the requester to click Send for Approval again.'
            );
        }

        return redirect()->route('login')->with(
            'success',
            'Your approval is recorded. The request was forwarded to ' . $nextLabel . '.'
        );
    }

    public function rejectSigned(Request $request, $id, $approver)
    {
        if (!$request->hasValidSignature()) {
            return redirect()->route('login')->with('error', 'This rejection link is invalid or expired.');
        }

        $prTracking = PrTracking::findOrFail($id);
        if (!in_array($approver, PrTracking::APPROVER_KEYS, true)) {
            return redirect()->route('login')->with('error', 'Invalid approver link.');
        }

        if ($prTracking->approval_status === 'approved') {
            return redirect()->route('login')->with('error', 'This PR is already fully approved and cannot be rejected.');
        }

        if ($prTracking->approval_status === 'rejected') {
            return redirect()->route('login')->with('success', 'This PR was already rejected.');
        }

        $currentKey = $prTracking->currentApproverKey();
        if ($currentKey !== $approver) {
            return redirect()->route('login')->with(
                'error',
                'This rejection link is not active. Only the current approver in sequence can act.'
            );
        }

        $this->setApproverStatus($prTracking, $approver, 'rejected');
        $prTracking->approval_status = 'rejected';
        $prTracking->approved_request_status = 'Rejected';
        $prTracking->save();

        return redirect()->route('login')->with('success', 'You rejected this PR request.');
    }

    private function setApproverStatus(PrTracking $prTracking, string $key, string $status): void
    {
        if ($key === 'one') {
            $prTracking->approver_one_status = $status;
            $prTracking->approver_one_action_at = now();
        } else {
            $prTracking->approver_two_status = $status;
            $prTracking->approver_two_action_at = now();
        }
    }

    private function resolveOverallApprovalStatus(PrTracking $prTracking): string
    {
        foreach (PrTracking::APPROVER_KEYS as $key) {
            if ($prTracking->approverStatus($key) === 'rejected') {
                return 'rejected';
            }
        }

        foreach (PrTracking::APPROVER_KEYS as $key) {
            if ($prTracking->approverStatus($key) !== 'approved') {
                return $prTracking->approver_one_status === 'approved'
                    ? 'partially_approved'
                    : 'pending_approval';
            }
        }

        return 'approved';
    }

    /**
     * Send email only to the current pending approver in the sequence.
     */
    private function sendApprovalEmailToCurrent(PrTracking $prTracking, bool $isResendOrStart = false)
    {
        $currentKey = $prTracking->currentApproverKey();
        if (!$currentKey) {
            return redirect()->route('pr-tracking.index')->with(
                'warning',
                'No pending approver found for this PR.'
            );
        }

        $ok = $this->dispatchApprovalEmail($prTracking, $currentKey);
        $label = self::APPROVER_LABELS[$currentKey] ?? $currentKey;

        if (!$ok) {
            return redirect()->route('pr-tracking.index')->with(
                'warning',
                'Could not email ' . $label . '. Please check mail configuration and try again.'
            );
        }

        $stepNote = match ($currentKey) {
            'one' => 'Step 1 of 2: email sent to Ruman Mohammed. After approval, Badruddin will be notified.',
            'two' => 'Step 2 of 2: email sent to Badruddin. After approval, the PR will be fully approved.',
            default => 'Approval email sent.',
        };

        return redirect()
            ->route('pr-tracking.index')
            ->with('success', $stepNote);
    }

    private function dispatchApprovalEmail(PrTracking $prTracking, string $approverKey): bool
    {
        $email = $prTracking->approverEmail($approverKey);
        if (empty($email)) {
            return false;
        }

        try {
            Mail::to($email)->send(new PrTrackingApprovalRequestMail($prTracking, $approverKey));
            return true;
        } catch (\Throwable $e) {
            Log::error('PR tracking approval request email failed', [
                'pr_tracking_id' => $prTracking->id,
                'approver' => $approverKey,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function prepareForApprovalRequest(PrTracking $prTracking): void
    {
        $prTracking->approver_one_email = self::APPROVER_ONE_EMAIL;
        $prTracking->approver_two_email = self::APPROVER_TWO_EMAIL;
        $prTracking->approver_three_status = 'approved'; // unused in 2-step flow

        if (in_array($prTracking->approval_status, ['draft', 'rejected'], true)) {
            $prTracking->approver_one_status = 'pending';
            $prTracking->approver_two_status = 'pending';
            $prTracking->approver_one_action_at = null;
            $prTracking->approver_two_action_at = null;
            $prTracking->approval_status = 'pending_approval';
        } else {
            $prTracking->approval_status = $this->resolveOverallApprovalStatus($prTracking);
            if ($prTracking->approval_status === 'approved') {
                return;
            }
            if ($prTracking->approval_status !== 'partially_approved') {
                $prTracking->approval_status = 'pending_approval';
            }
        }

        $prTracking->approval_requested_at = now();
        if ($prTracking->approval_status !== 'approved') {
            $prTracking->approved_request_status = 'Pending Approval';
        }
    }
}
