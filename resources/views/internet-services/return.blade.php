@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Return Internet Service</h3>

    <div class="card mb-3">
        <div class="card-body">
            <h5>Service Details</h5>
            <p><strong>Project:</strong> {{ $internetService->project_name ?? 'N/A' }}</p>
            <p><strong>Account Number:</strong> {{ $internetService->account_number ?? 'N/A' }}</p>
            <p><strong>Service Type:</strong> {{ ucfirst($internetService->service_type ?? 'N/A') }}</p>
            <p><strong>Start Date:</strong> {{ $internetService->service_start_date->format('d-m-Y') }}</p>
            <p><strong>MRC (Cost Per Day):</strong> {{ number_format($internetService->mrc ?? 0, 2) }} per day</p>
        </div>
    </div>

    <form action="{{ route('internet-services.process-return', $internetService->id) }}" method="POST">
        @csrf

        <div class="mb-3">
            <label class="form-label">End Date <span class="text-danger">*</span></label>
            <input type="date" name="service_end_date" id="service_end_date" class="form-control" required
                   min="{{ $internetService->service_start_date->format('Y-m-d') }}"
                   value="{{ old('service_end_date', date('Y-m-d')) }}">
        </div>

        {{-- Cost (Auto-calculated) --}}
        <div class="mb-3">
            <label class="form-label">Cost (Auto-calculated)</label>
            <input type="number" name="cost" id="cost" class="form-control" step="0.01" min="0" readonly>
            <small class="text-muted" id="cost_info">Select end date to calculate cost</small>
        </div>

        <button type="submit" class="btn btn-primary">Return Service</button>
        <a href="{{ route('internet-services.index') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mrc = {{ $internetService->mrc ?? 0 }};
    const startDate = '{{ $internetService->service_start_date->format('Y-m-d') }}';
    const endDateInput = document.getElementById('service_end_date');
    const costInput = document.getElementById('cost');
    const costInfo = document.getElementById('cost_info');
    
    function calculateCost() {
        const endDate = endDateInput.value;
        
        if (!endDate) {
            costInput.value = '';
            if (costInfo) {
                costInfo.textContent = 'Select end date to calculate cost';
                costInfo.className = 'text-muted';
            }
            return;
        }
        
        if (!mrc || mrc <= 0) {
            costInput.value = '';
            if (costInfo) {
                costInfo.textContent = 'MRC is not set for this service';
                costInfo.className = 'text-warning';
            }
            return;
        }
        
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (end < start) {
            costInput.value = '';
            if (costInfo) {
                costInfo.textContent = 'End date must be after start date';
                costInfo.className = 'text-danger';
            }
            return;
        }
        
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end days
        
        // Calculate cost: MRC (per day) × number of days
        const cost = mrc * diffDays;
        
        costInput.value = cost.toFixed(2);
        
        if (costInfo) {
            costInfo.textContent = `Cost calculated: ${diffDays} days × MRC ${mrc.toFixed(2)} per day = ${cost.toFixed(2)}`;
            costInfo.className = 'text-success';
        }
    }
    
    endDateInput.addEventListener('change', calculateCost);
    
    // Calculate on page load if end date is pre-filled
    if (endDateInput.value) {
        calculateCost();
    }
});
</script>
@endsection

