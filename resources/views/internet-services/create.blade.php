@extends('layouts.app')

@section('content')
<div class="container">
    <h3> Internet Service</h3>

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
                <option value="datacard">Data Card</option>
                <option value="fixed">Fixed Service</option>
                <option value="service">Out Sourced</option>
            </select>
        </div>

        {{-- Bandwidth --}}
        <div class="mb-3">
            <label class="form-label">Bandwidth</label>
            <input type="text" name="bandwidth" class="form-control" placeholder="e.g. 100 Mbps, 50 Mbps">
        </div>

        <input type="hidden" name="transaction_type" value="assign">

        {{-- PR Number and PO Number (only for Data Card) --}}
        <div id="datacardFields" style="display: none;">
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
            <small class="text-muted">Cost per month. Cost is calculated when you add an end date later (via Return or Edit).</small>
        </div>

        {{-- Start Date only; end date is added later via Return or Edit --}}
        <div class="mb-3">
            <label class="form-label">Start Date <span class="text-danger">*</span></label>
            <input type="date" name="service_start_date" id="service_start_date" class="form-control" required>
        </div>

        {{-- Person in Charge (Employee) --}}
        <div class="mb-3">
            <label class="form-label">Person in Charge</label>
            <select name="person_in_charge_id" class="form-control employee-select" required data-placeholder="Type to search...">
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
            <select name="project_manager_id" id="project_manager_id" class="form-control employee-select" data-placeholder="Type to search...">
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
            <select name="document_controller_id" id="document_controller_id" class="form-control employee-select" data-placeholder="Type to search...">
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
    const serviceTypeSelect = document.getElementById('service_type');
    const datacardFields = document.getElementById('datacardFields');

    // Show/hide PR and PO fields based on service type
    function toggleDatacardFields() {
        if (serviceTypeSelect.value === 'datacard') {
            datacardFields.style.display = 'block';
        } else {
            datacardFields.style.display = 'none';
        }
    }

    toggleDatacardFields();
    serviceTypeSelect.addEventListener('change', toggleDatacardFields);
    
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
