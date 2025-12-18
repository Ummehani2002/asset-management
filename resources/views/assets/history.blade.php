@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Asset History - {{ $asset->asset_id }} ({{ $asset->category->category_name }} - {{ $asset->brand->name }})</h2>

    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>Issued To</th>
                <th>Location</th>
                <th>Transaction Type</th>
                <th>Issue Date</th>
                <th>Return Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($history as $txn)
                <tr>
                    <td>{{ $txn->employee->name ?? 'N/A' }}</td>
                    <td>
                        @if($txn->location_id && $txn->location)
                            {{ $txn->location->location_name }}
                        @else
                            @php
                                // Try to get location from asset's latest transaction
                                $latestTxn = \App\Models\AssetTransaction::where('asset_id', $txn->asset_id)
                                    ->whereNotNull('location_id')
                                    ->with('location')
                                    ->latest('issue_date')
                                    ->first();
                            @endphp
                            {{ $latestTxn && $latestTxn->location ? $latestTxn->location->location_name : 'N/A' }}
                        @endif
                    </td>
                    <td>{{ $txn->transaction_type }}</td>
                    <td>{{ $txn->issue_date }}</td>
                    <td>{{ $txn->return_date ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No transaction history found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <a href="{{ route('assets.index') }}" class="btn btn-secondary mt-3">← Back to Assets</a>
</div>
@endsection
