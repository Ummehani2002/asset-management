@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <h2><i class="bi bi-journal-check me-2"></i>PR Tracking Master</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="master-form-card">
        <h5 class="mb-3" style="color: var(--primary); font-weight: 600;">Add PR Tracking Record</h5>
        <form method="POST" action="{{ route('pr-tracking.store') }}" autocomplete="off">
            @csrf
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Requisition Date <span class="text-danger">*</span></label>
                    <input type="date" name="requisition_date" class="form-control" value="{{ old('requisition_date') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Requisition Number <span class="text-danger">*</span></label>
                    <input type="text" name="requisition_number" class="form-control" value="{{ old('requisition_number') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Item Requested <span class="text-danger">*</span></label>
                    <input type="text" name="item_requested" class="form-control" value="{{ old('item_requested') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Requisition Received Date</label>
                    <input type="date" name="requisition_received_date" class="form-control" value="{{ old('requisition_received_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Requisition Status</label>
                    <input type="text" name="requisition_status" class="form-control" value="{{ old('requisition_status') }}" placeholder="e.g. Approved">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Approved Request Status</label>
                    <div class="input-group">
                        <input type="text" name="approved_request_status" class="form-control" value="{{ old('approved_request_status') }}" placeholder="e.g. LPO Issued">
                        <button type="submit" name="submit_action" value="send_for_approval" class="btn btn-primary">
                            <i class="bi bi-send me-1"></i>Send for Approval
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Forwarded To Purchase Date</label>
                    <input type="date" name="forwarded_to_purchase_date" class="form-control" value="{{ old('forwarded_to_purchase_date') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Approvers</label>
                    <input type="text" class="form-control" value="{{ $defaultApproverOne }}, {{ $defaultApproverTwo }}" readonly>
                </div>
                <div class="col-md-12">
                    <label class="form-label">Comments</label>
                    <textarea name="comments" class="form-control" rows="2">{{ old('comments') }}</textarea>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="submit_action" value="save_draft" class="btn btn-success w-100">
                        <i class="bi bi-plus-circle me-1"></i>Save
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="master-table-card">
        <div class="card-header">
            <h5 style="color: white; margin: 0;"><i class="bi bi-list-ul me-2"></i>PR Tracking List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Requisition Date</th>
                            <th>Requisition Number</th>
                            <th>Item Requested</th>
                            <th>Requisition Received Date</th>
                            <th>Requisition Status</th>
                            <th>Approved Request Status</th>
                            <th>Forwarded To Purchase Date</th>
                            <th>Comments</th>
                            <th>Approver 1</th>
                            <th>Approver 2</th>
                            <th>Overall Approval</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ optional($record->requisition_date)->format('d-m-Y') ?? '-' }}</td>
                                <td>{{ $record->requisition_number }}</td>
                                <td>{{ $record->item_requested }}</td>
                                <td>{{ optional($record->requisition_received_date)->format('d-m-Y') ?? '-' }}</td>
                                <td>{{ $record->requisition_status ?? '-' }}</td>
                                <td>{{ $record->approved_request_status ?? '-' }}</td>
                                <td>{{ optional($record->forwarded_to_purchase_date)->format('d-m-Y') ?? '-' }}</td>
                                <td>{{ $record->comments ?? '-' }}</td>
                                <td>
                                    <div>{{ $record->approver_one_email ?? '-' }}</div>
                                    <span class="badge {{ $record->approver_one_status === 'approved' ? 'bg-success' : ($record->approver_one_status === 'rejected' ? 'bg-danger' : 'bg-secondary') }}">
                                        {{ ucfirst(str_replace('_', ' ', $record->approver_one_status ?? 'pending')) }}
                                    </span>
                                </td>
                                <td>
                                    <div>{{ $record->approver_two_email ?? '-' }}</div>
                                    <span class="badge {{ $record->approver_two_status === 'approved' ? 'bg-success' : ($record->approver_two_status === 'rejected' ? 'bg-danger' : 'bg-secondary') }}">
                                        {{ ucfirst(str_replace('_', ' ', $record->approver_two_status ?? 'pending')) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge
                                        @if($record->approval_status === 'approved') bg-success
                                        @elseif($record->approval_status === 'rejected') bg-danger
                                        @elseif($record->approval_status === 'partially_approved') bg-warning text-dark
                                        @elseif($record->approval_status === 'pending_approval') bg-info text-dark
                                        @else bg-secondary
                                        @endif">
                                        {{ ucfirst(str_replace('_', ' ', $record->approval_status ?? 'draft')) }}
                                    </span>
                                </td>
                                <td>
                                    @if($record->approval_status !== 'approved')
                                        <form method="POST" action="{{ route('pr-tracking.request-approval', $record->id) }}" class="d-inline" onsubmit="return confirm('Send approval request email to both approvers and Umme Hani?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-send me-1"></i>Send for Approval
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-success fw-semibold">Approved by all</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="text-center text-muted py-4">No PR tracking records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
