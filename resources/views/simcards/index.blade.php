@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="bi bi-sim me-2"></i>SIM Card Transactions</h2>
        <p>View all SIM card transaction history</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="master-table-card">
        <div class="card-header">
            <h5 style="color: white; margin: 0;"><i class="bi bi-list-ul me-2"></i>Transaction History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>SIM Number</th>
                            <th>Transaction Type</th>
                            <th>Project</th>
                            <th>Entity</th>
                            <th>MRC</th>
                            <th>Issue Date</th>
                            <th>Return Date</th>
                            <th>PM/DC</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                            <tr>
                                <td>{{ $loop->iteration + ($transactions->currentPage()-1) * $transactions->perPage() }}</td>
                                <td>{{ $txn->simcard_number }}</td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ ucfirst($txn->transaction_type) }}
                                    </span>
                                </td>
                                <td>{{ $txn->project_name ?? 'N/A' }}</td>
                                <td>{{ $txn->entity ?? 'N/A' }}</td>
                                <td>{{ $txn->mrc ?? 'N/A' }}</td>
                                <td>{{ $txn->issue_date ?? 'N/A' }}</td>
                                <td>{{ $txn->return_date ?? 'N/A' }}</td>
                                <td>{{ $txn->pm_dc ?? 'N/A' }}</td>
                                <td>{{ $txn->created_at->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No transactions found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transactions->hasPages())
            <div class="card-footer" style="background: #f8f9fa; padding: 12px 20px; border-top: 1px solid #dee2e6;">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
