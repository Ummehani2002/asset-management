@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Edit Internet Service</h3>

    {{-- Service Selector --}}
    <div class="card mb-4" style="background: #f8f9fa; border: 1px solid #dee2e6;">
        <div class="card-body">
            <label class="form-label fw-bold">Select Internet Service to Edit</label>
            <select id="service_selector" class="form-control" style="font-size: 16px;">
                <option value="">-- Select a service to load details --</option>
                @foreach($allServices ?? [] as $service)
                    <option value="{{ $service->id }}" 
                        {{ $internetService->id == $service->id ? 'selected' : '' }}
                        data-service='@json($service)'>
                        {{ $service->project_name ?? 'N/A' }} - {{ $service->account_number ?? 'N/A' }} 
                        ({{ $service->service_type ?? 'N/A' }}) - 
                        Start: {{ $service->service_start_date ? $service->service_start_date->format('d-m-Y') : 'N/A' }}
                        @if($service->service_end_date)
                            - End: {{ $service->service_end_date->format('d-m-Y') }}
                        @else
                            <span class="text-warning">(Ongoing)</span>
                        @endif
                    </option>
                @endforeach
            </select>
            <small class="text-muted">Select a service to auto-fill all details. Then just update the end date and cost will calculate automatically.</small>
        </div>
    </div>

    <form action="{{ route('internet-services.update', $internetService->id) }}" method="POST" id="editForm">
        @csrf
        @method('PUT')

        {{-- Project --}}
        <div class="mb-3">
            <label class="form-label">Project</label>
            <select name="project_id" class="form-control" required>
                @foreach ($projects as $p)
                    <option value="{{ $p->id }}" 
                        {{ $internetService->project_id == $p->id ? 'selected' : '' }}>
                        {{ $p->project_id }} - {{ $p->project_name }} ({{ $p->entity }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Service Type --}}
        <div class="mb-3">
            <label class="form-label">Service Type</label>
            <select name="service_type" id="service_type" class="form-control">
                <option value="simcard" {{ $internetService->service_type == 'simcard' ? 'selected' : '' }}>SIM Card</option>
                <option value="fixed"   {{ $internetService->service_type == 'fixed' ? 'selected' : '' }}>Fixed Internet</option>
                <option value="service" {{ $internetService->service_type == 'service' ? 'selected' : '' }}>Other Service</option>
            </select>
        </div>

        {{-- Transaction Type --}}
        <div class="mb-3">
            <label class="form-label">Transaction Type</label>
            <select name="transaction_type" class="form-control">
                <option value="">-- select transaction type --</option>
                <option value="assign" {{ $internetService->transaction_type == 'assign' ? 'selected' : '' }}>Assign</option>
                <option value="return" {{ $internetService->transaction_type == 'return' ? 'selected' : '' }}>Return</option>
            </select>
        </div>

        {{-- PR Number and PO Number (only for SIM Card) --}}
        <div id="simcardFields" style="display: {{ $internetService->service_type == 'simcard' ? 'block' : 'none' }};">
            <div class="mb-3">
                <label class="form-label">PR Number</label>
                <input type="text" name="pr_number" class="form-control"
                       value="{{ $internetService->pr_number }}" placeholder="Enter PR number">
            </div>

            <div class="mb-3">
                <label class="form-label">PO Number</label>
                <input type="text" name="po_number" class="form-control"
                       value="{{ $internetService->po_number }}" placeholder="Enter PO number">
            </div>
        </div>

        {{-- Account Number --}}
        <div class="mb-3">
            <label class="form-label">Account Number</label>
            <input type="text" name="account_number" class="form-control"
                   value="{{ $internetService->account_number }}">
        </div>

        {{-- MRC (Per Day Cost) --}}
        <div class="mb-3">
            <label class="form-label">MRC (Cost Per Day) <span class="text-muted">(Daily Rate)</span></label>
            <input type="number" name="mrc" id="mrc" class="form-control" step="0.01" min="0"
                   value="{{ $internetService->mrc }}" placeholder="Enter daily cost (e.g., 10.00)">
            <small class="text-muted">Enter the cost per day. Total cost will be calculated as: Number of Days × MRC per day</small>
        </div>

        {{-- Dates --}}
        <div class="mb-3">
            <label class="form-label">Start Date</label>
            <input type="date" name="service_start_date" id="service_start_date" class="form-control"
                   value="{{ $internetService->service_start_date }}">
        </div>

        <div class="mb-3">
            <label class="form-label">End Date <small class="text-muted">(Optional - can be selected later)</small></label>
            <input type="date" name="service_end_date" id="service_end_date" class="form-control"
                   value="{{ $internetService->service_end_date }}">
            <small class="text-muted">Leave blank if service is still active. Cost will be calculated automatically when end date is selected.</small>
        </div>

        {{-- Cost (Auto-calculated) --}}
        <div class="mb-3">
            <label class="form-label">Cost</label>
            <input type="number" name="cost" id="cost" class="form-control" step="0.01" min="0" readonly
                   value="{{ $internetService->cost }}" placeholder="Will be calculated automatically when end date is selected">
            <small class="text-muted" id="cost_info">Enter MRC and select end date to calculate cost</small>
        </div>

        {{-- Person in Charge --}}
        <div class="mb-3">
            <label class="form-label">Person in Charge</label>
            <select name="person_in_charge_id" class="form-control">
                @foreach ($employees as $emp)
                    <option value="{{ $emp->id }}" 
                        {{ $internetService->person_in_charge == $emp->name ? 'selected' : '' }}>
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
                    <option value="{{ $emp->id }}" 
                        data-phone="{{ $emp->phone }}"
                        {{ ($internetService->project_manager_id ?? $internetService->project_manager) == $emp->id ? 'selected' : '' }}>
                        {{ $emp->name }} ({{ $emp->phone }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- PM Contact Number (Auto-filled) --}}
        <div class="mb-3">
            <label class="form-label">PM Contact Number</label>
            <input type="text" name="pm_contact_number" id="pm_contact_number" class="form-control" readonly 
                   value="{{ $internetService->pm_contact_number }}" placeholder="Will be auto-filled from selected PM">
        </div>

        {{-- Document Controller --}}
        <div class="mb-3">
            <label class="form-label">Document Controller</label>
            <select name="document_controller_id" id="document_controller_id" class="form-control">
                <option value="">-- Select Document Controller --</option>
                @foreach ($employees as $emp)
                    <option value="{{ $emp->id }}" 
                        data-phone="{{ $emp->phone }}"
                        {{ ($internetService->document_controller_id ?? $internetService->document_controller) == $emp->id ? 'selected' : '' }}>
                        {{ $emp->name }} ({{ $emp->phone }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Document Controller Number (Auto-filled) --}}
        <div class="mb-3">
            <label class="form-label">Document Controller Number</label>
            <input type="text" name="document_controller_number" id="document_controller_number" class="form-control" readonly
                   value="{{ $internetService->document_controller_number }}" placeholder="Will be auto-filled from selected Document Controller">
        </div>

        {{-- Status --}}
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
                <option value="active"  {{ $internetService->status == 'active' ? 'selected' : '' }}>Active</option>
                <option value="suspend" {{ $internetService->status == 'suspend' ? 'selected' : '' }}>Suspend</option>
                <option value="closed"  {{ $internetService->status == 'closed' ? 'selected' : '' }}>Closed</option>
            </select>
        </div>

        <button class="btn btn-primary">Update</button>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const serviceSelector = document.getElementById('service_selector');
    const editForm = document.getElementById('editForm');
    const mrcInput = document.getElementById('mrc');
    const startDateInput = document.getElementById('service_start_date');
    const endDateInput = document.getElementById('service_end_date');
    const costInput = document.getElementById('cost');
    const serviceTypeSelect = document.getElementById('service_type');
    const simcardFields = document.getElementById('simcardFields');
    
    // Service selector - load service details when selected
    if (serviceSelector) {
        serviceSelector.addEventListener('change', function() {
            const serviceId = this.value;
            if (!serviceId) return;
            
            // Update form action to point to selected service
            const currentAction = editForm.action;
            const baseUrl = currentAction.substring(0, currentAction.lastIndexOf('/'));
            editForm.action = baseUrl + '/' + serviceId;
            editForm.querySelector('input[name="_method"]').value = 'PUT';
            
            // Fetch service details
            fetch(`/internet-services/${serviceId}/details`)
                .then(response => response.json())
                .then(data => {
                    // Fill all form fields
                    if (document.querySelector(`select[name="project_id"]`)) {
                        document.querySelector(`select[name="project_id"]`).value = data.project_id || '';
                    }
                    if (serviceTypeSelect) {
                        serviceTypeSelect.value = data.service_type || '';
                        toggleSimcardFields(); // Update SIM card fields visibility
                    }
                    if (document.querySelector(`select[name="transaction_type"]`)) {
                        document.querySelector(`select[name="transaction_type"]`).value = data.transaction_type || '';
                    }
                    if (document.querySelector(`input[name="pr_number"]`)) {
                        document.querySelector(`input[name="pr_number"]`).value = data.pr_number || '';
                    }
                    if (document.querySelector(`input[name="po_number"]`)) {
                        document.querySelector(`input[name="po_number"]`).value = data.po_number || '';
                    }
                    if (document.querySelector(`input[name="account_number"]`)) {
                        document.querySelector(`input[name="account_number"]`).value = data.account_number || '';
                    }
                    if (mrcInput) {
                        mrcInput.value = data.mrc || '';
                    }
                    if (startDateInput) {
                        // Ensure date is in YYYY-MM-DD format
                        if (data.service_start_date) {
                            const startDate = new Date(data.service_start_date);
                            const formattedStartDate = startDate.toISOString().split('T')[0];
                            startDateInput.value = formattedStartDate;
                        } else {
                            startDateInput.value = '';
                        }
                    }
                    if (endDateInput) {
                        // Ensure date is in YYYY-MM-DD format
                        if (data.service_end_date) {
                            const endDate = new Date(data.service_end_date);
                            const formattedEndDate = endDate.toISOString().split('T')[0];
                            endDateInput.value = formattedEndDate;
                        } else {
                            endDateInput.value = '';
                        }
                    }
                    if (document.querySelector(`select[name="person_in_charge_id"]`)) {
                        document.querySelector(`select[name="person_in_charge_id"]`).value = data.person_in_charge_id || '';
                    }
                    if (document.querySelector(`input[name="project_manager"]`)) {
                        document.querySelector(`input[name="project_manager"]`).value = data.project_manager || '';
                    }
                    if (document.querySelector(`input[name="pm_contact_number"]`)) {
                        document.querySelector(`input[name="pm_contact_number"]`).value = data.pm_contact_number || '';
                    }
                    if (document.querySelector(`input[name="document_controller"]`)) {
                        document.querySelector(`input[name="document_controller"]`).value = data.document_controller || '';
                    }
                    if (document.querySelector(`input[name="document_controller_number"]`)) {
                        document.querySelector(`input[name="document_controller_number"]`).value = data.document_controller_number || '';
                    }
                    if (document.querySelector(`select[name="status"]`)) {
                        document.querySelector(`select[name="status"]`).value = data.status || 'active';
                    }
                    
                    // Trigger cost calculation
                    calculateCost();
                    
                    console.log('Service details loaded:', data);
                })
                .catch(error => {
                    console.error('Error loading service details:', error);
                    alert('Error loading service details. Please try again.');
                });
        });
    }

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
    
    // Calculate on page load if values exist
    calculateCost();
});
</script>
@endsection
