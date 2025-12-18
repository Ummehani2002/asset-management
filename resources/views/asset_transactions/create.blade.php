@extends('layouts.app')

@section('content')
<div class="container">
    <h2>{{ isset($transaction) ? 'Edit' : '' }} Asset Transaction</h2>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong>
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php $isEdit = isset($transaction); @endphp

    <form method="POST" 
          action="{{ $isEdit ? route('asset-transactions.update', $transaction->id) : route('asset-transactions.store') }}" 
          enctype="multipart/form-data" 
          id="transactionForm"
          novalidate>
        @csrf
        @if($isEdit) @method('PUT') @endif

        {{-- Asset Category --}}
        <div class="mb-3">
            <label for="asset_category_id">Asset Category <span class="text-danger">*</span></label>
            <select name="asset_category_id" id="asset_category_id" class="form-control" required>
                <option value="">Select Category</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" 
                        @if(old('asset_category_id', $transaction->asset->asset_category_id ?? '') == $cat->id) selected @endif>
                        {{ $cat->category_name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Asset Selection (with Serial Number) --}}
        <div class="mb-3" id="asset_selection_section" style="display:none;">
            <label for="asset_id">Asset (Serial Number) <span class="text-danger">*</span></label>
            <select name="asset_id" id="asset_id" class="form-control" required>
                <option value="">Select Category First</option>
                @if($isEdit && $transaction->asset)
                    <option value="{{ $transaction->asset->id }}" selected>
                        {{ $transaction->asset->assetCategory->category_name ?? 'N/A' }} - {{ $transaction->asset->serial_number }}
                    </option>
                @endif
            </select>
            <small class="text-muted" id="asset_status_info"></small>
        </div>

        {{-- Employee Selection (for Laptop - Assign) --}}
        <div class="mb-3" id="employee_section" style="display:none;">
            <label for="employee_id">Employee Name <span class="text-danger" id="employee_required">*</span></label>
            <select name="employee_id" id="employee_id" class="form-control">
                <option value="">Select Employee</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}"
                        @if(old('employee_id', $transaction->employee_id ?? '') == $emp->id) selected @endif>
                        {{ $emp->name }} ({{ $emp->email }})
                    </option>
                @endforeach
            </select>
            <small class="text-muted" id="employee_auto_fill_info"></small>
        </div>
        
        {{-- Hidden employee_id field for return transactions (always included in form) --}}
        <input type="hidden" name="employee_id_return" id="employee_id_return" value="">

        {{-- Employee Display (for Return - Read-only) --}}
        <div class="mb-3" id="employee_display_section" style="display:none;">
            <label>Assigned Employee Details</label>
            <div class="card bg-light p-3">
                <div class="row">
                    <div class="col-md-4">
                        <strong>Employee Name:</strong><br>
                        <span id="display_employee_name">-</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Employee ID:</strong><br>
                        <span id="display_employee_id">-</span>
                    </div>
                    <div class="col-md-4">
                        <strong>Entity:</strong><br>
                        <span id="display_employee_entity">-</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Project Name (for Printer) --}}
        <div class="mb-3" id="project_section" style="display:none;">
            <label for="project_name">Project Name <span class="text-danger" id="project_required">*</span></label>
            <input type="text" name="project_name" id="project_name" class="form-control"
                   value="{{ old('project_name', $transaction->project_name ?? '') }}"
                   placeholder="Enter project name">
            <small class="text-muted" id="project_auto_fill_info"></small>
            <select id="project_select" class="form-control mt-2" onchange="document.getElementById('project_name').value = this.value">
                <option value="">Or select existing project</option>
                @foreach($projects as $proj)
                    <option value="{{ $proj->project_name }}">{{ $proj->project_name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Location (for Laptop only) --}}
        <div class="mb-3" id="location_section" style="display:none;">
            <label for="location_id">Location</label>
            <select name="location_id" id="location_id" class="form-control">
                <option value="">Select Location</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}" 
                        @if(old('location_id', $transaction->location_id ?? '') == $loc->id) selected @endif>
                        {{ $loc->location_name }} ({{ $loc->location_id }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Transaction Type --}}
        <div class="mb-3" id="transaction_type_section" style="display:none;">
            <label for="transaction_type">Transaction Type <span class="text-danger">*</span></label>
            <select name="transaction_type" id="transaction_type" class="form-control" required>
                <option value="">Select Type</option>
                <option value="assign" @if(old('transaction_type', $transaction->transaction_type ?? '') == 'assign') selected @endif>Assign</option>
                <option value="return" @if(old('transaction_type', $transaction->transaction_type ?? '') == 'return') selected @endif>Return</option>
            </select>
            <small class="text-muted" id="transaction_type_info"></small>
        </div>

        {{-- Transaction Specific Fields --}}
        {{-- Assign Fields --}}
        <div class="mb-3" id="assign_fields" style="display:none;">
            <label for="issue_date">Assigned Date <span class="text-danger">*</span></label>
            <input type="date" name="issue_date" id="issue_date" class="form-control" required
                   value="{{ old('issue_date', $transaction->issue_date ?? date('Y-m-d')) }}">
            
            <div class="mb-3 mt-3">
                <label for="assign_image" class="form-label">
                    <i class="bi bi-camera me-2"></i>Upload Asset Image <span class="text-danger">*</span>
                </label>
                <input type="file" name="assign_image" id="assign_image" class="form-control" accept="image/*" required>
                <small class="text-muted">Upload an image of the asset during assignment (Max: 5MB, Formats: JPG, PNG, GIF)</small>
                @if(isset($transaction) && $transaction->assign_image)
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $transaction->assign_image) }}" alt="Assign Image" 
                             class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                        <p class="text-muted small mt-1">Current Image</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Return Fields --}}
        <div class="mb-3" id="return_fields" style="display:none;">
            <label for="return_date">Return Date <span class="text-danger">*</span></label>
            <input type="date" name="return_date" id="return_date" class="form-control" required
                   value="{{ old('return_date', $transaction->return_date ?? date('Y-m-d')) }}">
            
            <div class="mb-3 mt-3">
                <label for="return_image" class="form-label">
                    <i class="bi bi-camera me-2"></i>Upload Asset Image (Return)
                </label>
                <input type="file" name="return_image" id="return_image" class="form-control" accept="image/*">
                <small class="text-muted">Upload an image of the asset during return (Max: 5MB, Formats: JPG, PNG, GIF)</small>
                @if(isset($transaction) && $transaction->return_image)
                    <div class="mt-2">
                        <img src="{{ asset('storage/' . $transaction->return_image) }}" alt="Return Image" 
                             class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                        <p class="text-muted small mt-1">Current Image</p>
                    </div>
                @endif
            </div>
        </div>


        <button type="submit" class="btn btn-primary" id="submitBtn">
            <i class="bi bi-check-circle me-2"></i>{{ $isEdit ? 'Update' : 'Save' }} Transaction
        </button>
        <a href="{{ route('asset-transactions.index') }}" class="btn btn-secondary">
            <i class="bi bi-x-circle me-2"></i>Cancel
        </a>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoryDropdown = document.getElementById('asset_category_id');
    const assetDropdown = document.getElementById('asset_id');
    const employeeSection = document.getElementById('employee_section');
    const employeeDisplaySection = document.getElementById('employee_display_section');
    const projectSection = document.getElementById('project_section');
    const locationSection = document.getElementById('location_section');
    const transactionType = document.getElementById('transaction_type');
    const transactionTypeSection = document.getElementById('transaction_type_section');
    const assignFields = document.getElementById('assign_fields');
    const returnFields = document.getElementById('return_fields');
    const assetStatusInfo = document.getElementById('asset_status_info');
    const employeeAutoFillInfo = document.getElementById('employee_auto_fill_info');
    const projectAutoFillInfo = document.getElementById('project_auto_fill_info');
    const transactionTypeInfo = document.getElementById('transaction_type_info');
    const assetSelectionSection = document.getElementById('asset_selection_section');

    let currentCategory = '';
    let assetDetails = null;

    // Handle category change
    categoryDropdown.addEventListener('change', function() {
        const categoryId = this.value;
        currentCategory = '';
        
        if (!categoryId) {
            assetSelectionSection.style.display = 'none';
            hideAllFields();
            return;
        }

        // Show asset selection
        assetSelectionSection.style.display = 'block';
        assetDropdown.innerHTML = '<option value="">Loading assets...</option>';

        // Fetch assets for this category
        fetch(`/asset-transactions/get-assets-by-category/${categoryId}`)
            .then(res => res.json())
            .then(assets => {
                assetDropdown.innerHTML = '<option value="">Select Asset</option>';
                assets.forEach(asset => {
                    const option = document.createElement('option');
                    option.value = asset.id;
                    // Use display status (already normalized to "available" if returned)
                    option.textContent = `${asset.serial_number} (${asset.asset_id}) - Status: ${asset.status}`;
                    // Store original status for logic, or use status if original_status not provided
                    option.dataset.status = asset.original_status || asset.status;
                    option.dataset.category = asset.category_name.toLowerCase();
                    assetDropdown.appendChild(option);
                });
                
                if (assets.length > 0) {
                    currentCategory = assets[0].category_name.toLowerCase();
                }
            })
            .catch(err => {
                console.error('Error loading assets:', err);
                assetDropdown.innerHTML = '<option value="">Error loading assets</option>';
            });
    });

    // Handle asset selection
    assetDropdown.addEventListener('change', function() {
        const assetId = this.value;
        
        if (!assetId) {
            hideAssignmentFields();
            return;
        }

        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption && selectedOption.dataset.status) {
            const originalStatus = selectedOption.dataset.status;
            const category = selectedOption.dataset.category || '';
            currentCategory = category;
            
            // Show "available" instead of "returned" in the UI
            const displayStatus = originalStatus === 'returned' ? 'available' : originalStatus;
            assetStatusInfo.textContent = `Current Status: ${displayStatus}`;
            assetStatusInfo.className = 'text-muted';

            // Fetch asset details including previous transaction info
            fetch(`/asset-transactions/get-asset-details/${assetId}`)
                .then(res => res.json())
                .then(data => {
                    assetDetails = data;
                    updateTransactionTypeOptions(data);
                    showAssignmentFields(data, category);
                    
                    // If transaction type is already set to 'assign', ensure all fields are shown
                    if (transactionType.value === 'assign') {
                        assignFields.style.display = 'block';
                        updateEmployeeSectionVisibility();
                    }
                })
                .catch(err => {
                    console.error('Error loading asset details:', err);
                });
        }
    });

    // Handle transaction type change
    transactionType.addEventListener('change', function() {
        const txType = this.value;
        
        assignFields.style.display = 'none';
        returnFields.style.display = 'none';

        if (!txType) {
            // Reset employee section visibility based on asset status
            updateEmployeeSectionVisibility();
            return;
        }

        if (txType === 'assign') {
            assignFields.style.display = 'block';
            // Show appropriate fields based on category
            updateEmployeeSectionVisibility();
            // Ensure location is shown for laptop
            if (currentCategory.toLowerCase() === 'laptop') {
                locationSection.style.display = 'block';
            }
        } else if (txType === 'return') {
            returnFields.style.display = 'block';
            // Hide employee dropdown for return transactions
            employeeSection.style.display = 'none';
            employeeAutoFillInfo.textContent = '';
            
            // Show employee display section with auto-filled details
            if (assetDetails && assetDetails.current_employee_id) {
                employeeDisplaySection.style.display = 'block';
                document.getElementById('display_employee_name').textContent = assetDetails.current_employee_name || 'N/A';
                document.getElementById('display_employee_id').textContent = assetDetails.current_employee_id || 'N/A';
                document.getElementById('display_employee_entity').textContent = assetDetails.current_employee_entity || 'N/A';
                // Set both employee_id and employee_id_return (both are needed for return)
                const employeeIdField = document.getElementById('employee_id');
                const employeeReturnField = document.getElementById('employee_id_return');
                if (employeeIdField) employeeIdField.value = assetDetails.current_employee_id;
                if (employeeReturnField) employeeReturnField.value = assetDetails.current_employee_id;
                console.log('Set employee_id and employee_id_return in transaction type handler:', assetDetails.current_employee_id);
            } else {
                employeeDisplaySection.style.display = 'none';
                const employeeIdField = document.getElementById('employee_id');
                const employeeReturnField = document.getElementById('employee_id_return');
                if (employeeIdField) employeeIdField.value = '';
                if (employeeReturnField) employeeReturnField.value = '';
            }
        }
    });

    function showAssignmentFields(data, category) {
        transactionTypeSection.style.display = 'block';
        
        const categoryLower = category.toLowerCase();
        const txType = transactionType.value;
        
        // For return transactions, show employee details automatically
        if (txType === 'return' && data.current_employee_id) {
            employeeDisplaySection.style.display = 'block';
            employeeSection.style.display = 'none';
            document.getElementById('display_employee_name').textContent = data.current_employee_name || 'N/A';
            document.getElementById('display_employee_id').textContent = data.current_employee_id || 'N/A';
            document.getElementById('display_employee_entity').textContent = data.current_employee_entity || 'N/A';
            // Set both the select field and hidden field for return transactions
            document.getElementById('employee_id').value = data.current_employee_id;
            document.getElementById('employee_id_return').value = data.current_employee_id;
        } else {
            employeeDisplaySection.style.display = 'none';
        }
        
        // Show fields based on category
        if (categoryLower === 'laptop') {
            // For Laptop: Show employee and location
            if (txType === 'assign') {
                locationSection.style.display = 'block';
                
                // Auto-fill employee if available and assigning
                if (data.current_employee_id && txType === 'assign') {
                    document.getElementById('employee_id').value = data.current_employee_id;
                    employeeAutoFillInfo.textContent = `Auto-filled: ${data.current_employee_name || 'Previous employee'}`;
                    employeeAutoFillInfo.className = 'text-success';
                }
                
                // Auto-fill location if available
                if (data.current_location_id) {
                    document.getElementById('location_id').value = data.current_location_id;
                }
            }
        } else if (categoryLower === 'printer') {
            // For Printer: Show project name
            locationSection.style.display = 'none';
            
            // Auto-fill project if available
            if (data.current_project_name && txType === 'assign') {
                document.getElementById('project_name').value = data.current_project_name;
                projectAutoFillInfo.textContent = `Auto-filled: ${data.current_project_name}`;
                projectAutoFillInfo.className = 'text-success';
            }
        } else {
            // For other categories
            locationSection.style.display = 'none';
        }
        
        // Update visibility based on transaction type and category
        updateEmployeeSectionVisibility();
        
        // If transaction type is 'assign', show assign fields (Assigned Date and Asset Image)
        if (txType === 'assign') {
            assignFields.style.display = 'block';
        } else if (txType === 'return') {
            returnFields.style.display = 'block';
        }
    }
    
    function updateEmployeeSectionVisibility() {
        const txType = transactionType.value;
        const categoryLower = currentCategory.toLowerCase();
        
        if (txType === 'return') {
            // Hide employee dropdown for return
            employeeSection.style.display = 'none';
            projectSection.style.display = 'none';
            const employeeIdField = document.getElementById('employee_id');
            const employeeReturnField = document.getElementById('employee_id_return');
            document.getElementById('project_name').value = '';
            document.getElementById('project_name').required = false;
            employeeAutoFillInfo.textContent = '';
            projectAutoFillInfo.textContent = '';
            
            // Show employee display section if asset has assigned employee
            if (assetDetails && assetDetails.current_employee_id) {
                employeeDisplaySection.style.display = 'block';
                document.getElementById('display_employee_name').textContent = assetDetails.current_employee_name || 'N/A';
                document.getElementById('display_employee_id').textContent = assetDetails.current_employee_id || 'N/A';
                document.getElementById('display_employee_entity').textContent = assetDetails.current_employee_entity || 'N/A';
                // Set both employee_id and employee_id_return (both are needed)
                if (employeeIdField) {
                    employeeIdField.value = assetDetails.current_employee_id;
                    employeeIdField.required = false;
                }
                if (employeeReturnField) {
                    employeeReturnField.value = assetDetails.current_employee_id;
                }
                console.log('Set employee_id and employee_id_return for return:', assetDetails.current_employee_id);
            } else {
                employeeDisplaySection.style.display = 'none';
                if (employeeIdField) employeeIdField.value = '';
                if (employeeReturnField) employeeReturnField.value = '';
            }
        } else if (txType === 'assign') {
            // Hide employee display section for assign
            employeeDisplaySection.style.display = 'none';
            // Always show assign fields (Assigned Date and Asset Image)
            assignFields.style.display = 'block';
            
            // Show appropriate fields based on category
            if (categoryLower === 'laptop') {
                employeeSection.style.display = 'block';
                if (projectSection) projectSection.style.display = 'none';
                document.getElementById('employee_id').required = true;
                if (document.getElementById('employee_required')) {
                    document.getElementById('employee_required').style.display = 'inline';
                }
                if (document.getElementById('project_name')) {
                    document.getElementById('project_name').required = false;
                }
                // Show location for laptop
                locationSection.style.display = 'block';
            } else if (categoryLower === 'printer') {
                employeeSection.style.display = 'none';
                if (projectSection) projectSection.style.display = 'block';
                if (document.getElementById('project_name')) {
                    document.getElementById('project_name').required = true;
                }
                if (document.getElementById('project_required')) {
                    document.getElementById('project_required').style.display = 'inline';
                }
                document.getElementById('employee_id').required = false;
                if (document.getElementById('employee_required')) {
                    document.getElementById('employee_required').style.display = 'none';
                }
                locationSection.style.display = 'none';
            }
        }
    }

    function updateTransactionTypeOptions(data) {
        const txTypeSelect = transactionType;
        const availableTypes = data.available_transactions || [];
        
        // Clear existing options except the first one
        txTypeSelect.innerHTML = '<option value="">Select Type</option>';
        
        // Add available transaction types
        const allTypes = [
            { value: 'assign', label: 'Assign' },
            { value: 'return', label: 'Return' }
        ];
        
        allTypes.forEach(type => {
            if (availableTypes.includes(type.value)) {
                const option = document.createElement('option');
                option.value = type.value;
                option.textContent = type.label;
                txTypeSelect.appendChild(option);
            }
        });

        // Show info message - normalize "returned" to "available"
        const displayStatus = data.status === 'returned' ? 'available' : data.status;
        
        if (displayStatus === 'under_maintenance') {
            transactionTypeInfo.textContent = 'Asset is under maintenance. You can assign it (return from maintenance to same employee).';
            transactionTypeInfo.className = 'text-warning';
        } else if (displayStatus === 'assigned') {
            transactionTypeInfo.textContent = 'Asset is currently assigned. You can return it. Use System Maintenance form to send for maintenance.';
            transactionTypeInfo.className = 'text-info';
        } else {
            transactionTypeInfo.textContent = 'Asset is available for assignment to a new employee.';
            transactionTypeInfo.className = 'text-success';
        }
        
        // Update employee section visibility when transaction type changes
        updateEmployeeSectionVisibility();
    }

    function hideAssignmentFields() {
        employeeSection.style.display = 'none';
        locationSection.style.display = 'none';
        transactionTypeSection.style.display = 'none';
        assignFields.style.display = 'none';
        returnFields.style.display = 'none';
    }

    function hideAllFields() {
        assetSelectionSection.style.display = 'none';
        hideAssignmentFields();
    }

    // Form submission handler
    console.log('Setting up form submission handler...');
    const form = document.getElementById('transactionForm');
    const submitBtn = document.getElementById('submitBtn');
    
    console.log('Form element:', form);
    console.log('Submit button:', submitBtn);
    
    if (!form) {
        console.error('ERROR: Form element not found!');
    }
    if (!submitBtn) {
        console.error('ERROR: Submit button not found!');
    }
    
    if (form && submitBtn) {
        console.log('Adding submit event listener...');
        
        // Also add click handler to button as backup
        submitBtn.addEventListener('click', function(e) {
            console.log('=== SUBMIT BUTTON CLICKED ===');
            try {
                const txType = transactionType ? transactionType.value : 'unknown';
                console.log('Transaction type:', txType);
                
                // For return, ensure employee_id is set
                if (txType === 'return') {
                    const empSelect = document.getElementById('employee_id');
                    const empReturn = document.getElementById('employee_id_return');
                    console.log('Employee ID select value:', empSelect ? empSelect.value : 'element not found');
                    console.log('Employee ID return value:', empReturn ? empReturn.value : 'element not found');
                    
                    // Try to set employee_id from various sources
                    if (empSelect && empReturn) {
                        if (!empSelect.value && empReturn.value) {
                            empSelect.value = empReturn.value;
                            console.log('Set employee_id from employee_id_return:', empReturn.value);
                        } else if (!empReturn.value && empSelect.value) {
                            empReturn.value = empSelect.value;
                            console.log('Set employee_id_return from employee_id:', empSelect.value);
                        } else if (!empReturn.value && assetDetails && assetDetails.current_employee_id) {
                            empReturn.value = assetDetails.current_employee_id;
                            empSelect.value = assetDetails.current_employee_id;
                            console.log('Set both from assetDetails:', assetDetails.current_employee_id);
                        }
                    }
                }
            } catch (err) {
                console.error('Error in button click handler:', err);
            }
            // Don't prevent default - let form submit
            return true;
        });
        
        form.addEventListener('submit', function(e) {
            console.log('=== FORM SUBMIT EVENT TRIGGERED ===');
            
            const txType = transactionType ? transactionType.value : '';
            const assetId = document.getElementById('asset_id') ? document.getElementById('asset_id').value : '';
            
            console.log('Transaction type:', txType);
            console.log('Asset ID:', assetId);
            
            // Remove required attributes from hidden fields to prevent browser validation blocking
            const assignFields = document.getElementById('assign_fields');
            const returnFields = document.getElementById('return_fields');
            const issueDate = document.getElementById('issue_date');
            const returnDate = document.getElementById('return_date');
            const assignImage = document.getElementById('assign_image');
            
            if (txType === 'assign') {
                // For assign, make sure assign fields are required
                if (issueDate) issueDate.required = true;
                if (assignImage) assignImage.required = true;
                // Remove required from return fields
                if (returnDate) returnDate.required = false;
            } else if (txType === 'return') {
                // For return, make sure return fields are required
                if (returnDate) returnDate.required = true;
                // Remove required from assign fields
                if (issueDate) issueDate.required = false;
                if (assignImage) assignImage.required = false;
                
                // Ensure employee_id is set for return
                const employeeSelect = document.getElementById('employee_id');
                const employeeReturn = document.getElementById('employee_id_return');
                
                // Get employee_id from various sources
                let employeeId = null;
                if (employeeReturn && employeeReturn.value) {
                    employeeId = employeeReturn.value;
                } else if (employeeSelect && employeeSelect.value) {
                    employeeId = employeeSelect.value;
                } else if (assetDetails && assetDetails.current_employee_id) {
                    employeeId = assetDetails.current_employee_id;
                }
                
                // Set both fields to ensure employee_id is available
                if (employeeId) {
                    if (employeeSelect) {
                        employeeSelect.value = employeeId;
                        employeeSelect.required = false; // Don't require if hidden
                    }
                    if (employeeReturn) employeeReturn.value = employeeId;
                    console.log('Set employee_id for return:', employeeId);
                } else {
                    console.error('ERROR: Cannot find employee_id for return transaction!');
                    console.log('assetDetails:', assetDetails);
                    e.preventDefault();
                    alert('Error: Cannot determine employee. Please refresh the page and try again.');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i>Save Transaction';
                    }
                    return false;
                }
            }
            
            // Log final form data
            const formData = new FormData(form);
            console.log('=== FINAL FORM DATA ===');
            for (let [key, value] of formData.entries()) {
                console.log(key + ':', value);
            }
            
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
            }
            
            // Allow form to submit
            console.log('Submitting form to:', form.action);
            console.log('Form method:', form.method);
            return true;
        });
    }

    // Initialize on page load
    if (categoryDropdown.value) {
        categoryDropdown.dispatchEvent(new Event('change'));
    }
    
    // Final test - make sure form can submit
    console.log('=== FORM SETUP COMPLETE ===');
    console.log('Form found:', !!form);
    console.log('Submit button found:', !!submitBtn);
    console.log('Form action:', form ? form.action : 'N/A');
    
    // Add a direct test button click handler as fallback
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            console.log('=== DIRECT BUTTON CLICK ===');
            console.log('Event:', e);
            
            // If form doesn't submit after 100ms, try programmatic submit
            setTimeout(function() {
                if (!form.checkValidity()) {
                    console.log('Form validation failed, showing validation errors');
                    form.reportValidity();
                } else {
                    console.log('Form is valid, attempting programmatic submit');
                    // Don't prevent default, let normal submit happen
                }
            }, 100);
        }, true); // Use capture phase
    }
    
    // Also add a noValidate attribute to form to bypass HTML5 validation if needed
    // But we'll handle validation manually
    console.log('Form setup complete. Form action:', form ? form.action : 'N/A');
});
</script>
@endsection
