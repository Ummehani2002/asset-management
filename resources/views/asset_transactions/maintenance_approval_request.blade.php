@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Maintenance Approval Request</h2>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Request details</h5>
            <table class="table table-bordered">
                <tr>
                    <th style="width: 35%;">Asset</th>
                    <td><strong>{{ $request->asset->serial_number ?? 'N/A' }} ({{ $request->asset->asset_id ?? 'N/A' }})</strong> — {{ $request->asset->assetCategory->category_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Requested by</th>
                    <td>{{ $request->requestedByUser->name ?? 'User' }} ({{ $request->requestedByUser->email ?? '' }})</td>
                </tr>
                @if($request->request_notes)
                <tr>
                    <th>Notes</th>
                    <td>{{ $request->request_notes }}</td>
                </tr>
                @endif
            </table>

            <p class="text-muted">As the asset manager for this entity, you can approve or reject this request. If you approve, the requester will be able to fill the maintenance details and submit.</p>

            <div class="d-flex gap-2 mt-3">
                <form action="{{ route('asset-transactions.maintenance-approval-request-approve', $request->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>Approve
                    </button>
                </form>
                <form action="{{ route('asset-transactions.maintenance-approval-request-reject', $request->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Reject this request?');">
                    @csrf
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle me-2"></i>Reject
                    </button>
                </form>
                <a href="{{ route('asset-transactions.maintenance') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </div>
</div>
@endsection
