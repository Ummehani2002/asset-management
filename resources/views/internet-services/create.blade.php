@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Add Internet Service</h3>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
            @if(session('saved_service_id'))
                <a href="{{ route('internet-services.download-form', session('saved_service_id')) }}" class="btn btn-sm btn-outline-light ms-3">
                    <i class="bi bi-download me-1"></i>Download Form (PDF)
                </a>
            @endif
        </div>
    @endif

    <form action="{{ route('internet-services.store') }}" method="POST" autocomplete="off">
        @csrf

        {{-- Entity --}}
        <div class="mb-3">
            <label class="form-label">Entity <span class="text-danger">*</span></label>
            <select name="entity" id="entity" class="form-control" required>
                <option value="">-- Select Entity --</option>
                @foreach(\App\Helpers\EntityHelper::getEntities() as $entity)
                    <option value="{{ $entity }}" {{ old('entity') == $entity ? 'selected' : '' }}>
                        {{ ucwords($entity) }}
                    </option>
                @endforeach
            </select>
        </div>

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

        {{-- MRC (Monthly Cost) --}}
        <div class="mb-3">
            <label class="form-label">MRC (Monthly Cost) <span class="text-muted">(Monthly Rate)</span></label>
            <input type="number" name="mrc" id="mrc" class="form-control" step="0.01" min="0" placeholder="Enter monthly cost (e.g., 300.00)">
            <small class="text-muted">Enter the cost per month. End date will be calculated automatically when start date is selected.</small>
        </div>

        {{-- Dates --}}
        <div class="mb-3">
            <label class="form-label">Start Date</label>
            <input type="date" name="service_start_date" id="service_start_date" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">End Date <small class="text-muted">(Auto-calculated or manual)</small></label>
            <input type="date" name="service_end_date" id="service_end_date" class="form-control">
            <small class="text-muted" id="end_date_info">End date will be calculated automatically (1 month from start date) when start date is selected. You can also set it manually.</small>
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

        <button type="submit" class="btn btn-primary">Save</button>
        <button type="button" class="btn btn-secondary ms-2" onclick="resetForm(this)">
            <i class="bi bi-x-circle me-2"></i>Cancel
        </button>
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
        
        // Calculate months (approximate)
        const months = diffDays / 30.44; // Average days per month
        
        // Calculate cost: MRC (per month) × number of months
        const cost = mrc * months;
        
        costInput.value = cost.toFixed(2);
        
        // Update info message
        if (costInfo) {
            costInfo.textContent = `Cost calculated: ${months.toFixed(2)} months × MRC ${mrc.toFixed(2)} per month = ${cost.toFixed(2)}`;
            costInfo.className = 'text-success';
        }
    }

    // Calculate end date when start date is selected (if MRC is provided)
    function calculateEndDate() {
        const startDate = startDateInput.value;
        const mrc = parseFloat(mrcInput.value) || 0;
        const endDateInfo = document.getElementById('end_date_info');
        
        if (!startDate) {
            if (endDateInfo) {
                endDateInfo.textContent = 'Select start date to auto-calculate end date';
                endDateInfo.className = 'text-muted';
            }
            return;
        }
        
        // If MRC is provided, set end date to 1 month from start date
        if (mrc > 0) {
            const start = new Date(startDate);
            const end = new Date(start);
            end.setMonth(end.getMonth() + 1); // Add 1 month
            
            // Format as YYYY-MM-DD
            const year = end.getFullYear();
            const month = String(end.getMonth() + 1).padStart(2, '0');
            const day = String(end.getDate()).padStart(2, '0');
            const endDateStr = `${year}-${month}-${day}`;
            
            endDateInput.value = endDateStr;
            
            if (endDateInfo) {
                endDateInfo.textContent = `End date auto-calculated: 1 month from start date (${endDateStr})`;
                endDateInfo.className = 'text-success';
            }
            
            // Trigger cost calculation
            calculateCost();
        } else {
            if (endDateInfo) {
                endDateInfo.textContent = 'Enter MRC (monthly cost) to auto-calculate end date';
                endDateInfo.className = 'text-muted';
            }
        }
    }

    // Add event listeners
    mrcInput.addEventListener('input', function() {
        calculateEndDate();
        calculateCost();
    });
    startDateInput.addEventListener('change', calculateEndDate);
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
