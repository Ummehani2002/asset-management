@extends('layouts.app')

@section('content')
<div class="container-fluid master-page">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-tools me-2"></i>System Maintenance</h2>
                <p>Send assigned assets for maintenance</p>
            </div>
            <a href="{{ route('asset-transactions.create') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Transactions
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- Pending Approvals (for assigned asset managers) --}}
    @if($pendingApprovals->count() > 0)
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <h5 class="alert-heading"><i class="bi bi-bell me-2"></i>Pending Approvals ({{ $pendingApprovals->count() }})</h5>
            <p class="mb-2">You have maintenance tasks assigned to you. Approve to process them.</p>
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead><tr><th>Asset</th><th>Entity</th><th>Assigned By</th><th>Asset Manager (Entity)</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                        @foreach($pendingApprovals as $pa)
                            <tr>
                                <td>{{ $pa->asset->serial_number ?? 'N/A' }} ({{ $pa->asset->asset_id ?? '' }})</td>
                                <td>{{ $pa->asset_entity ?? '-' }}</td>
                                <td>{{ $pa->assignedBy->name ?? $pa->assignedBy->entity_name ?? 'N/A' }}</td>
                                <td>{{ $pa->assigned_by_entities ?? '-' }}</td>
                                <td>{{ $pa->created_at->format('d-M-Y') }}</td>
                                <td>
                                    <form action="{{ route('asset-transactions.maintenance-approve', $pa->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check"></i> Approve</button>
                                    </form>
                                    <form action="{{ route('asset-transactions.maintenance-reject', $pa->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Reject this assignment?');"><i class="bi bi-x"></i> Reject</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Tabs for Send to Maintenance, Assign, and Reassign -->
    <ul class="nav nav-tabs mb-4" id="maintenanceTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="send-tab" data-bs-toggle="tab" data-bs-target="#send-maintenance" type="button" role="tab">
                <i class="bi bi-arrow-down-circle me-2"></i>Send for Maintenance
            </button>
        </li>
        @if(auth()->user()?->employee_id && ($assetManagers ?? collect())->isNotEmpty())
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="assign-tab" data-bs-toggle="tab" data-bs-target="#assign-maintenance" type="button" role="tab">
                <i class="bi bi-person-plus me-2"></i>Assign to Asset Manager
            </button>
        </li>
        @endif
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="reassign-tab" data-bs-toggle="tab" data-bs-target="#reassign-maintenance" type="button" role="tab">
                <i class="bi bi-arrow-up-circle me-2"></i>Reassign from Maintenance
            </button>
        </li>
    </ul>

    <div class="tab-content" id="maintenanceTabContent">
        <!-- Send for Maintenance Tab -->
        <div class="tab-pane fade show active" id="send-maintenance" role="tabpanel">
            <form method="POST" action="{{ route('asset-transactions.maintenance-store') }}" enctype="multipart/form-data" id="maintenanceForm">
                @csrf

                {{-- Entity & Asset Manager --}}
                <div class="master-form-card mb-4">
                    <h5 class="mb-3"><i class="bi bi-building me-2"></i>Entity & Asset Manager</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="send_entity_id">Entity</label>
                            <select id="send_entity_id" class="form-control">
                                <option value="">-- Select Entity (optional) --</option>
                                @foreach($entities ?? [] as $ent)
                                    <option value="{{ $ent->id }}" data-asset-manager="{{ $ent->asset_manager_name ?? '' }}" data-asset-manager-id="{{ $ent->asset_manager_employee_id ?? '' }}" data-entity-name="{{ ucwords($ent->name) }}">{{ ucwords($ent->name) }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select entity to see asset manager. If busy, use "Assign to Asset Manager" tab.</small>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div id="send_asset_manager_display" class="text-muted small" style="min-height: 24px;">Select entity to see asset manager</div>
                        </div>
                    </div>
                </div>

                {{-- Asset Category --}}
                <div class="master-form-card mb-4">
                    <h5 class="mb-3"><i class="bi bi-tag me-2"></i>Select Asset</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="asset_category_id">Asset Category <span class="text-danger">*</span></label>
                            <select name="asset_category_id" id="asset_category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3" id="asset_selection_section" style="display:none;">
                            <label for="asset_id">Asset (Serial Number) <span class="text-danger">*</span></label>
                            <select name="asset_id" id="asset_id" class="form-control" required>
                                <option value="">Select Category First</option>
                            </select>
                            <small class="text-muted" id="asset_status_info"></small>
                            <small class="text-danger" id="asset_error_info"></small>
                        </div>
                    </div>
                </div>

        {{-- Maintenance Details --}}
        <div class="master-form-card mb-4" id="maintenance_details_section" style="display:none;">
            <h5 class="mb-3"><i class="bi bi-wrench me-2"></i>Maintenance Details</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="receive_date">Receive Date <span class="text-danger">*</span></label>
                    <input type="date" name="receive_date" id="receive_date" class="form-control" 
                           value="{{ old('receive_date', date('Y-m-d')) }}" required>
                    <small class="text-muted">When asset is received for maintenance</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="delivery_date">Expected Delivery Date</label>
                    <input type="date" name="delivery_date" id="delivery_date" class="form-control" 
                           value="{{ old('delivery_date', '') }}">
                    <small class="text-muted">Expected date when asset will be returned from maintenance</small>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="repair_type">Repair Type</label>
                    <select name="repair_type" id="repair_type" class="form-control">
                        <option value="">Select Repair Type</option>
                        <option value="Hardware Replacement">Hardware Replacement</option>
                        <option value="Software Installation">Software Installation</option>
                        <option value="Preventive Maintenance">Preventive Maintenance</option>
                        <option value="On Call Service">On Call Service</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="maintenance_image" class="form-label">
                        <i class="bi bi-camera me-2"></i>Upload Asset Image
                    </label>
                    <input type="file" name="maintenance_image" id="maintenance_image" class="form-control" accept="image/*">
                    <small class="text-muted">Upload an image of the asset (Max: 5MB)</small>
                </div>
            </div>

            <div class="mb-3">
                <label for="maintenance_notes">Maintenance Notes</label>
                <textarea name="maintenance_notes" id="maintenance_notes" class="form-control" rows="3" 
                          placeholder="Enter any notes about the maintenance..."></textarea>
            </div>

            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Note:</strong> An email will be sent to the assigned employee notifying them that the asset has been sent for maintenance. 
                After maintenance is complete, you can reassign the asset to the same employee using the Assign transaction.
            </div>
        </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-check-circle me-2"></i>Send for Maintenance
                    </button>
                    <a href="{{ route('asset-transactions.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Assign to Asset Manager Tab -->
        @if(auth()->user()?->employee_id && ($assetManagers ?? collect())->isNotEmpty())
        <div class="tab-pane fade" id="assign-maintenance" role="tabpanel">
            <form method="POST" action="{{ route('asset-transactions.maintenance-assign') }}" id="assignForm">
                @csrf
                <div class="master-form-card mb-4">
                    <h5 class="mb-3"><i class="bi bi-building me-2"></i>Entity & Current Asset Manager</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="assign_entity_id">Entity</label>
                            <select id="assign_entity_id" class="form-control">
                                <option value="">-- Select Entity (optional) --</option>
                                @foreach($entities ?? [] as $ent)
                                    <option value="{{ $ent->id }}" data-asset-manager="{{ $ent->asset_manager_name ?? '' }}" data-asset-manager-id="{{ $ent->asset_manager_employee_id ?? '' }}" data-entity-name="{{ ucwords($ent->name) }}">{{ ucwords($ent->name) }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select entity to see its asset manager. Reassign below if they're busy.</small>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div id="assign_entity_asset_manager_display" class="text-muted small" style="min-height: 24px;">Select entity to see asset manager</div>
                        </div>
                    </div>
                </div>
                <div class="master-form-card mb-4">
                    <h5 class="mb-3"><i class="bi bi-person-plus me-2"></i>Assign Maintenance to Another Asset Manager</h5>
                    <p class="text-muted mb-3">When you're busy, assign a maintenance task to another asset manager. They must approve before they can process it.</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="assign_category_id">Asset Category <span class="text-danger">*</span></label>
                            <select name="asset_category_id" id="assign_category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3" id="assign_asset_section" style="display:none;">
                            <label for="assign_asset_id">Asset Under Maintenance <span class="text-danger">*</span></label>
                            <select name="asset_id" id="assign_asset_id" class="form-control" required>
                                <option value="">Select Category First</option>
                            </select>
                            <input type="hidden" name="asset_transaction_id" id="assign_asset_transaction_id">
                            <small class="text-muted d-block mt-1" id="assign_asset_info"></small>
                        </div>
                    </div>
                    <div class="row" id="assign_manager_section" style="display:none;">
                        <div class="col-md-6 mb-3">
                            <label for="assigned_to_employee_id">Assign To Asset Manager <span class="text-danger">*</span></label>
                            <select name="assigned_to_employee_id" id="assigned_to_employee_id" class="form-control" required>
                                <option value="">Select Asset Manager</option>
                                @foreach($assetManagers ?? [] as $am)
                                    @if($am->id != auth()->user()?->employee_id)
                                        <option value="{{ $am->id }}">{{ $am->name ?? $am->entity_name ?? 'N/A' }} ({{ $am->employee_id ?? '' }}) - {{ $am->managed_entities ?? '-' }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="assign_notes">Notes (optional)</label>
                            <input type="text" name="notes" id="assign_notes" class="form-control" placeholder="e.g. Please handle this, I'm busy">
                        </div>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="assignBtn" disabled>
                        <i class="bi bi-send me-2"></i>Assign to Asset Manager
                    </button>
                    <a href="{{ route('asset-transactions.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        @endif

        <!-- Reassign from Maintenance Tab -->
        <div class="tab-pane fade" id="reassign-maintenance" role="tabpanel">
            <form method="POST" action="{{ route('asset-transactions.maintenance-reassign') }}" enctype="multipart/form-data" id="reassignForm">
                @csrf

                {{-- Entity & Asset Manager --}}
                <div class="master-form-card mb-4">
                    <h5 class="mb-3"><i class="bi bi-building me-2"></i>Entity & Asset Manager</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="reassign_entity_id">Entity</label>
                            <select id="reassign_entity_id" class="form-control">
                                <option value="">-- Select Entity (optional) --</option>
                                @foreach($entities ?? [] as $ent)
                                    <option value="{{ $ent->id }}" data-asset-manager="{{ $ent->asset_manager_name ?? '' }}" data-asset-manager-id="{{ $ent->asset_manager_employee_id ?? '' }}" data-entity-name="{{ ucwords($ent->name) }}">{{ ucwords($ent->name) }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Select entity to see asset manager for that entity.</small>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div id="reassign_entity_asset_manager_display" class="text-muted small" style="min-height: 24px;">Select entity to see asset manager</div>
                        </div>
                    </div>
                </div>

                {{-- Asset Category --}}
                <div class="master-form-card mb-4">
                    <h5 class="mb-3"><i class="bi bi-tag me-2"></i>Select Asset Under Maintenance</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="reassign_category_id">Asset Category <span class="text-danger">*</span></label>
                            <select name="asset_category_id" id="reassign_category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->category_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3" id="reassign_asset_section" style="display:none;">
                            <label for="reassign_asset_id">Asset (Serial Number) <span class="text-danger">*</span></label>
                            <select name="asset_id" id="reassign_asset_id" class="form-control" required>
                                <option value="">Select Category First</option>
                            </select>
                            <small class="text-muted d-block mt-1" id="reassign_status_info"></small>
                            <small class="text-danger" id="reassign_error_info"></small>
                        </div>
                    </div>
                </div>

                {{-- Action Type Selection --}}
                <div class="master-form-card mb-4" id="reassign_details_section" style="display:none;">
                    <h5 class="mb-3"><i class="bi bi-arrow-up-circle me-2"></i>Select Action</h5>
                    <div class="mb-3">
                        <label class="form-label">What would you like to do with this asset? <span class="text-danger">*</span></label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="action_type" id="action_reassign" value="reassign" checked>
                            <label class="form-check-label" for="action_reassign">
                                <strong>Reassign to Same Employee</strong> - Asset will be assigned back to the same employee
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="action_type" id="action_maintenance" value="maintenance">
                            <label class="form-check-label" for="action_maintenance">
                                <strong>Send Back to Maintenance</strong> - Asset will continue in maintenance
                            </label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="action_type" id="action_return" value="return">
                            <label class="form-check-label" for="action_return">
                                <strong>Return Asset</strong> - Asset will be returned and become available
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Reassign Details (for Reassign action) --}}
                <div class="master-form-card mb-4" id="reassign_action_section" style="display:none;">
                    <h5 class="mb-3"><i class="bi bi-person-check me-2"></i>Reassign to Same Employee</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="reassign_date">Reassign Date <span class="text-danger">*</span></label>
                            <input type="date" name="reassign_date" id="reassign_date" class="form-control" 
                                   value="{{ old('reassign_date', date('Y-m-d')) }}">
                            <small class="text-muted">Date when asset is ready for collection</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="reassign_image" class="form-label">
                                <i class="bi bi-camera me-2"></i>Upload Asset Image
                            </label>
                            <input type="file" name="reassign_image" id="reassign_image" class="form-control" accept="image/*">
                            <small class="text-muted">Upload an image of the asset (Max: 5MB)</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reassign_notes">Reassign Notes</label>
                        <textarea name="reassign_notes" id="reassign_notes" class="form-control" rows="3" 
                                  placeholder="Enter any notes about the reassignment..."></textarea>
                    </div>

                    <div class="alert alert-success">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> An email will be sent to the employee notifying them that the asset is ready for collection. 
                        The asset will be reassigned to the same employee with "assigned" status.
                    </div>
                </div>

                {{-- Maintenance Details (for Send Back to Maintenance action) --}}
                <div class="master-form-card mb-4" id="maintenance_action_section" style="display:none;">
                    <h5 class="mb-3"><i class="bi bi-wrench me-2"></i>Send Back to Maintenance</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="receive_date_maintenance">Receive Date <span class="text-danger">*</span></label>
                            <input type="date" name="receive_date" id="receive_date_maintenance" class="form-control" 
                                   value="{{ old('receive_date', date('Y-m-d')) }}">
                            <small class="text-muted">When asset is received for maintenance</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="delivery_date_maintenance">Expected Delivery Date</label>
                            <input type="date" name="delivery_date" id="delivery_date_maintenance" class="form-control" 
                                   value="{{ old('delivery_date', '') }}">
                            <small class="text-muted">Expected date when asset will be returned from maintenance</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="repair_type_maintenance">Repair Type</label>
                            <select name="repair_type" id="repair_type_maintenance" class="form-control">
                                <option value="">Select Repair Type</option>
                                <option value="Hardware Replacement">Hardware Replacement</option>
                                <option value="Software Installation">Software Installation</option>
                                <option value="Preventive Maintenance">Preventive Maintenance</option>
                                <option value="On Call Service">On Call Service</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="reassign_image_maintenance" class="form-label">
                                <i class="bi bi-camera me-2"></i>Upload Asset Image
                            </label>
                            <input type="file" name="reassign_image" id="reassign_image_maintenance" class="form-control" accept="image/*">
                            <small class="text-muted">Upload an image of the asset (Max: 5MB)</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reassign_notes_maintenance">Maintenance Notes</label>
                        <textarea name="reassign_notes" id="reassign_notes_maintenance" class="form-control" rows="3" 
                                  placeholder="Enter any notes about the maintenance..."></textarea>
                    </div>
                </div>

                {{-- Return Details (for Return action) --}}
                <div class="master-form-card mb-4" id="return_action_section" style="display:none;">
                    <h5 class="mb-3"><i class="bi bi-arrow-left-circle me-2"></i>Return Asset</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="return_date_field">Return Date <span class="text-danger">*</span></label>
                            <input type="date" name="reassign_date" id="return_date_field" class="form-control" 
                                   value="{{ old('reassign_date', date('Y-m-d')) }}">
                            <small class="text-muted">Date when asset is returned</small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="reassign_image_return" class="form-label">
                                <i class="bi bi-camera me-2"></i>Upload Asset Image
                            </label>
                            <input type="file" name="reassign_image" id="reassign_image_return" class="form-control" accept="image/*">
                            <small class="text-muted">Upload an image of the asset (Max: 5MB)</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reassign_notes_return">Return Notes</label>
                        <textarea name="reassign_notes" id="reassign_notes_return" class="form-control" rows="3" 
                                  placeholder="Enter any notes about the return..."></textarea>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> The asset will be returned and become available for assignment to any employee.
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success" id="reassignBtn">
                        <i class="bi bi-check-circle me-2"></i>Process Asset
                    </button>
                    <a href="{{ route('asset-transactions.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let currentEmployeeId = null;
    let currentEmployeeName = null;

    // Entity selection -> show Asset Manager (all tabs)
    function updateAssetManagerDisplay(selectId, displayId) {
        const opt = $(selectId).find('option:selected');
        const amName = opt.data('asset-manager');
        const amId = opt.data('asset-manager-id');
        const entityName = opt.data('entity-name');
        if (amName && entityName) {
            $(displayId).html('<strong>Asset Manager:</strong> ' + amName + (amId ? ' (' + amId + ')' : '') + ' - ' + entityName).removeClass('text-muted').addClass('text-info');
        } else if (entityName && !amName) {
            $(displayId).html('<span class="text-warning">No asset manager assigned for ' + entityName + '</span>').removeClass('text-muted text-info');
        } else {
            $(displayId).html('Select entity to see asset manager').removeClass('text-info').addClass('text-muted');
        }
    }
    $('#send_entity_id').on('change', function() { updateAssetManagerDisplay('#send_entity_id', '#send_asset_manager_display'); });
    $('#assign_entity_id').on('change', function() { updateAssetManagerDisplay('#assign_entity_id', '#assign_entity_asset_manager_display'); });
    $('#reassign_entity_id').on('change', function() { updateAssetManagerDisplay('#reassign_entity_id', '#reassign_entity_asset_manager_display'); });

    // Handle category change
    $('#asset_category_id').on('change', function() {
        const categoryId = $(this).val();
        
        if (!categoryId) {
            $('#asset_selection_section').hide();
            $('#maintenance_details_section').hide();
            return;
        }

        $('#asset_selection_section').show();
        $('#asset_id').html('<option value="">Loading assets...</option>');

        $.get(`/asset-transactions/get-assets-by-category/${categoryId}`, function(assets) {
            $('#asset_id').html('<option value="">Select Asset</option>');
            assets.forEach(function(asset) {
                // Only show assigned assets for maintenance
                if (asset.original_status === 'assigned') {
                    $('#asset_id').append(
                        $('<option></option>')
                            .val(asset.id)
                            .text(`${asset.serial_number} (${asset.asset_id}) - Assigned`)
                            .data('status', asset.original_status)
                    );
                }
            });
        });
    });

    // Handle asset selection - auto-fill entity & asset manager from location (linked with location entity)
    $('#asset_id').on('change', function() {
        const assetId = $(this).val();
        
        if (!assetId) {
            $('#maintenance_details_section').hide();
            $('#asset_error_info').text('');
            $('#send_entity_id').val('');
            updateAssetManagerDisplay('#send_entity_id', '#send_asset_manager_display');
            return;
        }

        // Get asset details (entity/asset manager from location when available)
        $.get(`/asset-transactions/get-asset-details/${assetId}`, function(data) {
            if (data.status !== 'assigned' && data.original_status !== 'assigned') {
                $('#asset_error_info').text('This asset is not assigned. Only assigned assets can be sent for maintenance.');
                $('#maintenance_details_section').hide();
                $('#send_entity_id').val('');
                return;
            }

            $('#asset_error_info').text('');
            currentEmployeeId = data.current_employee_id;
            currentEmployeeName = data.current_employee_name;

            // Auto-fill entity from location (location entity linked with location name)
            if (data.entity_id) {
                $('#send_entity_id').val(data.entity_id);
                updateAssetManagerDisplay('#send_entity_id', '#send_asset_manager_display');
            } else {
                $('#send_entity_id').val('');
                updateAssetManagerDisplay('#send_entity_id', '#send_asset_manager_display');
            }
            
            let infoHtml = '';
            if (data.current_employee_name) {
                infoHtml += `<strong>Assigned to:</strong> ${data.current_employee_name}`;
            }
            if (data.location_name) {
                infoHtml += (infoHtml ? ' &nbsp;|&nbsp; ' : '') + `<strong>Location:</strong> ${data.location_name || ''}${data.location_entity ? ' (' + data.location_entity + ')' : ''}`;
            }
            if (data.asset_manager_name) {
                infoHtml += (infoHtml ? ' &nbsp;|&nbsp; ' : '') + `<strong>Asset Manager:</strong> ${data.asset_manager_name} (${data.asset_manager_employee_id || ''}) - ${data.asset_manager_entity || ''}`;
            }
            if (infoHtml) {
                $('#asset_status_info').html(infoHtml + '<br><small class="text-muted">If asset manager is busy, use "Assign to Asset Manager" tab to reassign.</small>');
                $('#asset_status_info').removeClass('text-danger').addClass('text-info');
            }
            
            $('#maintenance_details_section').show();
        }).fail(function() {
            $('#asset_error_info').text('Error loading asset details.');
            $('#maintenance_details_section').hide();
            $('#send_entity_id').val('');
        });
    });

    // Form submission
    $('#maintenanceForm').on('submit', function(e) {
        $('#submitBtn').prop('disabled', true);
        $('#submitBtn').html('<i class="bi bi-hourglass-split me-2"></i>Processing...');
    });

    // Assign Tab - Handle category change
    $('#assign_category_id').on('change', function() {
        const categoryId = $(this).val();
        if (!categoryId) {
            $('#assign_asset_section').hide();
            $('#assign_manager_section').hide();
            $('#assignBtn').prop('disabled', true);
            return;
        }
        $('#assign_asset_section').show();
        $('#assign_asset_id').html('<option value="">Loading...</option>');
        $('#assign_asset_transaction_id').val('');
        $('#assign_manager_section').hide();
        $('#assignBtn').prop('disabled', true);

        $.get(`/asset-transactions/get-maintenance-assets-by-category/${categoryId}`, function(assets) {
            $('#assign_asset_id').html('<option value="">Select Asset</option>');
            assets.forEach(function(asset) {
                if (asset.transaction_id) {
                    $('#assign_asset_id').append(
                        $('<option></option>')
                            .val(asset.id)
                            .text(`${asset.serial_number} (${asset.asset_id}) - Under Maintenance`)
                            .data('transactionId', asset.transaction_id)
                    );
                }
            });
        });
    });

    $('#assign_asset_id').on('change', function() {
        const assetId = $(this).val();
        const opt = $(this).find('option:selected');
        const txnId = opt.data('transactionId');
        if (txnId && assetId) {
            $('#assign_asset_transaction_id').val(txnId);
            $('#assign_manager_section').show();
            $('#assignBtn').prop('disabled', false);
            $('#assign_asset_info').text('Loading...');
            $.get(`/asset-transactions/get-asset-details/${assetId}`, function(data) {
                let info = '';
                if (data.asset_manager_name) {
                    info = `Current Asset Manager: ${data.asset_manager_name} (${data.asset_manager_employee_id || ''}) - ${data.asset_manager_entity || ''}. Reassign below if busy.`;
                } else {
                    info = 'Select an asset manager below to assign this maintenance task.';
                }
                $('#assign_asset_info').html(info).addClass('text-info');
            }).fail(function() {
                $('#assign_asset_info').text('').removeClass('text-info');
            });
        } else {
            $('#assign_asset_transaction_id').val('');
            $('#assign_manager_section').hide();
            $('#assignBtn').prop('disabled', true);
            $('#assign_asset_info').text('');
        }
    });

    // Reassign Tab - Handle category change
    $('#reassign_category_id').on('change', function() {
        const categoryId = $(this).val();
        
        if (!categoryId) {
            $('#reassign_asset_section').hide();
            $('#reassign_details_section').hide();
            return;
        }

        $('#reassign_asset_section').show();
        $('#reassign_asset_id').html('<option value="">Loading assets...</option>');

        $.get(`/asset-transactions/get-assets-by-category/${categoryId}`, function(assets) {
            $('#reassign_asset_id').html('<option value="">Select Asset</option>');
            assets.forEach(function(asset) {
                // Only show assets under maintenance
                if (asset.original_status === 'under_maintenance') {
                    $('#reassign_asset_id').append(
                        $('<option></option>')
                            .val(asset.id)
                            .text(`${asset.serial_number} (${asset.asset_id}) - Under Maintenance`)
                            .data('status', asset.original_status)
                    );
                }
            });
        });
    });

    // Reassign Tab - Handle asset selection (auto-fill entity from location)
    $('#reassign_asset_id').on('change', function() {
        const assetId = $(this).val();
        
        if (!assetId) {
            $('#reassign_details_section').hide();
            $('#reassign_action_section').hide();
            $('#maintenance_action_section').hide();
            $('#return_action_section').hide();
            $('#reassign_error_info').text('');
            $('#reassign_entity_id').val('');
            updateAssetManagerDisplay('#reassign_entity_id', '#reassign_entity_asset_manager_display');
            return;
        }

        // Get asset details (entity/asset manager from location when available)
        $.get(`/asset-transactions/get-asset-details/${assetId}`, function(data) {
            if (data.status !== 'under_maintenance' && data.original_status !== 'under_maintenance') {
                $('#reassign_error_info').text('This asset is not under maintenance. Only assets under maintenance can be processed.');
                $('#reassign_details_section').hide();
                $('#reassign_action_section').hide();
                $('#maintenance_action_section').hide();
                $('#return_action_section').hide();
                return;
            }

            $('#reassign_error_info').text('');

            // Auto-fill entity from location (location entity linked with location name)
            if (data.entity_id) {
                $('#reassign_entity_id').val(data.entity_id);
                updateAssetManagerDisplay('#reassign_entity_id', '#reassign_entity_asset_manager_display');
            } else {
                $('#reassign_entity_id').val('');
                updateAssetManagerDisplay('#reassign_entity_id', '#reassign_entity_asset_manager_display');
            }
            
            let reassignInfo = '';
            if (data.current_employee_name) {
                reassignInfo += `<strong>Previous Employee:</strong> ${data.current_employee_name}`;
            }
            if (data.location_name) {
                reassignInfo += (reassignInfo ? ' &nbsp;|&nbsp; ' : '') + `<strong>Location:</strong> ${data.location_name || ''}${data.location_entity ? ' (' + data.location_entity + ')' : ''}`;
            }
            if (data.asset_manager_name) {
                reassignInfo += (reassignInfo ? ' &nbsp;|&nbsp; ' : '') + `<strong>Asset Manager:</strong> ${data.asset_manager_name} (${data.asset_manager_employee_id || ''}) - ${data.asset_manager_entity || ''}`;
            }
            if (reassignInfo) {
                $('#reassign_status_info').html(reassignInfo).removeClass('text-danger').addClass('text-info');
            }
            
            $('#reassign_details_section').show();
            updateActionSections();
        }).fail(function() {
            $('#reassign_error_info').text('Error loading asset details.');
            $('#reassign_details_section').hide();
            $('#reassign_action_section').hide();
            $('#maintenance_action_section').hide();
            $('#return_action_section').hide();
            $('#reassign_entity_id').val('');
        });
    });

    // Handle action type change
    $('input[name="action_type"]').on('change', function() {
        updateActionSections();
    });

    function updateActionSections() {
        const actionType = $('input[name="action_type"]:checked').val();
        
        // Hide all sections
        $('#reassign_action_section').hide();
        $('#maintenance_action_section').hide();
        $('#return_action_section').hide();
        
        // Show relevant section
        if (actionType === 'reassign') {
            $('#reassign_action_section').show();
            $('#reassign_date').prop('required', true);
            $('#receive_date_maintenance').prop('required', false);
            $('#return_date_field').prop('required', false);
        } else if (actionType === 'maintenance') {
            $('#maintenance_action_section').show();
            $('#reassign_date').prop('required', false);
            $('#receive_date_maintenance').prop('required', true);
            $('#return_date_field').prop('required', false);
        } else if (actionType === 'return') {
            $('#return_action_section').show();
            $('#reassign_date').prop('required', false);
            $('#receive_date_maintenance').prop('required', false);
            $('#return_date_field').prop('required', true);
        }
    }

    // Reassign form submission
    $('#reassignForm').on('submit', function(e) {
        const actionType = $('input[name="action_type"]:checked').val();
        
        // Validate required fields based on action type
        if (actionType === 'reassign') {
            if (!$('#reassign_date').val()) {
                e.preventDefault();
                alert('Please enter a reassign date.');
                return false;
            }
        } else if (actionType === 'maintenance') {
            if (!$('#receive_date_maintenance').val()) {
                e.preventDefault();
                alert('Please enter a receive date for maintenance.');
                return false;
            }
        } else if (actionType === 'return') {
            if (!$('#return_date_field').val()) {
                e.preventDefault();
                alert('Please enter a return date.');
                return false;
            }
        }
        
        $('#reassignBtn').prop('disabled', true);
        $('#reassignBtn').html('<i class="bi bi-hourglass-split me-2"></i>Processing...');
    });
});
</script>
@endsection

