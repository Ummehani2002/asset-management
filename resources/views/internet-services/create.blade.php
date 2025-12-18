@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Add Internet Service</h3>

    <form action="{{ route('internet-services.store') }}" method="POST">
        @csrf

        {{-- Project --}}
        <div class="mb-3">
            <label class="form-label">Project</label>
            <select name="project_id" class="form-control" required>
                <option value="">-- select project --</option>
                @foreach ($projects as $p)
                    <option value="{{ $p->id }}">
                        {{ $p->project_id }} - {{ $p->project_name }} ({{ $p->entity }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Service Type --}}
        <div class="mb-3">
            <label class="form-label">Service Type</label>
            <select name="service_type" id="service_type" class="form-control" required>
                <option value="simcard">SIM Card</option>
                <option value="fixed">Fixed Service</option>
                <option value="service">Out Sourced</option>
            </select>
        </div>

        {{-- Transaction Type --}}
        <div class="mb-3">
            <label class="form-label">Transaction Type</label>
            <select name="transaction_type" class="form-control">
                <option value="">-- select transaction type --</option>
                <option value="assign">Assign</option>
                <option value="return">Return</option>
            </select>
        </div>

        {{-- PR Number and PO Number (only for SIM Card) --}}
        <div id="simcardFields" style="display: none;">
            <div class="mb-3">
                <label class="form-label">PR Number</label>
                <input type="text" name="pr_number" class="form-control" placeholder="Enter PR number">
            </div>

            <div class="mb-3">
                <label class="form-label">PO Number</label>
                <input type="text" name="po_number" class="form-control" placeholder="Enter PO number">
            </div>
        </div>

        {{-- Account Number --}}
        <div class="mb-3">
            <label class="form-label">Account Number</label>
            <input type="text" name="account_number" class="form-control">
        </div>

        {{-- MRC (Per Day Cost) --}}
        <div class="mb-3">
            <label class="form-label">MRC (Cost Per Day) <span class="text-muted">(Daily Rate)</span></label>
            <input type="number" name="mrc" id="mrc" class="form-control" step="0.01" min="0" placeholder="Enter daily cost (e.g., 10.00)">
            <small class="text-muted">Enter the cost per day. Total cost will be calculated as: Number of Days × MRC per day</small>
        </div>

        {{-- Dates --}}
        <div class="mb-3">
            <label class="form-label">Start Date</label>
            <input type="date" name="service_start_date" id="service_start_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">End Date <small class="text-muted">(Optional - can be selected later)</small></label>
            <input type="date" name="service_end_date" id="service_end_date" class="form-control">
            <small class="text-muted">Leave blank if service is still active. Cost will be calculated automatically when end date is selected.</small>
        </div>

        {{-- Cost (Auto-calculated) --}}
        <div class="mb-3">
            <label class="form-label">Cost</label>
            <input type="number" name="cost" id="cost" class="form-control" step="0.01" min="0" readonly placeholder="Will be calculated automatically when end date is selected">
            <small class="text-muted" id="cost_info">Enter MRC and select end date to calculate cost</small>
        </div>

        {{-- Person in Charge (Employee) --}}
        <div class="mb-3">
            <label class="form-label">Person in Charge</label>
            <select name="person_in_charge_id" class="form-control" required>
                @foreach ($employees as $emp)
                    <option value="{{ $emp->id }}">
                        {{ $emp->name }} ({{ $emp->phone }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Project Manager --}}
        <div class="mb-3">
            <label class="form-label">Project Manager</label>
            <select name="project_manager_id" id="project_manager_id" class="form-control">
                <option value="">-- Select Project Manager --</option>
                @foreach ($employees as $emp)
                    <option value="{{ $emp->id }}" data-phone="{{ $emp->phone }}">
                        {{ $emp->name }} ({{ $emp->phone }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- PM Contact Number (Auto-filled) --}}
        <div class="mb-3">
            <label class="form-label">PM Contact Number</label>
            <input type="text" name="pm_contact_number" id="pm_contact_number" class="form-control" readonly placeholder="Will be auto-filled from selected PM">
        </div>

        {{-- Document Controller --}}
        <div class="mb-3">
            <label class="form-label">Document Controller</label>
            <select name="document_controller_id" id="document_controller_id" class="form-control">
                <option value="">-- Select Document Controller --</option>
                @foreach ($employees as $emp)
                    <option value="{{ $emp->id }}" data-phone="{{ $emp->phone }}">
                        {{ $emp->name }} ({{ $emp->phone }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Document Controller Number (Auto-filled) --}}
        <div class="mb-3">
            <label class="form-label">Document Controller Number</label>
            <input type="text" name="document_controller_number" id="document_controller_number" class="form-control" readonly placeholder="Will be auto-filled from selected Document Controller">
        </div>

        {{-- Status --}}
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
                <option value="active">Active</option>
                <option value="suspend">Suspend</option>
                <option value="closed">Closed</option>
            </select>
        </div>

        <button class="btn btn-primary">Save</button>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mrcInput = document.getElementById('mrc');
    const startDateInput = document.getElementById('service_start_date');
    const endDateInput = document.getElementById('service_end_date');
    const costInput = document.getElementById('cost');
    const serviceTypeSelect = document.getElementById('service_type');
    const simcardFields = document.getElementById('simcardFields');

    // Show/hide PR and PO fields based on service type
    function toggleSimcardFields() {
        if (serviceTypeSelect.value === 'simcard') {
            simcardFields.style.display = 'block';
        } else {
            simcardFields.style.display = 'none';
        }
    }

    // Initial check
    toggleSimcardFields();

    // Listen for service type changes
    serviceTypeSelect.addEventListener('change', toggleSimcardFields);

    const costInfo = document.getElementById('cost_info');
    
    function calculateCost() {
        const mrc = parseFloat(mrcInput.value) || 0;
        const startDate = startDateInput.value;
        const endDate = endDateInput.value;

        // Clear cost if end date is not selected
        if (!endDate) {
            costInput.value = '';
            if (costInfo) {
                costInfo.textContent = 'Enter MRC and select end date to calculate cost';
                costInfo.className = 'text-muted';
            }
            return;
        }

        // Check if MRC and start date are provided
        if (!mrc || mrc <= 0) {
            costInput.value = '';
            if (costInfo) {
                costInfo.textContent = 'Please enter MRC to calculate cost';
                costInfo.className = 'text-warning';
            }
            return;
        }

        if (!startDate) {
            costInput.value = '';
            if (costInfo) {
                costInfo.textContent = 'Please select start date to calculate cost';
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

        // Calculate difference in days (including both start and end days)
        const diffTime = Math.abs(end - start);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1; // +1 to include both start and end days
        
        // Calculate cost: MRC (per day) × number of days
        const cost = mrc * diffDays;
        
        costInput.value = cost.toFixed(2);
        
        // Update info message
        if (costInfo) {
            costInfo.textContent = `Cost calculated: ${diffDays} days × MRC ${mrc.toFixed(2)} per day = ${cost.toFixed(2)}`;
            costInfo.className = 'text-success';
        }
    }

    // Add event listeners
    mrcInput.addEventListener('input', calculateCost);
    startDateInput.addEventListener('change', calculateCost);
    endDateInput.addEventListener('change', calculateCost);
    
    // Auto-fill PM contact number
    const pmSelect = document.getElementById('project_manager_id');
    const pmContactInput = document.getElementById('pm_contact_number');
    if (pmSelect && pmContactInput) {
        pmSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.value) {
                pmContactInput.value = selectedOption.dataset.phone || '';
            } else {
                pmContactInput.value = '';
            }
        });
    }
    
    // Auto-fill Document Controller contact number
    const dmSelect = document.getElementById('document_controller_id');
    const dmContactInput = document.getElementById('document_controller_number');
    if (dmSelect && dmContactInput) {
        dmSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.value) {
                dmContactInput.value = selectedOption.dataset.phone || '';
            } else {
                dmContactInput.value = '';
            }
        });
    }
});
</script>
@endsection
